<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\OrderStatus;
use Filament\Forms\Components;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Orden')
                    ->description('Datos principales de la orden de pedido')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Components\TextInput::make('order_number')
                                    ->label('Número de Orden')
                                    ->disabled()
                                    ->dehydrated(false),

                                Components\Select::make('status')
                                    ->label('Estado')
                                    ->options(OrderStatus::class)
                                    ->required()
                                    ->default(OrderStatus::DRAFT)
                                    ->native(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Components\Select::make('supplier_company_id')
                                    ->label('Proveedor')
                                    ->relationship('supplierCompany', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Components\TextInput::make('total_amount')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),

                Section::make('Fechas')
                    ->description('Fechas importantes de la orden')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Components\DatePicker::make('order_date')
                                    ->label('Fecha de Orden')
                                    ->required()
                                    ->default(now()),

                                Components\DatePicker::make('expected_delivery_date')
                                    ->label('Fecha de Entrega Esperada')
                                    ->after('order_date'),

                                Components\DatePicker::make('actual_delivery_date')
                                    ->label('Fecha de Entrega Real')
                                    ->after('order_date')
                                    ->visible(fn ($get) => in_array($get('status'), ['completed', 'partially_received'])),
                            ]),
                    ]),

                Section::make('Información Adicional')
                    ->description('Notas y observaciones')
                    ->schema([
                        Components\Textarea::make('notes')
                            ->label('Notas')
                            ->placeholder('Notas adicionales sobre la orden...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Metadatos')
                    ->description('Información de auditoría')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Components\Select::make('created_by')
                                    ->label('Creado por')
                                    ->relationship('createdBy', 'name')
                                    ->disabled()
                                    ->dehydrated(false),

                                Components\Select::make('approved_by')
                                    ->label('Aprobado por')
                                    ->relationship('approvedBy', 'name')
                                    ->visible(fn ($get) => $get('status') !== 'draft'),
                            ]),

                        Components\DateTimePicker::make('approved_at')
                            ->label('Fecha de Aprobación')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($get) => $get('approved_at')),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
