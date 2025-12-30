<?php

namespace App\Filament\Resources\CommercialRequests\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommercialRequestViewSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información de la Solicitud')
                ->schema([
                    Placeholder::make('relationship_type')
                        ->label('Tipo de Relación')
                        ->content(fn ($record) => match($record->relationship_type) {
                            'client' => '🤝 Cliente',
                            'supplier' => '📦 Proveedor',
                            default => 'Desconocido'
                        }),

                    Placeholder::make('status')
                        ->label('Estado')
                        ->content(fn ($record) => match($record->status) {
                            'pending' => '⏳ Pendiente',
                            'approved' => '✅ Aprobada',
                            'rejected' => '❌ Rechazada',
                            default => 'Desconocido'
                        }),

                    Placeholder::make('created_at')
                        ->label('Fecha de Solicitud')
                        ->content(fn ($record) => $record->created_at->format('d/m/Y H:i')),
                ])
                ->columns(3),

            Section::make('Empresa Solicitante')
                ->schema([
                    Placeholder::make('requester_company')
                        ->label('Empresa')
                        ->content(fn ($record) => $record->requesterCompany->name),

                    Placeholder::make('requester_email')
                        ->label('Email')
                        ->content(fn ($record) => $record->requesterCompany->email ?? 'No especificado'),

                    Placeholder::make('requester_phone')
                        ->label('Teléfono')
                        ->content(fn ($record) => $record->requesterCompany->phone ?? 'No especificado'),

                    Placeholder::make('requested_by')
                        ->label('Solicitado por')
                        ->content(fn ($record) => $record->requestedByUser->name ?? 'Usuario desconocido'),
                ])
                ->columns(2),

            Section::make('Mensaje de Solicitud')
                ->schema([
                    Textarea::make('message')
                        ->label('Mensaje')
                        ->disabled()
                        ->rows(4)
                        ->placeholder('Sin mensaje'),
                ]),

            Section::make('Respuesta')
                ->schema([
                    Placeholder::make('responded_at')
                        ->label('Fecha de Respuesta')
                        ->content(fn ($record) => $record->responded_at
                            ? $record->responded_at->format('d/m/Y H:i')
                            : 'Sin responder')
                        ->visible(fn ($record) => !$record->isPending()),

                    Placeholder::make('responded_by')
                        ->label('Respondido por')
                        ->content(fn ($record) => $record->respondedByUser->name ?? 'Usuario desconocido')
                        ->visible(fn ($record) => !$record->isPending()),

                    Textarea::make('response_message')
                        ->label('Mensaje de Respuesta')
                        ->disabled()
                        ->rows(4)
                        ->placeholder('Sin mensaje de respuesta')
                        ->visible(fn ($record) => !$record->isPending()),
                ])
                ->visible(fn ($record) => !$record->isPending())
                ->columns(2),
        ]);
    }
}
