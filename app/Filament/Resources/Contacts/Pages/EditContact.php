<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Si es contacto Grafired, mostrar información y botón de sincronizar
        if (!$this->record->is_local) {
            $actions[] = Action::make('grafired_info')
                ->label('Contacto Grafired')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);

            $actions[] = Action::make('sync_from_grafired')
                ->label('Sincronizar desde Grafired')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(function () {
                    if (method_exists($this->record, 'syncFromLinkedCompany')) {
                        $this->record->syncFromLinkedCompany();

                        Notification::make()
                            ->title('Datos sincronizados correctamente')
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'name',
                            'email',
                            'phone',
                            'address',
                        ]);
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Sincronizar datos')
                ->modalDescription('Esto actualizará los datos de este contacto con la información de la empresa Grafired enlazada. ¿Deseas continuar?')
                ->modalSubmitActionLabel('Sí, sincronizar');
        }

        // Acciones estándar solo para contactos locales
        if ($this->record->is_local) {
            $actions[] = DeleteAction::make();
            $actions[] = ForceDeleteAction::make();
            $actions[] = RestoreAction::make();
        }

        return $actions;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Si es contacto Grafired, agregar un indicador
        if (!$this->record->is_local) {
            $data['_is_grafired'] = true;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Prevenir guardado de contactos Grafired
        if (!$this->record->is_local) {
            Notification::make()
                ->title('No se puede editar')
                ->body('Los contactos de Grafired no pueden ser editados. Usa el botón "Sincronizar desde Grafired" para actualizar los datos.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Asegurar valores por defecto para campos numéricos obligatorios
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['payment_terms'] = $data['payment_terms'] ?? 0;
        $data['discount_percentage'] = $data['discount_percentage'] ?? 0;

        return $data;
    }
}
