<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Company;
use App\Models\User;
use App\Models\Contact;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\DocumentType;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Creando datos de prueba para GrafiRed...');

        // 1. Crear roles básicos del sistema
        $this->createRoles();

        // 2. Crear empresa de prueba
        $company = $this->createTestCompany();

        // 3. Crear usuarios de prueba
        $this->createTestUsers($company);

        // 4. Crear datos base para la empresa
        $this->createCompanyBaseData($company);

        $this->command->info('✅ Datos de prueba creados exitosamente!');
        $this->command->info('');
        $this->command->info('📋 DATOS DE ACCESO:');
        $this->command->info('🏢 Empresa: ' . $company->name);
        $this->command->info('👤 Admin: admin@grafired.test | password: password');
        $this->command->info('👤 Manager: manager@grafired.test | password: password');
        $this->command->info('👤 Employee: employee@grafired.test | password: password');
        $this->command->info('');
        $this->command->info('🌐 URL: /admin');
    }

    private function createRoles(): void
    {
        $this->command->info('📝 Creando roles y permisos...');

        // Crear permisos básicos
        $permissions = [
            // Gestión de usuarios
            'view-users',
            'create-users', 
            'edit-users',
            'delete-users',

            // Gestión de documentos/cotizaciones
            'view-documents',
            'create-documents',
            'edit-documents', 
            'delete-documents',
            'approve-documents',

            // Gestión de productos e inventario
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',
            'manage-inventory',

            // Gestión de clientes
            'view-contacts',
            'create-contacts',
            'edit-contacts',
            'delete-contacts',

            // Configuración del sistema
            'manage-company-settings',
            'view-reports',
            'manage-paper-catalog',
            'manage-printing-machines',

            // Administración avanzada
            'access-admin-panel',
            'manage-roles',
            'view-system-logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear roles
        $roles = [
            'Super Admin' => $permissions, // Acceso completo
            'Company Admin' => [
                'view-users', 'create-users', 'edit-users',
                'view-documents', 'create-documents', 'edit-documents', 'approve-documents',
                'view-products', 'create-products', 'edit-products', 'manage-inventory',
                'view-contacts', 'create-contacts', 'edit-contacts', 'delete-contacts',
                'manage-company-settings', 'view-reports', 
                'manage-paper-catalog', 'manage-printing-machines',
                'access-admin-panel'
            ],
            'Manager' => [
                'view-users',
                'view-documents', 'create-documents', 'edit-documents', 'approve-documents',
                'view-products', 'create-products', 'edit-products',
                'view-contacts', 'create-contacts', 'edit-contacts',
                'view-reports',
                'access-admin-panel'
            ],
            'Employee' => [
                'view-documents', 'create-documents', 'edit-documents',
                'view-products',
                'view-contacts', 'create-contacts', 'edit-contacts',
                'access-admin-panel'
            ],
            'Client' => [
                'view-documents' // Solo ver sus propias cotizaciones
            ]
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
            $this->command->info("   ✓ Rol creado: {$roleName}");
        }
    }

    private function createTestCompany(): Company
    {
        $this->command->info('🏢 Creando empresa de prueba...');

        $company = Company::firstOrCreate(
            ['slug' => 'grafired-demo'],
            [
                'name' => 'GrafiRed Demo',
                'email' => 'info@grafired-demo.com',
                'phone' => '+57 300 123 4567',
                'tax_id' => '900123456-7',
                'address' => 'Carrera 15 #93-47, Oficina 501, Bogotá D.C.',
                'subscription_plan' => 'premium',
                'subscription_expires_at' => now()->addYear(),
                'max_users' => 50,
                'is_active' => true
            ]
        );

        $this->command->info("   ✓ Empresa creada: {$company->name}");
        return $company;
    }

    private function createTestUsers(Company $company): void
    {
        $this->command->info('👥 Creando usuarios de prueba...');

        $users = [
            [
                'name' => 'Administrador Sistema',
                'email' => 'admin@grafired.test',
                'role' => 'Company Admin',
                'position' => 'Gerente General'
            ],
            [
                'name' => 'María Rodríguez',
                'email' => 'manager@grafired.test', 
                'role' => 'Manager',
                'position' => 'Jefe de Ventas'
            ],
            [
                'name' => 'Carlos López',
                'email' => 'employee@grafired.test',
                'role' => 'Employee', 
                'position' => 'Diseñador Gráfico'
            ]
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'company_id' => $company->id,
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'position' => $userData['position'],
                    'phone' => '+57 300 ' . rand(1000000, 9999999),
                    'is_active' => true,
                    'email_verified_at' => now()
                ]
            );

            $user->assignRole($userData['role']);
            $this->command->info("   ✓ Usuario creado: {$userData['name']} ({$userData['role']})");
        }
    }

    private function createCompanyBaseData(Company $company): void
    {
        $this->command->info('📦 Creando datos base de la empresa...');

        // Crear tipos de documento
        $this->createDocumentTypes();

        // Crear algunos clientes de prueba
        $this->createTestContacts($company);

        // Crear catálogo de papeles
        $this->createPaperCatalog($company);

        // Crear máquinas de impresión
        $this->createPrintingMachines($company);

        // Crear algunos productos de inventario
        $this->createTestProducts($company);
    }

    private function createDocumentTypes(): void
    {
        $documentTypes = [
            [
                'name' => 'Cotización',
                'code' => 'QUOTE',
                'description' => 'Documento de cotización para clientes'
            ],
            [
                'name' => 'Orden de Producción', 
                'code' => 'ORDER',
                'description' => 'Orden de trabajo para producción'
            ],
            [
                'name' => 'Factura',
                'code' => 'INVOICE', 
                'description' => 'Factura de venta'
            ],
            [
                'name' => 'Nota de Crédito',
                'code' => 'CREDIT',
                'description' => 'Nota de crédito'
            ]
        ];

        foreach ($documentTypes as $type) {
            DocumentType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        $this->command->info('   ✓ Tipos de documento creados');
    }

    private function createTestContacts(Company $company): void
    {
        $contacts = [
            [
                'name' => 'Grupo Empresarial ABC',
                'contact_person' => 'Ana García',
                'email' => 'ana.garcia@grupoabc.com',
                'phone' => '+57 1 234 5678',
                'type' => 'customer',
                'tax_id' => '830123456-2'
            ],
            [
                'name' => 'Fundación Educativa XYZ',
                'contact_person' => 'Roberto Martínez', 
                'email' => 'roberto@fundacionxyz.org',
                'phone' => '+57 2 345 6789',
                'type' => 'customer',
                'tax_id' => '800987654-1'
            ],
            [
                'name' => 'Distribuidora de Papel Colombia',
                'contact_person' => 'Patricia Hernández',
                'email' => 'patricia@papelcolombia.com',
                'phone' => '+57 4 567 8901',
                'type' => 'supplier',
                'tax_id' => '900765432-8'
            ]
        ];

        foreach ($contacts as $contactData) {
            Contact::firstOrCreate(
                ['email' => $contactData['email'], 'company_id' => $company->id],
                array_merge($contactData, ['company_id' => $company->id])
            );
        }

        $this->command->info('   ✓ Contactos de prueba creados');
    }

    private function createPaperCatalog(Company $company): void
    {
        $papers = [
            [
                'code' => 'BOND-75', 
                'name' => 'Bond',
                'weight' => 75,
                'width' => 70.0,
                'height' => 100.0,
                'cost_per_sheet' => 850.00,
                'price' => 1200.00,
                'stock' => 500
            ],
            [
                'code' => 'PROP-115',
                'name' => 'Propalcote', 
                'weight' => 115,
                'width' => 70.0,
                'height' => 100.0,
                'cost_per_sheet' => 1450.00,
                'price' => 2000.00,
                'stock' => 300
            ],
            [
                'code' => 'CART-250',
                'name' => 'Cartulina',
                'weight' => 250,
                'width' => 70.0,
                'height' => 100.0, 
                'cost_per_sheet' => 2100.00,
                'price' => 2800.00,
                'stock' => 200
            ],
            [
                'code' => 'OPAL-180',
                'name' => 'Opalina',
                'weight' => 180,
                'width' => 70.0,
                'height' => 100.0,
                'cost_per_sheet' => 1850.00,
                'price' => 2500.00,
                'stock' => 150
            ]
        ];

        foreach ($papers as $paperData) {
            Paper::firstOrCreate(
                ['code' => $paperData['code'], 'company_id' => $company->id],
                array_merge($paperData, [
                    'company_id' => $company->id,
                    'is_own' => true,
                    'is_active' => true
                ])
            );
        }

        $this->command->info('   ✓ Catálogo de papeles creado');
    }

    private function createPrintingMachines(Company $company): void
    {
        $machines = [
            [
                'name' => 'Heidelberg Speedmaster SM 52-4',
                'type' => 'offset',
                'max_width' => 70.0,
                'max_height' => 100.0,
                'max_colors' => 4,
                'cost_per_impression' => 280.00,
                'setup_cost' => 15000.00
            ],
            [
                'name' => 'Xerox Versant 180',
                'type' => 'digital', 
                'max_width' => 32.0,
                'max_height' => 46.0,
                'max_colors' => 4,
                'cost_per_impression' => 450.00,
                'setup_cost' => 0.00
            ],
            [
                'name' => 'Komori Lithrone G40',
                'type' => 'offset',
                'max_width' => 100.0,
                'max_height' => 125.0,
                'max_colors' => 8,
                'cost_per_impression' => 350.00,
                'setup_cost' => 25000.00
            ]
        ];

        foreach ($machines as $machineData) {
            PrintingMachine::firstOrCreate(
                ['name' => $machineData['name'], 'company_id' => $company->id],
                array_merge($machineData, [
                    'company_id' => $company->id,
                    'is_own' => true,
                    'is_active' => true
                ])
            );
        }

        $this->command->info('   ✓ Máquinas de impresión creadas');
    }

    private function createTestProducts(Company $company): void
    {
        $products = [
            [
                'name' => 'Tarjetas de Presentación Premium',
                'code' => 'TCP-001',
                'description' => 'Tarjetas en cartulina 250g, impresión 4x1, plastificado mate',
                'purchase_price' => 180.00,
                'sale_price' => 280.00,
                'stock' => 50,
                'min_stock' => 10
            ],
            [
                'name' => 'Folletos Publicitarios A4',
                'code' => 'FPA-001', 
                'description' => 'Folleto tamaño carta, propalcote 115g, impresión 4x4',
                'purchase_price' => 125.00,
                'sale_price' => 200.00,
                'stock' => 100,
                'min_stock' => 20
            ],
            [
                'name' => 'Volantes Medio Pliego',
                'code' => 'VMP-001',
                'description' => 'Volante 1/2 pliego, bond 75g, impresión 4x0',
                'purchase_price' => 45.00,
                'sale_price' => 85.00,
                'stock' => 200,
                'min_stock' => 50
            ],
            [
                'name' => 'Carpetas Corporativas',
                'code' => 'CAR-001',
                'description' => 'Carpeta tamaño oficio, cartulina 300g, impresión 4x0, doble solapa',
                'purchase_price' => 850.00,
                'sale_price' => 1350.00,
                'stock' => 25,
                'min_stock' => 5
            ]
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(
                ['code' => $productData['code'], 'company_id' => $company->id],
                array_merge($productData, [
                    'company_id' => $company->id,
                    'is_own_product' => true,
                    'active' => true
                ])
            );
        }

        $this->command->info('   ✓ Productos de inventario creados');
    }
}