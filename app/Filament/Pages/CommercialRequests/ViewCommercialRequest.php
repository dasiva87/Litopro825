<?php

namespace App\Filament\Pages\CommercialRequests;

use App\Services\CommercialRequestService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class ViewCommercialRequest extends EditRecord
{
    protected static string $resource = \App\Filament\Resources\CommercialRequestResource::class;

    protected static ?string $title = 'Ver Solicitud Comercial';

    // Deshabilitar la edición del formulario
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // No permitir guardar cambios directos al formulario
        return $data;
    }

    // Ocultar el botón de guardar por defecto
    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getFormActions(): array
    {
        // No mostrar acciones del formulario (guardar, cancelar, etc.)
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Aprobar Solicitud')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->modalHeading('Aprobar Solicitud Comercial')
                ->modalDescription('Al aprobar esta solicitud, se creará automáticamente la relación comercial entre ambas empresas.')
                ->form([
                    Textarea::make('response_message')
                        ->label('Mensaje de bienvenida (opcional)')
                        ->placeholder('Ej: ¡Bienvenido! Esperamos una excelente relación comercial...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    // Validación de seguridad: solo la empresa destino puede aprobar
                    if ($this->record->target_company_id !== auth()->user()->company_id) {
                        Notification::make()
                            ->danger()
                            ->title('Acción no permitida')
                            ->body('Solo la empresa que recibe la solicitud puede aprobarla')
                            ->send();

                        return;
                    }

                    if (! $this->record->isPending()) {
                        Notification::make()
                            ->warning()
                            ->title('Solicitud ya procesada')
                            ->body('Esta solicitud ya fue aprobada o rechazada')
                            ->send();

                        return;
                    }

                    $service = app(CommercialRequestService::class);

                    try {
                        $service->approveRequest(
                            $this->record,
                            auth()->user(),
                            $data['response_message'] ?? null
                        );

                        Notification::make()
                            ->success()
                            ->title('Solicitud aprobada exitosamente')
                            ->body('Se ha creado la relación comercial y notificado a la empresa solicitante.')
                            ->send();

                        return redirect()->route('filament.admin.resources.commercial-requests.index');

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error al aprobar solicitud')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalSubmitActionLabel('Aprobar')
                ->visible(function () {
                    if (! auth()->check() || ! auth()->user()->company_id) {
                        return false;
                    }

                    return $this->record->isPending() &&
                           $this->record->target_company_id === auth()->user()->company_id;
                }),

            Action::make('reject')
                ->label('Rechazar Solicitud')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->modalHeading('Rechazar Solicitud Comercial')
                ->modalDescription('La empresa solicitante será notificada del rechazo.')
                ->form([
                    Textarea::make('response_message')
                        ->label('Motivo del rechazo')
                        ->placeholder('Explica por qué rechazas esta solicitud...')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data) {
                    // Validación de seguridad: solo la empresa destino puede rechazar
                    if ($this->record->target_company_id !== auth()->user()->company_id) {
                        Notification::make()
                            ->danger()
                            ->title('Acción no permitida')
                            ->body('Solo la empresa que recibe la solicitud puede rechazarla')
                            ->send();

                        return;
                    }

                    if (! $this->record->isPending()) {
                        Notification::make()
                            ->warning()
                            ->title('Solicitud ya procesada')
                            ->body('Esta solicitud ya fue aprobada o rechazada')
                            ->send();

                        return;
                    }

                    $service = app(CommercialRequestService::class);

                    try {
                        $service->rejectRequest(
                            $this->record,
                            auth()->user(),
                            $data['response_message']
                        );

                        Notification::make()
                            ->warning()
                            ->title('Solicitud rechazada')
                            ->body('La empresa solicitante ha sido notificada.')
                            ->send();

                        return redirect()->route('filament.admin.resources.commercial-requests.index');

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error al rechazar solicitud')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalSubmitActionLabel('Rechazar')
                ->visible(function () {
                    if (! auth()->check() || ! auth()->user()->company_id) {
                        return false;
                    }

                    return $this->record->isPending() &&
                           $this->record->target_company_id === auth()->user()->company_id;
                }),
        ];
    }
}
