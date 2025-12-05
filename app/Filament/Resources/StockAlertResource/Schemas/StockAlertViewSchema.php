<?php

namespace App\Filament\Resources\StockAlertResource\Schemas;

use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\KeyValue;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;

class StockAlertViewSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Información de la Alerta')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Título')
                                    ->disabled()
                                    ->columnSpanFull(),

                                Textarea::make('message')
                                    ->label('Mensaje')
                                    ->disabled()
                                    ->rows(3)
                                    ->columnSpanFull(),

                                TextInput::make('type')
                                    ->label('Tipo')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'low_stock' => 'Stock Bajo',
                                        'out_of_stock' => 'Sin Stock',
                                        'critical_low' => 'Stock Crítico',
                                        'reorder_point' => 'Punto de Reorden',
                                        'excess_stock' => 'Exceso de Stock',
                                        'movement_anomaly' => 'Movimiento Anómalo',
                                        default => $state
                                    }),

                                TextInput::make('severity')
                                    ->label('Severidad')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'low' => 'Baja',
                                        'medium' => 'Media',
                                        'high' => 'Alta',
                                        'critical' => 'Crítica',
                                        default => $state
                                    }),

                                TextInput::make('status')
                                    ->label('Estado')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'active' => 'Activa',
                                        'acknowledged' => 'Reconocida',
                                        'resolved' => 'Resuelta',
                                        'dismissed' => 'Descartada',
                                        default => $state
                                    }),

                                TextInput::make('current_stock')
                                    ->label('Stock Actual')
                                    ->disabled()
                                    ->suffix('unidades'),

                                TextInput::make('min_stock')
                                    ->label('Stock Mínimo')
                                    ->disabled()
                                    ->suffix('unidades'),

                                TextInput::make('threshold_value')
                                    ->label('Valor Umbral')
                                    ->disabled()
                                    ->suffix('unidades'),
                            ]),
                    ]),

                Section::make('Fechas y Responsables')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('triggered_at')
                                    ->label('Activada el')
                                    ->disabled(),

                                DateTimePicker::make('acknowledged_at')
                                    ->label('Reconocida el')
                                    ->disabled(),

                                DateTimePicker::make('resolved_at')
                                    ->label('Resuelta el')
                                    ->disabled(),

                                DateTimePicker::make('expires_at')
                                    ->label('Expira el')
                                    ->disabled(),
                            ]),
                    ])
                    ->collapsed(),

                Section::make('Metadatos')
                    ->schema([
                        KeyValue::make('metadata')
                            ->label('Información Adicional')
                            ->disabled(),
                    ])
                    ->collapsed()
                    ->hidden(fn ($record) => !$record || empty($record->metadata)),
            ]);
    }
}
