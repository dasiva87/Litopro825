<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\OrderStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class PurchaseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Sección Principal
                Section::make('Información General')
                    ->icon('heroicon-o-shopping-cart')
                    ->columns(2)
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
                                OrderStatus::CONFIRMED => 'warning',
                                OrderStatus::RECEIVED => 'success',
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

                // Información de Empresa y Proveedor
                Grid::make(2)
                    ->schema([
                        Section::make('Empresa Solicitante')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                TextEntry::make('company.name')
                                    ->label('Nombre de la Empresa')
                                    ->icon('heroicon-o-building-storefront'),
                            ]),

                        Section::make('Proveedor')
                            ->icon('heroicon-o-truck')
                            ->schema([
                                TextEntry::make('supplierCompany.name')
                                    ->label('Nombre del Proveedor')
                                    ->icon('heroicon-o-building-library'),
                            ]),
                    ]),

                // Fechas
                Section::make('Fechas Importantes')
                    ->icon('heroicon-o-calendar')
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

                // Información de Auditoría
                Section::make('Información del Sistema')
                    ->icon('heroicon-o-information-circle')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('createdBy.name')
                            ->label('Creado por')
                            ->icon('heroicon-o-user')
                            ->default('N/A'),

                        TextEntry::make('approvedBy.name')
                            ->label('Aprobado por')
                            ->icon('heroicon-o-shield-check')
                            ->placeholder('Pendiente de aprobación'),

                        TextEntry::make('created_at')
                            ->label('Fecha de Creación')
                            ->dateTime('d M, Y H:i')
                            ->icon('heroicon-o-clock'),

                        TextEntry::make('approved_at')
                            ->label('Fecha de Aprobación')
                            ->dateTime('d M, Y H:i')
                            ->icon('heroicon-o-check-badge')
                            ->placeholder('No aprobada'),
                    ]),
            ]);
    }
}
