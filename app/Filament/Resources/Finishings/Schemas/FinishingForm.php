<?php

namespace App\Filament\Resources\Finishings\Schemas;

use App\Enums\FinishingMeasurementUnit;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;

class FinishingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->description('Datos básicos del acabado')
                    ->icon('heroicon-o-information-circle')
                    ->components([
                        Grid::make(3)
                            ->components([
                                TextInput::make('code')
                                    ->label('Código')
                                    ->placeholder('Se genera automáticamente')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('measurement_unit')
                                    ->label('Unidad de Medida')
                                    ->options(FinishingMeasurementUnit::options())
                                    ->required()
                                    ->live()
                                    ->helperText(fn($state) => 
                                        $state ? FinishingMeasurementUnit::from($state)->description() : null
                                    ),
                            ]),
                        
                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Precios y Proveedor')
                    ->description('Configuración de precios y información del proveedor')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(3)
                    ->components([
                        TextInput::make('unit_price')
                            ->label('Precio Unitario')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0),
                        Toggle::make('is_own_provider')
                            ->label('Proveedor Propio')
                            ->helperText('¿Es un servicio/producto propio?'),
                        Toggle::make('active')
                            ->label('Activo')
                            ->default(true),
                    ]),

                Section::make('Rangos de Precios')
                    ->description('Configure los rangos de cantidad y precios específicos')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn($get) => $get('measurement_unit') === 'rango')
                    ->columnSpanFull()
                    ->components([
                        Repeater::make('ranges')
                            ->relationship('ranges')
                            ->label('')
                            ->addActionLabel('Agregar Rango')
                            ->defaultItems(1)
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->columns(4)
                            ->components([
                                TextInput::make('min_quantity')
                                    ->label('Cantidad Mínima')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1),
                                TextInput::make('max_quantity')
                                    ->label('Cantidad Máxima')
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('Dejar vacío para "sin límite"'),
                                TextInput::make('range_price')
                                    ->label('Precio del Rango')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->minValue(0),
                                TextInput::make('sort_order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Orden de presentación'),
                            ]),
                    ]),
            ]);
    }
}
