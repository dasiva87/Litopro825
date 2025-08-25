<?php

namespace App\Filament\Resources\SimpleItems\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use App\Models\Paper;
use App\Models\PrintingMachine;

class SimpleItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Describe detalladamente el trabajo a realizar...'),
                            
                        TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->suffix('unidades')
                            ->placeholder('1000'),
                    ]),
                    
                Section::make('Dimensiones del Producto')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('horizontal_size')
                                    ->label('Tamaño Horizontal')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->minValue(0)
                                    ->step(0.1)
                                    ->placeholder('21.0'),
                                    
                                TextInput::make('vertical_size')
                                    ->label('Tamaño Vertical')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->minValue(0)
                                    ->step(0.1)
                                    ->placeholder('29.7'),
                                    
                                Placeholder::make('area_calculation')
                                    ->label('Área Total')
                                    ->content(function ($get) {
                                        return $get('horizontal_size') && $get('vertical_size') ? 
                                            number_format($get('horizontal_size') * $get('vertical_size'), 2) . ' cm²' : 
                                            '- cm²';
                                    }),
                            ]),
                    ]),
                    
                Section::make('Papel y Máquina')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('paper_id')
                                    ->label('Papel')
                                    ->relationship('paper', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) => 
                                        $record->code . ' - ' . $record->name . 
                                        ' (' . $record->width . 'x' . $record->height . 'cm)'
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                    
                                Select::make('printing_machine_id')
                                    ->label('Máquina de Impresión')
                                    ->relationship('printingMachine', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) => 
                                        $record->name . ' - ' . ucfirst($record->type) .
                                        ' (Max: ' . $record->max_colors . ' colores)'
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),
                    
                Section::make('Información de Tintas')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('ink_front_count')
                                    ->label('Tintas Tiro')
                                    ->numeric()
                                    ->required()
                                    ->default(4)
                                    ->minValue(0)
                                    ->maxValue(8),
                                    
                                TextInput::make('ink_back_count')
                                    ->label('Tintas Retiro')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(8),
                                    
                                Toggle::make('front_back_plate')
                                    ->label('Tiro y Retiro Plancha'),
                            ]),
                    ]),
                    
                Section::make('Costos Adicionales')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('design_value')
                                    ->label('Valor Diseño')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01),
                                    
                                TextInput::make('transport_value')
                                    ->label('Valor Transporte')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01),
                                    
                                TextInput::make('rifle_value')
                                    ->label('Valor Rifle/Doblez')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01),
                                    
                                TextInput::make('profit_percentage')
                                    ->label('Porcentaje de Ganancia')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(30)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.1),
                            ]),
                    ]),
                    
                Section::make('Resumen de Costos')
                    ->description('Estos valores se calculan automáticamente')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('costs_breakdown')
                                    ->label('Desglose de Costos')
                                    ->content(function ($record) {
                                        if (!$record) return 'Guarda el item para ver el desglose';
                                        
                                        return 
                                            'Papel: $' . number_format($record->paper_cost, 2) . '<br>' .
                                            'Impresión: $' . number_format($record->printing_cost, 2) . '<br>' .
                                            'Montaje: $' . number_format($record->mounting_cost, 2);
                                    })
                                    ->html(),
                                    
                                Placeholder::make('final_pricing')
                                    ->label('Precio Final')
                                    ->content(function ($record) {
                                        if (!$record) return 'Guarda el item para ver el precio';
                                        
                                        return 
                                            '<strong>Total: $' . number_format($record->final_price, 2) . '</strong><br>' .
                                            'Por unidad: $' . number_format($record->final_price / max($record->quantity, 1), 4);
                                    })
                                    ->html(),
                            ]),
                    ]),
            ]);
    }
}
