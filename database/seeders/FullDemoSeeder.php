<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FullDemoSeeder extends Seeder
{
    /**
     * Seeder para demo completo con datos de prueba.
     * Incluye todos los datos necesarios para mostrar funcionalidades.
     */
    public function run(): void
    {
        $this->command->info('🎯 Iniciando seeder de demo completo...');

        // Primero los datos de producción básicos
        $this->call([
            ProductionSeeder::class,
        ]);

        // Luego datos de prueba y demo
        $this->call([
            TestDataSeeder::class,           // Empresas, usuarios, papeles, máquinas
            PlanSeeder::class,               // Planes de suscripción
            DigitalItemSeeder::class,        // Items digitales de ejemplo
            DemoQuotationSeeder::class,      // Cotización de demostración
            DashboardDemoSeeder::class,      // Datos para widgets del dashboard
            SocialNetworkDemoSeeder::class,  // Red social empresarial
            SocialPostSeeder::class,         // Posts de ejemplo
            CollectionAccountSeeder::class,  // Cuentas de cobro de ejemplo
        ]);

        $this->command->info('✅ Demo completo seeded exitosamente!');
        $this->command->info('🔗 Acceso demo:');
        $this->command->info('   URL: /admin');
        $this->command->info('   Usuario: demo@litopro.test');
        $this->command->info('   Password: password');
    }
}
