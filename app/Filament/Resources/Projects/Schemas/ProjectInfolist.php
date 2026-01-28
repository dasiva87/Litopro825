<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Header Section
                Section::make()
                    ->columnSpan(2)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('code')
                            ->label('Código')
                            ->icon('heroicon-o-hashtag')
                            ->weight(FontWeight::Bold)
                            ->copyable(),

                        TextEntry::make('name')
                            ->label('Nombre')
                            ->icon('heroicon-o-rectangle-stack')
                            ->weight(FontWeight::Bold),

                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (ProjectStatus $state) => $state->getLabel()),

                        TextEntry::make('contact.name')
                            ->label('Cliente')
                            ->icon('heroicon-o-user'),
                    ]),

                // Description
                Section::make()
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('description')
                            ->label('Descripción')
                            ->placeholder('Sin descripción')
                            ->columnSpanFull(),
                    ]),

                // Dates Section
                Section::make('Fechas')
                    ->columnSpan(1)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('start_date')
                            ->label('Inicio')
                            ->date('d M, Y')
                            ->icon('heroicon-o-calendar'),

                        TextEntry::make('estimated_end_date')
                            ->label('Fin Estimado')
                            ->date('d M, Y')
                            ->icon('heroicon-o-clock')
                            ->placeholder('Sin fecha'),

                        TextEntry::make('budget')
                            ->label('Presupuesto')
                            ->money('COP')
                            ->icon('heroicon-o-currency-dollar')
                            ->placeholder('No definido'),

                        TextEntry::make('completion_percentage')
                            ->label('Progreso')
                            ->suffix('%')
                            ->icon('heroicon-o-chart-bar')
                            ->color(fn ($state) => match (true) {
                                $state >= 100 => 'success',
                                $state >= 50 => 'warning',
                                default => 'gray',
                            }),
                    ]),

                // Summary Section
                Section::make('Resumen de Documentos')
                    ->columnSpan(1)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('documents_count')
                            ->label('Cotizaciones')
                            ->state(fn ($record) => $record->documents()->count())
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('purchase_orders_count')
                            ->label('Órdenes de Pedido')
                            ->state(fn ($record) => $record->purchaseOrders()->count())
                            ->badge()
                            ->color('info'),

                        TextEntry::make('production_orders_count')
                            ->label('Órdenes de Producción')
                            ->state(fn ($record) => $record->productionOrders()->count())
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('collection_accounts_count')
                            ->label('Cuentas de Cobro')
                            ->state(fn ($record) => $record->collectionAccounts()->count())
                            ->badge()
                            ->color('success'),
                    ]),

                // Notes
                Section::make('Notas')
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notas Internas')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
