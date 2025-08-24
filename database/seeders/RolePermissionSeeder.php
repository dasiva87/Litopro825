<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos específicos para LitoPro
        $permissions = [
            // Gestión de empresas
            'view-companies',
            'create-companies',
            'edit-companies',
            'delete-companies',
            
            // Gestión de usuarios
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            
            // Gestión de contactos (clientes/proveedores)
            'view-contacts',
            'create-contacts',
            'edit-contacts',
            'delete-contacts',
            
            // Módulo de cotizaciones
            'view-quotes',
            'create-quotes',
            'edit-quotes',
            'delete-quotes',
            'approve-quotes',
            'send-quotes',
            
            // Órdenes de producción
            'view-production-orders',
            'create-production-orders',
            'edit-production-orders',
            'delete-production-orders',
            'assign-production-orders',
            
            // Pedidos de papel
            'view-paper-orders',
            'create-paper-orders',
            'edit-paper-orders',
            'delete-paper-orders',
            
            // Equipos y materiales
            'view-equipment',
            'create-equipment',
            'edit-equipment',
            'delete-equipment',
            
            // Productos y catálogos
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',
            
            // Configuraciones
            'view-settings',
            'edit-settings',
            
            // Red social
            'view-posts',
            'create-posts',
            'edit-posts',
            'delete-posts',
            'moderate-posts',
            
            // Reportes
            'view-reports',
            'export-reports',
            
            // Calculadora de cortes
            'use-cutting-calculator',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear roles específicos para el sector litográfico
        $roles = [
            'Super Admin' => $permissions, // Todos los permisos
            'Company Admin' => [
                'view-users', 'create-users', 'edit-users', 'delete-users',
                'view-contacts', 'create-contacts', 'edit-contacts', 'delete-contacts',
                'view-quotes', 'create-quotes', 'edit-quotes', 'delete-quotes', 'approve-quotes', 'send-quotes',
                'view-production-orders', 'create-production-orders', 'edit-production-orders', 'delete-production-orders', 'assign-production-orders',
                'view-paper-orders', 'create-paper-orders', 'edit-paper-orders', 'delete-paper-orders',
                'view-equipment', 'create-equipment', 'edit-equipment', 'delete-equipment',
                'view-products', 'create-products', 'edit-products', 'delete-products',
                'view-settings', 'edit-settings',
                'view-posts', 'create-posts', 'edit-posts', 'delete-posts', 'moderate-posts',
                'view-reports', 'export-reports',
                'use-cutting-calculator',
            ],
            'Manager' => [
                'view-users',
                'view-contacts', 'create-contacts', 'edit-contacts',
                'view-quotes', 'create-quotes', 'edit-quotes', 'approve-quotes', 'send-quotes',
                'view-production-orders', 'create-production-orders', 'edit-production-orders', 'assign-production-orders',
                'view-paper-orders', 'create-paper-orders', 'edit-paper-orders',
                'view-equipment', 'view-products',
                'view-posts', 'create-posts', 'edit-posts',
                'view-reports', 'export-reports',
                'use-cutting-calculator',
            ],
            'Salesperson' => [
                'view-contacts', 'create-contacts', 'edit-contacts',
                'view-quotes', 'create-quotes', 'edit-quotes', 'send-quotes',
                'view-production-orders', 'create-production-orders',
                'view-paper-orders', 'create-paper-orders',
                'view-equipment', 'view-products',
                'view-posts', 'create-posts',
                'use-cutting-calculator',
            ],
            'Operator' => [
                'view-production-orders', 'edit-production-orders',
                'view-paper-orders',
                'view-equipment', 'view-products',
                'view-posts', 'create-posts',
                'use-cutting-calculator',
            ],
            'Customer' => [
                'view-quotes',
                'view-posts', 'create-posts',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}