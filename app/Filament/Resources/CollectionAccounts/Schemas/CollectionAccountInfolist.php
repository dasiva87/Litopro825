<?php

namespace App\Filament\Resources\CollectionAccounts\Schemas;

use App\Enums\CollectionAccountStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class CollectionAccountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Sección Principal
                Section::make('Información General')
                    ->icon('heroicon-o-document-text')
                    ->columns(2)
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
                            ->formatStateUsing(fn (CollectionAccountStatus $state): string => $state->label()),

                        TextEntry::make('total_amount')
                            ->label('Monto Total')
                            ->icon('heroicon-o-currency-dollar')
                            ->money('COP')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->color('success'),
                    ]),

                // Información de Cliente y Empresa
                Grid::make(2)
                    ->schema([
                        Section::make('Empresa')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                TextEntry::make('company.name')
                                    ->label('Nombre de la Empresa')
                                    ->icon('heroicon-o-building-storefront'),
                            ]),

                        Section::make('Cliente')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                TextEntry::make('clientCompany.name')
                                    ->label('Nombre del Cliente')
                                    ->icon('heroicon-o-user'),
                            ]),
                    ]),

                // Fechas
                Section::make('Fechas Importantes')
                    ->icon('heroicon-o-calendar')
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
