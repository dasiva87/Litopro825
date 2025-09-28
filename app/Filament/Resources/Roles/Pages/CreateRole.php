<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asegurar que el guard_name sea 'web'
        $data['guard_name'] = 'web';

        return $data;
    }

    protected function afterCreate(): void
    {
        $role = $this->record;
        $formData = $this->form->getState();

        // Recopilar todos los permisos seleccionados de todas las categorías
        $allPermissions = [];

        $permissionCategories = [
            'user_permissions',
            'contact_permissions',
            'quote_permissions',
            'document_permissions',
            'production_permissions',
            'paper_permissions',
            'product_permissions',
            'equipment_permissions',
            'system_permissions',
            'report_permissions',
            'social_permissions'
        ];

        foreach ($permissionCategories as $category) {
            if (isset($formData[$category]) && is_array($formData[$category])) {
                $allPermissions = array_merge($allPermissions, $formData[$category]);
            }
        }

        // Sincronizar permisos con el rol
        if (!empty($allPermissions)) {
            $role->syncPermissions($allPermissions);
        }
    }
}