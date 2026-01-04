<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\OrderStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class PurchaseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2) // UNA SOLA COLUMNA
            ->components([
                // 1. Información General
                Section::make()
                    ->columnSpan(2)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('Número de Orden')
                            ->icon('heroicon-o-hashtag')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->copyable()
                            ->copyMessage('Copiado')
                            ->copyMessageDuration(1500),

                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (OrderStatus $state): string => match ($state) {
                                OrderStatus::DRAFT => 'gray',
                                OrderStatus::SENT => 'info',
                                OrderStatus::IN_PROGRESS => 'warning',
                                OrderStatus::COMPLETED => 'success',
                                OrderStatus::CANCELLED => 'danger',
                            })
                            ->formatStateUsing(fn (OrderStatus $state): string => $state->getLabel()),

                        TextEntry::make('total_amount')
                            ->label('Monto Total')
                            ->icon('heroicon-o-currency-dollar')
                            ->money('COP')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->color('success'),

                        TextEntry::make('notes')
                            ->label('Notas')
                            ->icon('heroicon-o-document-text')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ]),

                // 2. Fechas Importantes
                Section::make()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('order_date')
                            ->label('Fecha de Orden')
                            ->date('d M, Y')
                            ->icon('heroicon-o-calendar-days'),

                        TextEntry::make('expected_delivery_date')
                            ->label('Fecha de Entrega Esperada')
                            ->date('d M, Y')
                            ->icon('heroicon-o-clock')
                            ->placeholder('Sin fecha esperada'),

                        TextEntry::make('actual_delivery_date')
                            ->label('Fecha de Entrega Real')
                            ->date('d M, Y')
                            ->icon('heroicon-o-check-circle')
                            ->placeholder('No recibida')
                            ->color('success'),
                    ]),

                // 3. Empresa Solicitante
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Nombre de la Empresa')
                            ->icon('heroicon-o-building-storefront')
                            ->weight(FontWeight::SemiBold),

                        TextEntry::make('supplierCompany.name')
                            ->label('Nombre del Proveedor')
                            ->icon('heroicon-o-building-library')
                            ->weight(FontWeight::SemiBold),
                    ]),

              

                // 5. Items (RelationManager - se renderiza automáticamente después)
            ]);
    }
}
