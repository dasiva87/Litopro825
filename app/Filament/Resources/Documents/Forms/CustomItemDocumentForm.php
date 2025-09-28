<?php

namespace App\Filament\Resources\Documents\Forms;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CustomItemDocumentForm
{
    public static function schema(): array
    {
        return [
            Forms\Components\Hidden::make('itemable_type')
                ->default('App\\Models\\CustomItem'),

            Section::make('Item Personalizado')
                ->description('Agrega un item personalizado con precios manuales')
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->label('Descripción del Item')
                        ->required()
                        ->rows(3)
                        ->placeholder('Describe el producto o servicio personalizado')
                        ->columnSpanFull(),

                    Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->suffix('unidades')
                                ->live()
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    $unitPrice = $get('unit_price') ?? 0;
                                    $total = $state * $unitPrice;
                                    $set('total_price', $total);
                                }),

                            Forms\Components\TextInput::make('unit_price')
                                ->label('Precio Unitario')
                                ->numeric()
                                ->required()
                                ->prefix('$')
                                ->step(0.01)
                                ->minValue(0)
                                ->live()
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    $quantity = $get('quantity') ?? 1;
                                    $total = $quantity * $state;
                                    $set('total_price', $total);
                                }),

                            Forms\Components\TextInput::make('total_price')
                                ->label('Precio Total')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(),
                        ]),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notas Adicionales')
                        ->rows(2)
                        ->placeholder('Notas internas sobre este item (opcional)')
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('custom_preview')
                        ->content(function ($get) {
                            $description = $get('description');
                            $quantity = $get('quantity') ?? 1;
                            $unitPrice = $get('unit_price') ?? 0;
                            $totalPrice = $quantity * $unitPrice;

                            if (empty($description)) {
                                return '📝 Agrega una descripción para ver la vista previa';
                            }

                            $content = '<div class="p-4 bg-green-50 rounded space-y-2">';
                            $content .= '<h4 class="font-semibold text-green-800">Vista Previa del Item</h4>';
                            $content .= '<div><strong>📋 Descripción:</strong> '.e($description).'</div>';
                            $content .= '<div><strong>📊 Cantidad:</strong> '.number_format($quantity).' unidades</div>';
                            $content .= '<div><strong>💰 Precio unitario:</strong> $'.number_format($unitPrice, 2).'</div>';
                            $content .= '<div class="mt-2 p-2 bg-white rounded border border-green-200">';
                            $content .= '<div class="text-lg font-semibold text-green-600">💵 Total: $'.number_format($totalPrice, 2).'</div>';
                            $content .= '</div>';
                            $content .= '<div class="text-green-600"><strong>✅ Item personalizado listo</strong></div>';
                            $content .= '</div>';

                            return $content;
                        })
                        ->html()
                        ->columnSpanFull(),
                ]),
        ];
    }
}