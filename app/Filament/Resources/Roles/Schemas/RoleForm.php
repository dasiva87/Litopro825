<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Rol')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del Rol')
                            ->required()
                            ->unique(table: 'roles', column: 'name', ignorable: fn($record) => $record)
                            ->maxLength(255)
                            ->placeholder('Ej: Vendedor Senior, Diseñador, etc.'),

                        TextInput::make('guard_name')
                            ->label('Guard')
                            ->default('web')
                            ->hidden(),
                    ])->columns(1),

                Section::make('Gestión de Usuarios')
                    ->schema([
                        CheckboxList::make('user_permissions')
                            ->label('Permisos de Usuarios')
                            ->options(self::getPermissionsByCategory('users'))
                            ->columns(2)
                            ->visible(fn() => auth()->user()->can('managePermissions', auth()->user())),
                    ])
                    ->collapsible(),

                Section::make('Gestión de Contactos')
                    ->schema([
                        CheckboxList::make('contact_permissions')
                            ->label('Permisos de Contactos (Clientes/Proveedores)')
                            ->options(self::getPermissionsByCategory('contacts'))
                            ->columns(2),
                    ])
                    ->collapsible(),

                Section::make('Cotizaciones y Documentos')
                    ->schema([
                        CheckboxList::make('quote_permissions')
                            ->label('Permisos de Cotizaciones')
                            ->options(self::getPermissionsByCategory('quotes'))
                            ->columns(2),

                        CheckboxList::make('document_permissions')
                            ->label('Permisos de Documentos')
                            ->options(self::getPermissionsByCategory('documents'))
                            ->columns(2),
                    ])
                    ->collapsible(),

                Section::make('Órdenes de Producción')
                    ->schema([
                        CheckboxList::make('production_permissions')
                            ->label('Permisos de Órdenes de Producción')
                            ->options(self::getPermissionsByCategory('production-orders'))
                            ->columns(2),

                        CheckboxList::make('paper_permissions')
                            ->label('Permisos de Órdenes de Papel')
                            ->options(self::getPermissionsByCategory('paper-orders'))
                            ->columns(2),
                    ])
                    ->collapsible(),

                Section::make('Productos y Equipos')
                    ->schema([
                        CheckboxList::make('product_permissions')
                            ->label('Permisos de Productos')
                            ->options(self::getPermissionsByCategory('products'))
                            ->columns(2),

                        CheckboxList::make('equipment_permissions')
                            ->label('Permisos de Equipos')
                            ->options(self::getPermissionsByCategory('equipment'))
                            ->columns(2),
                    ])
                    ->collapsible(),

                Section::make('Sistema y Configuraciones')
                    ->schema([
                        CheckboxList::make('system_permissions')
                            ->label('Permisos del Sistema')
                            ->options(self::getPermissionsByCategory('system'))
                            ->columns(2),

                        CheckboxList::make('report_permissions')
                            ->label('Permisos de Reportes')
                            ->options(self::getPermissionsByCategory('reports'))
                            ->columns(2),
                    ])
                    ->collapsible(),

                Section::make('Red Social')
                    ->schema([
                        CheckboxList::make('social_permissions')
                            ->label('Permisos de Red Social')
                            ->options(self::getPermissionsByCategory('posts'))
                            ->columns(2),
                    ])
                    ->collapsible(),
            ]);
    }

    /**
     * Obtener permisos por categoría
     */
    private static function getPermissionsByCategory(string $category): array
    {
        $categoryMap = [
            'users' => ['view-users', 'create-users', 'edit-users', 'delete-users'],
            'contacts' => ['view-contacts', 'create-contacts', 'edit-contacts', 'delete-contacts'],
            'quotes' => ['view-quotes', 'create-quotes', 'edit-quotes', 'delete-quotes', 'approve-quotes', 'send-quotes'],
            'documents' => ['view-documents', 'create-documents', 'edit-documents', 'delete-documents', 'approve-documents'],
            'production-orders' => ['view-production-orders', 'create-production-orders', 'edit-production-orders', 'delete-production-orders', 'assign-production-orders'],
            'paper-orders' => ['view-paper-orders', 'create-paper-orders', 'edit-paper-orders', 'delete-paper-orders'],
            'products' => ['view-products', 'create-products', 'edit-products', 'delete-products'],
            'equipment' => ['view-equipment', 'create-equipment', 'edit-equipment', 'delete-equipment'],
            'system' => ['view-settings', 'edit-settings', 'manage-company-settings', 'manage-paper-catalog', 'manage-printing-machines', 'access-admin-panel', 'manage-roles', 'view-system-logs'],
            'reports' => ['view-reports', 'export-reports'],
            'posts' => ['view-posts', 'create-posts', 'edit-posts', 'delete-posts', 'moderate-posts'],
        ];

        $permissions = $categoryMap[$category] ?? [];

        // Filtrar permisos que existen en la base de datos
        $existingPermissions = Permission::whereIn('name', $permissions)->pluck('name', 'name')->toArray();

        // Si es Company Admin, no puede gestionar ciertos permisos críticos
        if (auth()->user()->hasRole('Company Admin') && !auth()->user()->hasRole('Super Admin')) {
            $restrictedPermissions = ['manage-roles', 'view-system-logs'];
            $existingPermissions = array_diff_key($existingPermissions, array_flip($restrictedPermissions));
        }

        return $existingPermissions;
    }
}