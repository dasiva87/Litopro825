<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📋 Creando planes de suscripción...');

        $plans = [
            [
                'name' => 'Plan Gratuito',
                'slug' => 'free',
                'description' => 'Plan gratuito para probar LitoPro. Funcionalidades básicas.',
                'stripe_price_id' => null,
                'price' => 0.00,
                'currency' => 'usd',
                'interval' => 'month',
                'trial_days' => 0,
                'features' => [
                    '1 usuario',
                    'Hasta 10 cotizaciones por mes',
                    'Gestión básica de inventario (20 productos)',
                    'Calculadora de papel básica',
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
                'stripe_price_id' => 'price_basic_monthly',
                'price' => 150000.00,
                'currency' => 'cop',
                'interval' => 'month',
                'trial_days' => 30,
                'features' => [
                    'Hasta 3 usuarios',
                    'Hasta 100 cotizaciones por mes',
                    'Gestión completa de inventario (100 productos)',
                    'Calculadora de papel avanzada',
                    'Órdenes de compra',
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
                'name' => 'Plan Pro',
                'slug' => 'pro',
                'description' => 'Plan completo para litografías en crecimiento con todas las funcionalidades.',
                'stripe_price_id' => 'price_pro_monthly',
                'price' => 350000.00,
                'currency' => 'cop',
                'interval' => 'month',
                'trial_days' => 30,
                'features' => [
                    'Usuarios ilimitados',
                    'Cotizaciones ilimitadas',
                    'Productos ilimitados',
                    'Gestión avanzada de inventario',
                    'Calculadora de papel avanzada',
                    'Órdenes de compra',
                    'Cuentas de cobro',
                    'Reportes y analytics avanzados',
                    'Soporte prioritario 24/7',
                    'Red social completa',
                    'Automatización de procesos',
                    'Integración con proveedores',
                    'API access'
                ],
                'limits' => [
                    'max_users' => -1, // Ilimitado
                    'max_documents_per_month' => -1, // Ilimitado
                    'max_products' => -1, // Ilimitado
                    'max_storage_mb' => 10000,
                    'social_feed_access' => true,
                    'advanced_reports' => true,
                    'automation_features' => true,
                    'api_access' => true
                ],
                'is_active' => true,
                'sort_order' => 2,
            ]
        ];

        foreach ($plans as $planData) {
            $plan = Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
            $this->command->info("   ✓ {$plan->name} creado/actualizado");
        }

        $this->command->info('✅ Planes de suscripción creados exitosamente!');
    }
}
