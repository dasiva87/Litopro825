<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seeder para datos de producción mínimos necesarios.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando seeders de producción...');

        // Datos geográficos básicos (requeridos para la app)
        $this->call([
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
        ]);

        // Roles y permisos (críticos para la seguridad)
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // Tipos de documentos (necesarios para cotizaciones)
        $this->call([
            DocumentTypeSeeder::class,
        ]);

        // Acabados para talonarios (requeridos por TalonarioItem)
        $this->call([
            TalonarioFinishingsSeeder::class,
        ]);

        $this->command->info('✅ Seeders de producción completados exitosamente!');
        $this->command->warn('ℹ️  Recuerda crear manualmente:');
        $this->command->warn('   - Empresa principal');
        $this->command->warn('   - Usuario administrador');
        $this->command->warn('   - Papeles y máquinas de impresión');
    }
}
