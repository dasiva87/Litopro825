<?php

namespace App\Filament\Resources\CollectionAccounts\Schemas;

use App\Enums\CollectionAccountStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class CollectionAccountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2) // UNA SOLA COLUMNA
            ->components([
                // 1. Información General
                Section::make()
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('account_number')
                            ->label('Número de Cuenta')
                            ->icon('heroicon-o-hashtag')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->copyable()
                            ->copyMessage('Copiado')
                            ->copyMessageDuration(1500),

                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (CollectionAccountStatus $state): string => match ($state) {
                                CollectionAccountStatus::DRAFT => 'gray',
                                CollectionAccountStatus::SENT => 'info',
                                CollectionAccountStatus::APPROVED => 'success',
                                CollectionAccountStatus::PAID => 'success',
                                CollectionAccountStatus::CANCELLED => 'danger',
                            })
                            ->formatStateUsing(fn (CollectionAccountStatus $state): string => $state->getLabel()),

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
                        TextEntry::make('issue_date')
                            ->label('Fecha de Emisión')
                            ->date('d M, Y')
                            ->icon('heroicon-o-calendar-days'),

                        TextEntry::make('due_date')
                            ->label('Fecha de Vencimiento')
                            ->date('d M, Y')
                            ->icon('heroicon-o-clock')
                            ->placeholder('Sin vencimiento'),

                        TextEntry::make('paid_date')
                            ->label('Fecha de Pago')
                            ->date('d M, Y')
                            ->icon('heroicon-o-check-circle')
                            ->placeholder('No pagada')
                            ->color('success'),
                    ]),

                // 3. Empresa Emisora
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Nombre de la Empresa')
                            ->icon('heroicon-o-building-storefront')
                            ->weight(FontWeight::SemiBold),

                        TextEntry::make('clientCompany.name')
                            ->label('Empresa Cliente')
                            ->icon('heroicon-o-building-library')
                            ->weight(FontWeight::SemiBold)
                            ->placeholder('No asignada')
                            ->visible(fn ($record) => $record->client_company_id !== null),

                        TextEntry::make('contact.name')
                            ->label('Cliente/Contacto')
                            ->icon('heroicon-o-user')
                            ->weight(FontWeight::SemiBold)
                            ->placeholder('No asignado')
                            ->visible(fn ($record) => $record->contact_id !== null),
                    ]),

              

                // 5. Items (RelationManager - se renderiza automáticamente después)
            ]);
    }
}
