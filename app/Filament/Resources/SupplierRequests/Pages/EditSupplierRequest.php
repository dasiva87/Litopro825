<?php

namespace App\Filament\Resources\SupplierRequests\Pages;

use App\Filament\Resources\SupplierRequests\SupplierRequestResource;
use App\Models\SupplierRelationship;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSupplierRequest extends EditRecord
{
    protected static string $resource = SupplierRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(function () {
                    // Solo permitir eliminar para litografías (que enviaron la solicitud)
                    $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                    $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;
                    return $company && $company->isLitografia() && $this->record->isPending();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Solo permitir modificar si es papelería respondiendo
        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

        if (!$company || !$company->isPapeleria()) {
            // Si no es papelería, no permitir cambios
            return $this->record->only($this->record->getFillable());
        }

        // Si es papelería, permitir responder
        if (isset($data['status']) && $data['status'] !== 'pending') {
            $data['responded_at'] = now();
            $data['responded_by_user_id'] = auth()->id();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

        // Solo procesar para papelerías
        if (!$company || !$company->isPapeleria()) {
            return;
        }

        // Buscar la relación existente
        $existingRelation = SupplierRelationship::where('client_company_id', $this->record->requester_company_id)
            ->where('supplier_company_id', $this->record->supplier_company_id)
            ->first();

        if ($this->record->isApproved()) {
            // Si fue aprobada, crear o reactivar la relación de proveedor
            if (!$existingRelation) {
                SupplierRelationship::create([
                    'client_company_id' => $this->record->requester_company_id,
                    'supplier_company_id' => $this->record->supplier_company_id,
                    'approved_by_user_id' => auth()->id(),
                    'approved_at' => now(),
                    'is_active' => true,
                ]);

                Notification::make()
                    ->title('Solicitud Aprobada')
                    ->body('Se ha creado la relación de proveedor con ' . $this->record->requesterCompany->name)
                    ->success()
                    ->send();
            } else {
                // Si existe pero está inactiva, reactivarla
                if (!$existingRelation->is_active) {
                    $existingRelation->reactivate('Solicitud aprobada nuevamente');

                    Notification::make()
                        ->title('Relación Reactivada')
                        ->body('Se ha reactivado la relación de proveedor con ' . $this->record->requesterCompany->name)
                        ->success()
                        ->send();
                }
            }
        } elseif ($this->record->isRejected()) {
            // Si fue rechazada, desactivar la relación existente
            if ($existingRelation && $existingRelation->is_active) {
                $existingRelation->deactivate('Solicitud rechazada');

                Notification::make()
                    ->title('Solicitud Rechazada')
                    ->body('Se ha desactivado la relación de proveedor con ' . $this->record->requesterCompany->name)
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Solicitud Rechazada')
                    ->body('Se ha rechazado la solicitud de ' . $this->record->requesterCompany->name)
                    ->warning()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        // Redirigir de vuelta al listado
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

        if ($company && $company->isPapeleria()) {
            return 'Responder Solicitud de Proveedor';
        }

        return 'Ver Solicitud de Proveedor';
    }
}
