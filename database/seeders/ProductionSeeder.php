<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seeder para datos de producción mínimos necesarios.
     *
     * Este seeder llama a MinimalProductionSeeder que incluye:
     * - Planes de suscripción (4 planes)
     * - Roles y permisos
     * - Usuario super-admin
     * - Datos geográficos
     * - Tipos de documentos
     * - Acabados para talonarios
     */
    public function run(): void
    {
        $this->call([
            MinimalProductionSeeder::class,
        ]);
    }
}
