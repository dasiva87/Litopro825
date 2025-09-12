<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Wizard\Step;
use App\Models\CustomItem;

class CustomItemHandler extends AbstractItemHandler
{
    public function getEditForm($record): array
    {
        return [
            $this->makeSection('Editar Item Personalizado', 'Modificar item personalizado')
                ->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción del Item')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                        
                    $this->makeGrid(3)->schema([
                        $this->makeTextInput('quantity', 'Cantidad')
                            ->suffix('unidades')
                            ->minValue(1),
                            
                        Components\TextInput::make('unit_price')
                            ->label('Precio Unitario')
                            ->prefix('$')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                            
                        Components\Placeholder::make('total')
                            ->label('Total')
                            ->content(function ($get) {
                                $quantity = $get('quantity') ?? 1;
                                $unitPrice = $get('unit_price') ?? 0;
                                return '$' . number_format($quantity * $unitPrice, 2);
                            }),
                    ]),
                ])
        ];
    }
    
    public function fillForm($record): array
    {
        return [
            'description' => $record->itemable->description ?? '',
            'quantity' => $record->quantity,
            'unit_price' => $record->unit_price,
        ];
    }
    
    public function handleUpdate($record, array $data): void
    {
        if ($record->itemable) {
            $record->itemable->update([
                'description' => $data['description'],
            ]);
        }
        
        $record->update([
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'total_price' => $data['quantity'] * $data['unit_price'],
        ]);
    }
    
    public function getWizardStep(): Step
    {
        return Step::make('Personalizado')
            ->description('Item con precio manual')
            ->icon('heroicon-o-wrench-screwdriver')
            ->schema([
                Components\Textarea::make('description')
                    ->label('Descripción del Item')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('Describe el item personalizado...'),
                    
                $this->makeGrid(3)->schema([
                    $this->makeTextInput('quantity', 'Cantidad')
                        ->default(1)
                        ->suffix('unidades')
                        ->minValue(1)
                        ->live(),
                        
                    Components\TextInput::make('unit_price')
                        ->label('Precio Unitario')
                        ->prefix('$')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->live(),
                        
                    Components\Placeholder::make('calculated_total')
                        ->label('Total Calculado')
                        ->content(function ($get) {
                            $quantity = $get('quantity') ?? 1;
                            $unitPrice = $get('unit_price') ?? 0;
                            return '$' . number_format($quantity * $unitPrice, 2);
                        }),
                ]),
                
                Components\Textarea::make('notes')
                    ->label('Notas Adicionales')
                    ->rows(2)
                    ->columnSpanFull()
                    ->placeholder('Notas adicionales sobre el item...'),
            ]);
    }
}