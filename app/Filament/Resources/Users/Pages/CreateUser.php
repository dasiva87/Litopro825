<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asignar company_id del usuario autenticado (excepto Super Admin que puede manejar múltiples empresas)
        if (!auth()->user()->hasRole('Super Admin')) {
            $data['company_id'] = auth()->user()->company_id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $roleData = $this->form->getState();

        // Asignar el rol seleccionado si existe
        if (isset($roleData['role']) && auth()->user()->can('assignRoles', auth()->user())) {
            $user->assignRole($roleData['role']);
        }
    }
}
