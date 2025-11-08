<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class DocumentInfolist
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
                                TextEntry::make('contact.name')
                                    ->label('Nombre del Cliente')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('contact.email')
                                    ->label('Email del Cliente')
                                    ->icon('heroicon-o-envelope')
                                    ->placeholder('Sin email'),
                            ]),
                    ]),

                // Fechas
                Section::make('Fechas Importantes')
                    ->icon('heroicon-o-calendar')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('date')
                            ->label('Fecha de Cotización')
                            ->date('d M, Y')
                            ->icon('heroicon-o-calendar-days'),

                        TextEntry::make('valid_until')
                            ->label('Válida Hasta')
                            ->date('d M, Y')
                            ->icon('heroicon-o-clock')
                            ->placeholder('Sin fecha de vencimiento'),

                        TextEntry::make('due_date')
                            ->label('Fecha de Vencimiento')
                            ->date('d M, Y')
                            ->icon('heroicon-o-calendar-days')
                            ->placeholder('Sin fecha de vencimiento'),
                    ]),

                // Notas
                Section::make('Notas')
                    ->icon('heroicon-o-document-text')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notas para el Cliente')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),

                        TextEntry::make('internal_notes')
                            ->label('Notas Internas')
                            ->icon('heroicon-o-lock-closed')
                            ->placeholder('Sin notas internas')
                            ->columnSpanFull(),
                    ]),

                // Información de Auditoría
                Section::make('Información del Sistema')
                    ->icon('heroicon-o-information-circle')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Creado por')
                            ->icon('heroicon-o-user')
                            ->default('N/A'),

                        TextEntry::make('documentType.name')
                            ->label('Tipo de Documento')
                            ->icon('heroicon-o-document')
                            ->placeholder('Sin tipo'),

                        TextEntry::make('created_at')
                            ->label('Fecha de Creación')
                            ->dateTime('d M, Y H:i')
                            ->icon('heroicon-o-clock'),

                        TextEntry::make('updated_at')
                            ->label('Última Actualización')
                            ->dateTime('d M, Y H:i')
                            ->icon('heroicon-o-arrow-path'),
                    ]),
            ]);
    }
}
