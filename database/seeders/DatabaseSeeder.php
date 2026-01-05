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
        // Determinar qué seeder ejecutar basado en el ambiente
        $environment = app()->environment();

        if ($environment === 'production') {
            $this->command->info('🚀 Ambiente de PRODUCCIÓN detectado');
            $this->call([
                ProductionSeeder::class,
            ]);
        } else {
            $this->command->info('🔧 Ambiente de DESARROLLO detectado');
            $this->call([
                FullDemoSeeder::class,
            ]);
        }

        $this->command->info('✅ GrafiRed seeded successfully!');

        if ($environment !== 'production') {
            $this->command->info('💡 Para producción usa: php artisan db:seed --class=ProductionSeeder');
        }
    }
}
