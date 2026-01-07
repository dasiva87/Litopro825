<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearProductionCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grafired:clear-cache
                            {--production : Regenerar cachés optimizados para producción}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia todos los cachés de la aplicación (vistas, config, rutas, eventos, Filament)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Limpiando cachés de GrafiRed...');
        $this->newLine();

        // Paso 1: Limpiar todos los cachés
        $this->info('📋 Limpiando caché de configuración...');
        $this->call('config:clear');

        $this->info('🛣️  Limpiando caché de rutas...');
        $this->call('route:clear');

        $this->info('👁️  Limpiando caché de vistas...');
        $this->call('view:clear');

        $this->info('💾 Limpiando caché de aplicación...');
        $this->call('cache:clear');

        $this->info('📢 Limpiando caché de eventos...');
        $this->call('event:clear');

        $this->info('🎨 Limpiando caché de componentes Filament...');
        $this->call('filament:cache-components');

        $this->info('⚡ Limpiando optimizaciones...');
        $this->call('optimize:clear');

        $this->newLine();

        // Paso 2: Si es producción, regenerar cachés
        if ($this->option('production')) {
            $this->warn('🏭 Modo PRODUCCIÓN: Regenerando cachés optimizados...');
            $this->newLine();

            $this->info('📋 Cacheando configuración...');
            $this->call('config:cache');

            $this->info('🛣️  Cacheando rutas...');
            $this->call('route:cache');

            $this->info('📢 Cacheando eventos...');
            $this->call('event:cache');

            $this->info('⚡ Optimizando aplicación...');
            $this->call('optimize');

            $this->newLine();
            $this->comment('💡 No olvides ejecutar: composer dump-autoload --optimize');
        }

        $this->newLine();
        $this->info('✅ ¡Todos los cachés han sido limpiados!');
        $this->newLine();

        // Mostrar resumen
        $this->table(
            ['Caché', 'Estado'],
            [
                ['Configuración', '✓ Limpiado'],
                ['Rutas', '✓ Limpiado'],
                ['Vistas', '✓ Limpiado'],
                ['Aplicación', '✓ Limpiado'],
                ['Eventos', '✓ Limpiado'],
                ['Filament', '✓ Limpiado'],
                ['Optimizaciones', '✓ Limpiado'],
            ]
        );

        if ($this->option('production')) {
            $this->newLine();
            $this->info('🚀 La aplicación está optimizada para producción');
        } else {
            $this->newLine();
            $this->comment('💡 Para producción, usa: php artisan grafired:clear-cache --production');
        }

        return self::SUCCESS;
    }
}
