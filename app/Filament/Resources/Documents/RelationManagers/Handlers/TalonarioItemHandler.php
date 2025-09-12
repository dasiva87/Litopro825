<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Wizard\Step;
use App\Models\TalonarioItem;

class TalonarioItemHandler extends AbstractItemHandler
{
    public function getEditForm($record): array
    {
        return [
            $this->makeSection('Editar Talonario', 'Modificar configuración del talonario')
                ->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción del Talonario')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                        
                    $this->makeGrid(2)->schema([
                        $this->makeTextInput('quantity', 'Cantidad de Talonarios')
                            ->suffix('talonarios')
                            ->default(10),
                            
                        $this->makeTextInput('profit_percentage', 'Margen de Ganancia')
                            ->suffix('%')
                            ->default(25),
                    ]),
                    
                    $this->makeGrid(3)->schema([
                        Components\TextInput::make('prefijo')
                            ->label('Prefijo')
                            ->default('Nº')
                            ->maxLength(10),
                            
                        $this->makeTextInput('numero_inicial', 'Número Inicial')
                            ->default(1)
                            ->minValue(1),
                            
                        $this->makeTextInput('numero_final', 'Número Final')
                            ->default(1000)
                            ->minValue(2),
                    ]),
                ])
        ];
    }
    
    public function fillForm($record): array
    {
        $talonario = $record->itemable;
        return $talonario ? $talonario->toArray() : [];
    }
    
    public function handleUpdate($record, array $data): void
    {
        $talonario = $record->itemable;
        if ($talonario) {
            $talonario->update($data);
            $talonario->calculateAll();
            $record->calculateAndUpdatePrices();
        }
    }
    
    public function getWizardStep(): Step
    {
        return Step::make('Talonario')
            ->description('Talonarios con numeración secuencial')
            ->icon('heroicon-o-clipboard-document-list')
            ->schema([
                $this->makeSection('Información Básica')->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción del Talonario')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Ej: Recibos de caja, Facturas comerciales...'),
                        
                    $this->makeGrid(2)->schema([
                        $this->makeTextInput('quantity', 'Cantidad de Talonarios')
                            ->default(10)
                            ->suffix('talonarios'),
                            
                        $this->makeTextInput('profit_percentage', 'Margen de Ganancia')
                            ->suffix('%')
                            ->default(25),
                    ]),
                ]),
                
                $this->makeSection('Configuración de Numeración')->schema([
                    $this->makeGrid(3)->schema([
                        Components\TextInput::make('prefijo')
                            ->label('Prefijo')
                            ->default('Nº')
                            ->maxLength(10)
                            ->placeholder('Nº, Rec., Fact.'),
                            
                        $this->makeTextInput('numero_inicial', 'Número Inicial')
                            ->default(1)
                            ->minValue(1),
                            
                        $this->makeTextInput('numero_final', 'Número Final')
                            ->default(1000)
                            ->minValue(2),
                    ]),
                    
                    $this->makeGrid(2)->schema([
                        $this->makeTextInput('numeros_por_talonario', 'Números por Talonario')
                            ->default(25)
                            ->minValue(1)
                            ->maxValue(100),
                            
                        Components\Placeholder::make('total_numbers')
                            ->label('Total de Números')
                            ->content(function ($get) {
                                $inicial = $get('numero_inicial') ?? 1;
                                $final = $get('numero_final') ?? 1000;
                                return ($final - $inicial + 1) . ' números';
                            }),
                    ]),
                ]),
            ]);
    }
}