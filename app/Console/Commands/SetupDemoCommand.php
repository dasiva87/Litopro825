<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SetupDemoCommand extends Command
{
    protected $signature = 'grafired:setup-demo {--fresh : Drop all tables and recreate}';
    protected $description = 'Setup GrafiRed with demo data for testing and development';

    public function handle()
    {
        $this->info('🚀 Configurando GrafiRed para demostración...');
        $this->newLine();

        try {
            if ($this->option('fresh')) {
                $this->setupFreshDemo();
            } else {
                $this->setupDemo();
            }

            $this->displayAccessInfo();
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la configuración: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function setupFreshDemo(): void
    {
        $this->warn('⚠️  Esta operación eliminará TODOS los datos existentes.');
        if (!$this->confirm('¿Continuar con la instalación limpia?')) {
            $this->info('Operación cancelada.');
            return;
        }

        $this->info('🗃️ Recreando base de datos...');
        Artisan::call('migrate:fresh', [], $this->getOutput());

        $this->info('📋 Ejecutando seeders base...');
        Artisan::call('db:seed', ['--class' => 'CountrySeeder'], $this->getOutput());
        Artisan::call('db:seed', ['--class' => 'StateSeeder'], $this->getOutput());
        Artisan::call('db:seed', ['--class' => 'CitySeeder'], $this->getOutput());
        
        $this->setupDemo();
    }

    private function setupDemo(): void
    {
        $this->info('👥 Creando datos de prueba...');
        Artisan::call('db:seed', ['--class' => 'TestDataSeeder'], $this->getOutput());

        $this->info('📋 Creando cotización de demostración...');
        Artisan::call('db:seed', ['--class' => 'DemoQuotationSeeder'], $this->getOutput());

        $this->info('🧹 Limpiando caché...');
        Artisan::call('optimize:clear', [], $this->getOutput());
    }

    private function displayAccessInfo(): void
    {
        $this->newLine();
        $this->info('✅ ¡GrafiRed configurado exitosamente!');
        $this->newLine();
        
        $this->line('<fg=cyan>📋 INFORMACIÓN DE ACCESO:</fg=cyan>');
        $this->table(
            ['Rol', 'Email', 'Contraseña', 'Descripción'],
            [
                ['Admin', 'admin@grafired.test', 'password', 'Acceso completo al sistema'],
                ['Manager', 'manager@grafired.test', 'password', 'Gestión de ventas y reportes'],
                ['Employee', 'employee@grafired.test', 'password', 'Operación básica']
            ]
        );
        
        $this->newLine();
        $this->line('<fg=green>🌐 URL del sistema: /admin</fg=green>');
        $this->line('<fg=yellow>🏢 Empresa: GrafiRed Demo</fg=yellow>');
        $this->line('<fg=magenta>📊 Cotización demo creada: COT-2025-DEMO-001</fg=magenta>');
        
        $this->newLine();
        $this->line('<fg=blue>💡 Datos incluidos:</fg=blue>');
        $this->line('   • Roles y permisos completos');
        $this->line('   • 3 usuarios de diferentes niveles');
        $this->line('   • Catálogo de papeles y máquinas');
        $this->line('   • Productos de inventario');
        $this->line('   • Clientes y proveedores');
        $this->line('   • Cotización con SimpleItems y Products');
        
        $this->newLine();
        $this->line('<fg=gray>Para reinstalar: php artisan grafired:setup-demo --fresh</fg=gray>');
    }
}
