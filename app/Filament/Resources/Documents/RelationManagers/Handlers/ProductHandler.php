<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Wizard\Step;
use App\Models\Product;

class ProductHandler extends AbstractItemHandler
{
    public function getEditForm($record): array
    {
        return [
            $this->makeSection('Editar Producto', 'Modificar cantidad del producto')
                ->schema([
                    Components\TextInput::make('quantity')
                        ->label('Cantidad')
                        ->numeric()
                        ->required()
                        ->minValue(1),
                        
                    Components\TextInput::make('unit_price')
                        ->label('Precio Unitario')
                        ->numeric()
                        ->prefix('$')
                        ->readOnly(),
                ])
        ];
    }
    
    public function fillForm($record): array
    {
        return [
            'quantity' => $record->quantity,
            'unit_price' => $record->unit_price,
        ];
    }
    
    public function handleUpdate($record, array $data): void
    {
        $record->update([
            'quantity' => $data['quantity'],
            'total_price' => $data['quantity'] * $record->unit_price,
        ]);
    }
    
    public function getWizardStep(): Step
    {
        return Step::make('Producto')
            ->description('Productos del inventario')
            ->icon('heroicon-o-cube')
            ->schema([
                Components\Select::make('product_id')
                    ->label('Seleccionar Producto')
                    ->options(function () {
                        return Product::where('company_id', auth()->user()->company_id)
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        if ($state) {
                            $product = Product::find($state);
                            if ($product) {
                                $set('unit_price', $product->price);
                                $set('description', $product->description);
                                $set('stock_available', $product->stock);
                            }
                        }
                    }),
                    
                $this->makeGrid(3)->schema([
                    $this->makeTextInput('quantity', 'Cantidad')
                        ->default(1)
                        ->minValue(1),
                        
                    Components\TextInput::make('unit_price')
                        ->label('Precio Unitario')
                        ->prefix('$')
                        ->numeric()
                        ->readOnly(),
                        
                    Components\Placeholder::make('stock_available')
                        ->label('Stock Disponible')
                        ->content(fn ($get) => ($get('stock_available') ?? 0) . ' unidades'),
                ]),
            ]);
    }
}