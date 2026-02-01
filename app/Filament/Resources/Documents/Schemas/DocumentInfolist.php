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
                    ->columns(5)
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

                        TextEntry::make('project.name')
                            ->label('Proyecto')
                            ->icon('heroicon-o-folder')
                            ->badge()
                            ->color('primary')
                            ->url(fn ($record) => $record->project_id
                                ? route('filament.admin.resources.projects.view', $record->project_id)
                                : null)
                            ->visible(fn ($record) => $record->project_id !== null),

                        TextEntry::make('version')
                            ->label('Versión')
                            ->icon('heroicon-o-document-duplicate')
                            ->default('1'),

                        TextEntry::make('date')
                            ->label('Fecha de Cotización')
                            ->date('d M, Y')
                            ->icon('heroicon-o-calendar-days'),
                    ]),


                // 2.5. Resumen Financiero (debajo de Fechas)
                Section::make()
                    ->columnSpan(1)
                    ->schema([
                        ViewEntry::make('financial_summary')
                            ->label('')
                            ->view('filament.resources.documents.infolist.financial-summary'),
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

                        TextEntry::make('contact.city.name')
                            ->label('Ciudad')
                            ->icon('heroicon-o-map-pin')
                            ->placeholder('Sin ciudad'),
                    ]),

                // 4. Items (RelationManager - se renderiza automáticamente después)
                // 5. Resumen Financiero (Widget - se renderiza automáticamente después de Items)
            ]);
    }
}
