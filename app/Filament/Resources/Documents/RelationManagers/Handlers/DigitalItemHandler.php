<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Wizard\Step;
use App\Models\DigitalItem;

class DigitalItemHandler extends AbstractItemHandler
{
    public function getEditForm($record): array
    {
        return [
            $this->makeSection('Editar Item Digital', 'Modificar servicio digital')
                ->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción del Servicio')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                        
                    $this->makeGrid(3)->schema([
                        $this->makeTextInput('quantity', 'Cantidad')
                            ->suffix('unidades'),
                            
                        $this->makeSelect('pricing_type', 'Tipo de Valoración', [
                            'unit' => 'Por Unidad',
                            'size' => 'Por Tamaño (m²)',
                        ]),
                        
                        $this->makeTextInput('unit_value', 'Valor Unitario')
                            ->prefix('$')
                            ->numeric(),
                    ]),
                ])
        ];
    }
    
    public function fillForm($record): array
    {
        $item = $record->itemable;
        return $item ? $item->toArray() : [];
    }
    
    public function handleUpdate($record, array $data): void
    {
        $item = $record->itemable;
        if ($item) {
            $item->update($data);
            $item->calculateAll();
            $record->calculateAndUpdatePrices();
        }
    }
    
    public function getWizardStep(): Step
    {
        return Step::make('Item Digital')
            ->description('Servicios digitales con valoración flexible')
            ->icon('heroicon-o-computer-desktop')
            ->schema([
                $this->makeSection('Información del Servicio')->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción del Servicio Digital')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Ej: Diseño gráfico, Desarrollo web, Fotografía...'),
                        
                    $this->makeGrid(2)->schema([
                        $this->makeTextInput('quantity', 'Cantidad')
                            ->default(1)
                            ->suffix('unidades'),
                            
                        $this->makeSelect('pricing_type', 'Tipo de Valoración', [
                            'unit' => 'Por Unidad - Precio fijo',
                            'size' => 'Por Tamaño (m²) - Según dimensiones',
                        ])->default('unit')
                        ->live(),
                    ]),
                ]),
                
                $this->makeSection('Valoración')->schema([
                    $this->makeTextInput('unit_value', 'Valor')
                        ->prefix('$')
                        ->numeric()
                        ->required()
                        ->helperText(function ($get) {
                            return $get('pricing_type') === 'size' 
                                ? 'Valor por metro cuadrado'
                                : 'Valor por unidad';
                        }),
                        
                    $this->makeGrid(2)
                        ->schema([
                            $this->makeTextInput('width', 'Ancho')
                                ->suffix('cm')
                                ->numeric(),
                                
                            $this->makeTextInput('height', 'Alto')
                                ->suffix('cm') 
                                ->numeric(),
                        ])
                        ->visible(fn ($get) => $get('pricing_type') === 'size'),
                ]),
            ]);
    }
}