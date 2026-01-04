<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2) // DOS COLUMNAS
            ->components([
                // 1. Información General
                Section::make()
                    ->columnSpan(2)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('document_number')
                            ->label('Número de Cotización')
                            ->icon('heroicon-o-hashtag')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->copyable()
                            ->copyMessage('Copiado')
                            ->copyMessageDuration(1500),

                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'sent' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'draft' => 'Borrador',
                                'sent' => 'Enviada',
                                'approved' => 'Aprobada',
                                'rejected' => 'Rechazada',
                                default => ucfirst($state),
                            }),

                        TextEntry::make('reference')
                            ->label('Referencia')
                            ->icon('heroicon-o-bookmark')
                            ->placeholder('Sin referencia'),

                        TextEntry::make('version')
                            ->label('Versión')
                            ->icon('heroicon-o-document-duplicate')
                            ->default('1'),
                    ]),

                // 2. Fechas Importantes
                Section::make()
                    ->columnSpan(1)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('date')
                            ->label('Fecha de Cotización')
                            ->date('d M, Y')
                            ->icon('heroicon-o-calendar-days'),

                        TextEntry::make('valid_until')
                            ->label('Válida Hasta')
                            ->date('d M, Y')
                            ->icon(function ($record) {
                                if (!$record->valid_until) return 'heroicon-o-clock';

                                $now = now();
                                $validUntil = \Carbon\Carbon::parse($record->valid_until);

                                if ($validUntil->isPast()) {
                                    return 'heroicon-o-x-circle';
                                } elseif ($validUntil->diffInDays($now) <= 5) {
                                    return 'heroicon-o-exclamation-triangle';
                                }
                                return 'heroicon-o-check-circle';
                            })
                            ->color(function ($record) {
                                if (!$record->valid_until) return 'gray';

                                $now = now();
                                $validUntil = \Carbon\Carbon::parse($record->valid_until);

                                if ($validUntil->isPast()) {
                                    return 'danger';
                                } elseif ($validUntil->diffInDays($now) <= 5) {
                                    return 'warning';
                                }
                                return 'success';
                            })
                            ->badge()
                            ->placeholder('Sin fecha de vencimiento'),

                        TextEntry::make('due_date')
                            ->label('Fecha de Vencimiento')
                            ->date('d M, Y')
                            ->icon('heroicon-o-calendar-days')
                            ->placeholder('Sin fecha de vencimiento'),
                    ]),

                // 3. Cliente
                Section::make()
                    ->columnSpan(1)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('contact.name')
                            ->label('Nombre del Cliente')
                            ->icon('heroicon-o-user')
                            ->weight(FontWeight::SemiBold),

                        TextEntry::make('contact.email')
                            ->label('Email del Cliente')
                            ->icon('heroicon-o-envelope')
                            ->copyable()
                            ->copyMessage('Email copiado')
                            ->placeholder('Sin email'),

                        TextEntry::make('contact.phone')
                            ->label('Teléfono')
                            ->icon('heroicon-o-phone')
                            ->url(fn ($state) => $state ? 'tel:' . $state : null)
                            ->placeholder('Sin teléfono'),

                        TextEntry::make('contact.city')
                            ->label('Ciudad')
                            ->icon('heroicon-o-map-pin')
                            ->placeholder('Sin ciudad'),
                    ]),

                // 4. Items (RelationManager - se renderiza automáticamente después)
                // 5. Resumen Financiero (Widget - se renderiza automáticamente después de Items)
            ]);
    }
}
