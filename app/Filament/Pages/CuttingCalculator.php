<?php

namespace App\Filament\Pages;

use App\Services\CuttingCalculatorService;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CuttingCalculator extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.cutting-calculator';
    
    // protected static string|null $navigationIcon = 'heroicon-o-calculator';
    
    protected static ?string $navigationLabel = 'Calculadora de Cortes';
    
    protected static ?string $title = 'Calculadora de Cortes de Papel';

    public ?array $data = [];
    public ?array $results = null;

    public function mount(): void
    {
        $this->loadDefaultData();
    }

    private function getDefaultData(): array
    {
        return [
            'paper_width' => 100,
            'paper_height' => 70,
            'cut_width' => 22,
            'cut_height' => 28,
            'desired_cuts' => 100,
            'orientation' => 'horizontal'
        ];
    }

    private function loadDefaultData(): void
    {
        $defaultData = $this->getDefaultData();
        
        $this->form->fill($defaultData);
        $this->data = $defaultData;
        
        // Realizar cálculo automático con los datos predefinidos
        $this->performInitialCalculation();
    }

    private function performInitialCalculation(): void
    {
        try {
            $calculator = new CuttingCalculatorService();
            
            $this->results = $calculator->calculateCuts(
                paperWidth: (float) $this->data['paper_width'],
                paperHeight: (float) $this->data['paper_height'],
                cutWidth: (float) $this->data['cut_width'],
                cutHeight: (float) $this->data['cut_height'],
                desiredCuts: (int) $this->data['desired_cuts'],
                orientation: $this->data['orientation']
            );
            
            // Actualizar canvas después del cálculo inicial
            $this->updateCanvasJS('inicial');
        
            
        } catch (\Exception $e) {
            // En caso de error en el cálculo inicial, simplemente no mostramos resultados
            $this->results = null;
        }
    }

    private function updateCanvasJS(string $context = 'general'): void
    {
        $this->js("
            console.log('Actualizando canvas - contexto: {$context}');
            setTimeout(function() {
                try {
                    if (typeof initCanvas === 'function') {
                        initCanvas();
                        console.log('Canvas actualizado exitosamente - {$context}');
                    } else {
                        console.error('Función initCanvas no encontrada - {$context}');
                    }
                } catch (error) {
                    console.error('Error actualizando canvas - {$context}:', error);
                }
            }, 100);
        ");
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dimensiones del Papel')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('paper_width')
                                    ->label('Ancho del Papel (cm)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->maxValue(125)
                                    ->required()
                                    ->suffix('cm'),
                                    
                                TextInput::make('paper_height')
                                    ->label('Largo del Papel (cm)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->maxValue(125)
                                    ->required()
                                    ->suffix('cm'),
                            ]),
                    ]),

                Section::make('Dimensiones del Corte')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('cut_width')
                                    ->label('Ancho del Corte (cm)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->required()
                                    ->suffix('cm'),
                                    
                                TextInput::make('cut_height')
                                    ->label('Largo del Corte (cm)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->required()
                                    ->suffix('cm'),
                            ]),
                    ]),

                Section::make('Configuración de Cálculo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('desired_cuts')
                                    ->label('Cortes Deseados')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required()
                                    ->suffix('unidades'),
                                    
                                Select::make('orientation')
                                    ->label('Orientación')
                                    ->options([
                                        'horizontal' => 'Horizontal',
                                        'vertical' => 'Vertical',
                                        'maximum' => 'Máximo Aprovechamiento'
                                    ])
                                    ->default('horizontal')
                                    ->required(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('calculate')
                ->label('Calcular')
                ->icon('heroicon-m-calculator')
                ->action('calculate'),
                
            Action::make('reset')
                ->label('Restaurar')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action('resetForm'),
        ];
    }

    public function calculate(): void
    {
        $data = $this->form->getState();
        
        try {
            $calculator = new CuttingCalculatorService();
            
            $this->results = $calculator->calculateCuts(
                paperWidth: (float) $data['paper_width'],
                paperHeight: (float) $data['paper_height'],
                cutWidth: (float) $data['cut_width'],
                cutHeight: (float) $data['cut_height'],
                desiredCuts: (int) $data['desired_cuts'],
                orientation: $data['orientation']
            );

            // Almacenar los datos del formulario para el canvas
            $this->data = $data;

            // Actualizar canvas inmediatamente
            $this->updateCanvasJS('calculate');

            Notification::make()
                ->title('Cálculo realizado exitosamente')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error en el cálculo')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetForm(): void
    {
        $this->loadDefaultData();
        
        // Actualizar canvas después de restaurar
        $this->updateCanvasJS('restore');
        
        Notification::make()
            ->title('Formulario restaurado con datos predefinidos')
            ->success()
            ->send();
    }
}
