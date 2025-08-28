<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar seeders en orden
        $this->call([
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            RolePermissionSeeder::class,
            TestDataSeeder::class, // Datos de prueba para desarrollo
        ]);

        $this->command->info('LitoPro seeded successfully with test data!');
    }
}
