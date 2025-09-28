<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar el rol actual del usuario
        $user = $this->record;
        $data['role'] = $user->roles->first()?->name;

        return $data;
    }

    protected function afterSave(): void
    {
        $user = $this->record;
        $roleData = $this->form->getState();

        // Actualizar el rol si existe y tiene permisos
        if (isset($roleData['role']) && auth()->user()->can('assignRoles', auth()->user())) {
            $user->syncRoles([$roleData['role']]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
