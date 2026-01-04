<?php

namespace App\Filament\Resources\ProductionOrders\Schemas;

use App\Enums\ProductionStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ProductionOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3) // UNA SOLA COLUMNA
            ->components([
                // 1. Información General
                Section::make()
                    ->columnSpan(3)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('production_number')
                            ->label('Número de Producción')
                            ->icon('heroicon-o-hashtag')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->copyable()
                            ->copyMessage('Copiado')
                            ->copyMessageDuration(1500),

                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (ProductionStatus $state): string => match ($state) {
                                ProductionStatus::DRAFT => 'gray',
                                ProductionStatus::SENT => 'info',
                                ProductionStatus::IN_PROGRESS => 'warning',
                                ProductionStatus::COMPLETED => 'success',
                                ProductionStatus::CANCELLED => 'danger',
                            })
                            ->formatStateUsing(fn (ProductionStatus $state): string => $state->getLabel()),

                        TextEntry::make('total_items')
                            ->label('Total Items')
                            ->icon('heroicon-o-cube')
                            ->suffix(' items')
                            ->default(0),

                        TextEntry::make('total_impressions')
                            ->label('Total Millares')
                            ->icon('heroicon-o-chart-bar')
                            ->suffix(' M')
                            ->numeric(
                                decimalPlaces: 2,
                                decimalSeparator: ',',
                                thousandsSeparator: '.',
                            )
                            ->default(0),
                    ]),

                // 2. Fechas Importantes
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('scheduled_date')
                            ->label('Fecha Programada')
                            ->date('d M, Y')
                            ->icon('heroicon-o-calendar-days')
                            ->placeholder('Sin fecha programada'),

                        TextEntry::make('started_at')
                            ->label('Fecha de Inicio')
                            ->dateTime('d M, Y H:i')
                            ->icon('heroicon-o-play')
                            ->placeholder('No iniciada')
                            ->visible(fn ($record) => $record->started_at !== null),

                        TextEntry::make('completed_at')
                            ->label('Fecha de Finalización')
                            ->dateTime('d M, Y H:i')
                            ->icon('heroicon-o-check-circle')
                            ->placeholder('No completada')
                            ->color('success')
                            ->visible(fn ($record) => $record->completed_at !== null),
                    ]),

                // 3. Proveedor
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('supplierCompany.name')
                            ->label('Empresa Proveedora')
                            ->icon('heroicon-o-building-library')
                            ->weight(FontWeight::SemiBold)
                            ->placeholder('No asignada')
                            ->visible(fn ($record) => $record->supplier_company_id !== null),

                        TextEntry::make('supplier.name')
                            ->label('Proveedor/Contacto')
                            ->icon('heroicon-o-user')
                            ->weight(FontWeight::SemiBold)
                            ->placeholder('No asignado')
                            ->visible(fn ($record) => $record->supplier_id !== null),

                        TextEntry::make('operator.name')
                            ->label('Operador Asignado')
                            ->icon('heroicon-o-user-circle')
                            ->weight(FontWeight::SemiBold)
                            ->placeholder('Sin asignar'),
                    ]),

                // 4. Notas
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notas Generales')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),

                        TextEntry::make('operator_notes')
                            ->label('Notas del Operador')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->placeholder('Sin notas del operador')
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->operator_notes)),
                    ]),

                // 5. Items (RelationManager - se renderiza automáticamente después)
            ]);
    }
}
