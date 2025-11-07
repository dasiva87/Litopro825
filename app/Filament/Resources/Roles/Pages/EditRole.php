<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $role = $this->record;
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        // Distribuir permisos por categorías para los checkboxes
        $permissionCategories = [
            'user_permissions' => ['view-users', 'create-users', 'edit-users', 'delete-users'],
            'contact_permissions' => ['view-contacts', 'create-contacts', 'edit-contacts', 'delete-contacts'],
            'quote_permissions' => ['view-quotes', 'create-quotes', 'edit-quotes', 'delete-quotes', 'approve-quotes', 'send-quotes'],
            'document_permissions' => ['view-documents', 'create-documents', 'edit-documents', 'delete-documents', 'approve-documents'],
            'production_permissions' => ['view-production-orders', 'create-production-orders', 'edit-production-orders', 'delete-production-orders', 'assign-production-orders'],
            'paper_permissions' => ['view-paper-orders', 'create-paper-orders', 'edit-paper-orders', 'delete-paper-orders'],
            'product_permissions' => ['view-products', 'create-products', 'edit-products', 'delete-products'],
            'equipment_permissions' => ['view-equipment', 'create-equipment', 'edit-equipment', 'delete-equipment'],
            'company_permissions' => ['view-companies', 'create-companies', 'edit-companies', 'delete-companies'],
            'inventory_permissions' => ['manage-inventory', 'manage-paper-catalog', 'manage-printing-machines'],
            'system_permissions' => ['view-settings', 'edit-settings', 'manage-company-settings', 'access-admin-panel', 'manage-roles', 'view-system-logs'],
            'report_permissions' => ['view-reports', 'export-reports'],
            'social_permissions' => ['view-posts', 'create-posts', 'edit-posts', 'delete-posts', 'moderate-posts'],
        ];

        foreach ($permissionCategories as $category => $permissions) {
            // array_intersect devuelve valores con índices preservados, pero CheckboxList necesita valores planos
            $data[$category] = array_values(array_intersect($rolePermissions, $permissions));
        }

        return $data;
    }

    protected function afterSave(): void
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
            'company_permissions',
            'inventory_permissions',
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
        $role->syncPermissions($allPermissions);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn() => auth()->user()->can('delete', $this->record)),
        ];
    }
}