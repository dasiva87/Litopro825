<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MinimalProductionSeeder extends Seeder
{
    /**
     * Seed MÍNIMO Y LIMPIO para producción.
     * 
     * Incluye SOLO:
     * 1. Planes de suscripción (4 planes incluyendo gratuito)
     * 2. Roles y permisos del sistema
     * 3. Usuario super-admin inicial
     * 4. Datos geográficos (países, estados, ciudades)
     * 5. Tipos de documentos
     * 6. Acabados para talonarios
     * 
     * NO incluye datos de prueba ni empresas demo.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════╗');
        $this->command->info('║   🚀 GRAFIRED 3.0 - SEED DE PRODUCCIÓN        ║');
        $this->command->info('╚════════════════════════════════════════════════╝');
        $this->command->newLine();

        // 1. Datos geográficos (requeridos)
        $this->command->info('📍 Creando datos geográficos...');
        $this->call([
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
        ]);

        // 2. Tipos de documentos (requeridos)
        $this->command->info('📄 Creando tipos de documentos...');
        $this->call([DocumentTypeSeeder::class]);

        // 3. Acabados para talonarios (requeridos)
        $this->command->info('🎨 Creando acabados para talonarios...');
        $this->call([TalonarioFinishingsSeeder::class]);

        // 4. Roles y permisos
        $this->command->info('🔐 Creando roles y permisos...');
        $this->call([RolePermissionSeeder::class]);

        // 5. Planes de suscripción
        $this->seedPlans();

        // 6. Usuario super-admin
        $this->seedSuperAdmin();

        $this->command->newLine();
        $this->command->info('╔════════════════════════════════════════════════╗');
        $this->command->info('║   ✅ SEED DE PRODUCCIÓN COMPLETADO            ║');
        $this->command->info('╚════════════════════════════════════════════════╝');
        $this->command->newLine();
        
        $this->command->line('📊 RESUMEN:');
        $this->command->line('   • 4 Planes de suscripción');
        $this->command->line('   • 5 Roles del sistema');
        $this->command->line('   • 1 Usuario super-admin');
        $this->command->line('   • Datos geográficos de Colombia');
        $this->command->line('   • Tipos de documentos');
        $this->command->line('   • Acabados para talonarios');
        $this->command->newLine();
        
        $this->command->warn('⚠️  IMPORTANTE:');
        $this->command->warn('   1. Cambiar contraseña del super-admin después del primer login');
        $this->command->warn('   2. Configurar Stripe Price IDs en los planes de pago');
        $this->command->warn('   3. Configurar variables de entorno de Stripe');
        $this->command->newLine();
        
        $this->command->info('🔐 CREDENCIALES SUPER-ADMIN:');
        $this->command->info('   Email: admin@grafired.com');
        $this->command->info('   Password: GrafiRed2026!');
        $this->command->newLine();
    }

    private function seedPlans(): void
    {
        $this->command->info('💳 Creando planes de suscripción...');

        $plans = [
            [
                'name' => 'Plan Gratuito',
                'slug' => 'free',
                'description' => 'Plan gratuito para probar GrafiRed. Funcionalidades básicas limitadas.',
                'stripe_price_id' => null,
                'price' => 0.00,
                'currency' => 'cop',
                'interval' => 'month',
                'trial_days' => 0,
                'features' => [
                    '1 usuario',
                    'Hasta 10 cotizaciones por mes',
                    'Gestión básica de inventario (20 productos)',
                    'Calculadora de montaje',
                    'Soporte por email'
                ],
                'limits' => [
                    'max_users' => 1,
                    'max_documents_per_month' => 10,
                    'max_products' => 20,
                    'max_storage_mb' => 100,
                    'social_feed_access' => false,
                    'advanced_reports' => false
                ],
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Plan Básico',
                'slug' => 'basico',
                'description' => 'Plan ideal para litografías pequeñas que están comenzando.',
                'stripe_price_id' => null,
                'price' => 150000.00,
                'currency' => 'cop',
                'interval' => 'month',
                'trial_days' => 30,
                'features' => [
                    'Hasta 3 usuarios',
                    'Hasta 100 cotizaciones por mes',
                    'Gestión completa de inventario (100 productos)',
                    'Calculadora de montaje avanzada',
                    'Órdenes de compra y producción',
                    'Cuentas de cobro',
                    'Soporte por email',
                    'Red social de proveedores'
                ],
                'limits' => [
                    'max_users' => 3,
                    'max_documents_per_month' => 100,
                    'max_products' => 100,
                    'max_storage_mb' => 1000,
                    'social_feed_access' => true,
                    'advanced_reports' => false
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Plan Profesional',
                'slug' => 'profesional',
                'description' => 'Plan completo para litografías en crecimiento.',
                'stripe_price_id' => null,
                'price' => 300000.00,
                'currency' => 'cop',
                'interval' => 'month',
                'trial_days' => 30,
                'features' => [
                    'Hasta 10 usuarios',
                    'Cotizaciones ilimitadas',
                    'Productos ilimitados',
                    'Gestión avanzada de inventario',
                    'Órdenes de compra y producción',
                    'Cuentas de cobro',
                    'Reportes y analytics avanzados',
                    'Soporte prioritario',
                    'Red social completa'
                ],
                'limits' => [
                    'max_users' => 10,
                    'max_documents_per_month' => -1,
                    'max_products' => -1,
                    'max_storage_mb' => 5000,
                    'social_feed_access' => true,
                    'advanced_reports' => true
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Plan Empresarial',
                'slug' => 'empresarial',
                'description' => 'Plan completo para grandes litografías con todas las funcionalidades.',
                'stripe_price_id' => null,
                'price' => 500000.00,
                'currency' => 'cop',
                'interval' => 'month',
                'trial_days' => 30,
                'features' => [
                    'Usuarios ilimitados',
                    'Cotizaciones ilimitadas',
                    'Productos ilimitados',
                    'Gestión avanzada de inventario',
                    'Órdenes de compra y producción',
                    'Cuentas de cobro',
                    'Reportes y analytics avanzados',
                    'Soporte prioritario 24/7',
                    'Red social completa',
                    'Automatización de procesos',
                    'API access'
                ],
                'limits' => [
                    'max_users' => -1,
                    'max_documents_per_month' => -1,
                    'max_products' => -1,
                    'max_storage_mb' => 20000,
                    'social_feed_access' => true,
                    'advanced_reports' => true,
                    'automation_features' => true,
                    'api_access' => true
                ],
                'is_active' => true,
                'sort_order' => 3,
            ]
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
            $this->command->info("   ✓ {$planData['name']} - \${$planData['price']}");
        }
    }

    private function seedSuperAdmin(): void
    {
        $this->command->info('👤 Creando usuario Super Admin...');

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@grafired.com'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@grafired.com',
                'password' => Hash::make('GrafiRed2026!'),
                'company_id' => null,
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->assignRole('Super Admin');
        $this->command->info("   ✓ Super Admin creado: {$superAdmin->email}");
    }
}
