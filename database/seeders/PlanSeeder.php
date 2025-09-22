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
        $plans = [
            [
                'name' => 'Plan Gratuito',
                'slug' => 'free',
                'description' => 'Plan gratuito para probar LitoPro. Funcionalidades básicas.',
                'stripe_price_id' => null,
                'price' => 0.00,
                'currency' => 'usd',
                'interval' => 'month',
                'features' => [
                    '1 usuario',
                    'Hasta 5 cotizaciones por mes',
                    'Gestión básica de inventario (10 productos)',
                    'Calculadora de papel básica',
                    'Soporte por email',
                    'Acceso limitado a red social'
                ],
                'limits' => [
                    'max_users' => 1,
                    'max_documents_per_month' => 5,
                    'max_products' => 10,
                    'max_storage_mb' => 100,
                    'social_feed_access' => true,
                    'advanced_reports' => false
                ],
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Plan Básico',
                'slug' => 'basico',
                'description' => 'Plan ideal para litografías pequeñas que están comenzando.',
                'stripe_price_id' => 'price_basic_monthly_demo',
                'price' => 29.99,
                'currency' => 'usd',
                'interval' => 'month',
                'features' => [
                    'Hasta 3 usuarios',
                    'Hasta 50 cotizaciones por mes',
                    'Gestión básica de inventario',
                    'Calculadora de papel',
                    'Soporte por email',
                    'Acceso a red social básico'
                ],
                'limits' => [
                    'max_users' => 3,
                    'max_documents_per_month' => 50,
                    'max_products' => 25,
                    'max_storage_mb' => 500,
                    'social_feed_access' => true,
                    'advanced_reports' => false
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Plan Pro',
                'slug' => 'pro',
                'description' => 'Plan perfecto para litografías en crecimiento con necesidades avanzadas.',
                'stripe_price_id' => 'price_pro_monthly_demo',
                'price' => 79.99,
                'currency' => 'usd',
                'interval' => 'month',
                'features' => [
                    'Hasta 10 usuarios',
                    'Cotizaciones ilimitadas',
                    'Gestión avanzada de inventario',
                    'Calculadora de papel avanzada',
                    'Reportes y analytics',
                    'Soporte prioritario',
                    'Red social completa',
                    'Automatización de precios',
                    'Integración con proveedores'
                ],
                'limits' => [
                    'max_users' => 10,
                    'max_documents_per_month' => -1, // Ilimitado
                    'max_products' => 200,
                    'max_storage_mb' => 5000,
                    'social_feed_access' => true,
                    'advanced_reports' => true,
                    'automation_features' => true,
                    'api_access' => true
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Plan Enterprise',
                'slug' => 'enterprise',
                'description' => 'Solución completa para grandes litografías y redes de empresas.',
                'stripe_price_id' => 'price_enterprise_monthly_demo',
                'price' => 199.99,
                'currency' => 'usd',
                'interval' => 'month',
                'features' => [
                    'Usuarios ilimitados',
                    'Cotizaciones ilimitadas',
                    'Multi-empresa/sucursales',
                    'BI y reportes avanzados',
                    'Soporte 24/7',
                    'Red social premium',
                    'Automatización completa',
                    'Integración personalizada',
                    'Manager dedicado',
                    'Backup y seguridad avanzada'
                ],
                'limits' => [
                    'max_users' => -1, // Ilimitado
                    'max_documents_per_month' => -1, // Ilimitado
                    'max_products' => -1, // Ilimitado
                    'max_storage_mb' => -1, // Ilimitado
                    'social_feed_access' => true,
                    'advanced_reports' => true,
                    'automation_features' => true,
                    'api_access' => true,
                    'multi_company' => true,
                    'custom_integrations' => true
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Plan Básico Anual',
                'slug' => 'basico-anual',
                'description' => 'Plan básico con descuento anual (2 meses gratis).',
                'stripe_price_id' => 'price_basic_yearly_demo',
                'price' => 299.99,
                'currency' => 'usd',
                'interval' => 'year',
                'features' => [
                    'Hasta 3 usuarios',
                    'Hasta 50 cotizaciones por mes',
                    'Gestión básica de inventario',
                    'Calculadora de papel',
                    'Soporte por email',
                    'Acceso a red social básico',
                    '2 meses gratis vs plan mensual'
                ],
                'limits' => [
                    'max_users' => 3,
                    'max_documents_per_month' => 50,
                    'max_products' => 25,
                    'max_storage_mb' => 500,
                    'social_feed_access' => true,
                    'advanced_reports' => false
                ],
                'is_active' => true,
                'sort_order' => 4,
            ]
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']], // Buscar por slug
                $planData // Datos a actualizar o crear
            );
        }
    }
}
