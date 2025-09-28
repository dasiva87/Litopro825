<?php

namespace App\Filament\Resources\SupplierRequests\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SupplierRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Solicitud')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('requester_company')
                                    ->label('Empresa Solicitante')
                                    ->content(fn ($record) => $record?->requesterCompany?->name ?? 'N/A'),

                                Placeholder::make('supplier_company')
                                    ->label('Papelería Proveedora')
                                    ->content(fn ($record) => $record?->supplierCompany?->name ?? 'N/A'),

                                Placeholder::make('requested_by')
                                    ->label('Solicitado por')
                                    ->content(fn ($record) => $record?->requestedByUser?->name ?? 'N/A'),

                                Placeholder::make('created_at')
                                    ->label('Fecha de Solicitud')
                                    ->content(fn ($record) => $record?->created_at?->format('d/m/Y H:i') ?? 'N/A'),
                            ]),

                        Textarea::make('message')
                            ->label('Mensaje de la Solicitud')
                            ->rows(3)
                            ->disabled()
                            ->placeholder('Sin mensaje'),
                    ]),

                Section::make('Respuesta')
                    ->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'approved' => 'Aprobar',
                                'rejected' => 'Rechazar',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Limpiar mensaje de respuesta si cambia de aprobado/rechazado a pendiente
                                if ($state === 'pending') {
                                    $set('response_message', null);
                                }
                            }),

                        Textarea::make('response_message')
                            ->label(fn (Get $get) => $get('status') === 'approved'
                                ? 'Mensaje de Aprobación (Opcional)'
                                : 'Mensaje de Rechazo (Opcional)')
                            ->rows(3)
                            ->visible(fn (Get $get) => in_array($get('status'), ['approved', 'rejected']))
                            ->placeholder(fn (Get $get) => $get('status') === 'approved'
                                ? 'Bienvenidos como proveedor autorizado...'
                                : 'Motivo del rechazo...'),

                        Grid::make(2)
                            ->schema([
                                Placeholder::make('responded_by')
                                    ->label('Respondido por')
                                    ->content(fn ($record) => $record?->respondedByUser?->name ?? 'Pendiente')
                                    ->visible(fn ($record) => $record?->responded_at),

                                Placeholder::make('responded_at')
                                    ->label('Fecha de Respuesta')
                                    ->content(fn ($record) => $record?->responded_at?->format('d/m/Y H:i') ?? 'Pendiente')
                                    ->visible(fn ($record) => $record?->responded_at),
                            ]),
                    ])
                    ->visible(function () {
                        // Solo mostrar sección de respuesta para papelerías
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;
                        return $company && $company->isPapeleria();
                    }),

                Section::make('Información de Estado')
                    ->schema([
                        Placeholder::make('status_info')
                            ->label('Estado Actual')
                            ->content(function ($record) {
                                if (!$record) return 'N/A';

                                $badge = match($record->status) {
                                    'pending' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">⏳ Pendiente</span>',
                                    'approved' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✅ Aprobada</span>',
                                    'rejected' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">❌ Rechazada</span>',
                                    default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">❓ Desconocido</span>',
                                };

                                return new HtmlString($badge);
                            }),

                        Placeholder::make('current_response')
                            ->label('Respuesta Actual')
                            ->content(fn ($record) => $record?->response_message ?? 'Sin respuesta')
                            ->visible(fn ($record) => $record?->response_message),
                    ])
                    ->visible(function () {
                        // Solo mostrar para litografías (que ven el estado de sus solicitudes)
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;
                        return $company && $company->isLitografia();
                    }),

                // Campos ocultos para gestión del formulario
                Hidden::make('responded_by_user_id')
                    ->default(auth()->id()),
            ]);
    }
}
