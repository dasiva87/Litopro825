<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Models\Document;
use App\Models\PurchaseOrder;
use App\Models\ProductionOrder;
use App\Models\CollectionAccount;
use Illuminate\Console\Command;

class CleanTestData extends Command
{
    protected $signature = 'grafired:clean-test-data {--force : No pedir confirmación}';
    protected $description = 'Elimina todos los datos de prueba/demo de la base de datos, manteniendo solo datos del sistema';

    public function handle(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  ADVERTENCIA: Esto eliminará TODAS las empresas, usuarios (excepto super-admin), cotizaciones, órdenes, etc. ¿Continuar?')) {
                $this->warn('Operación cancelada');
                return self::FAILURE;
            }
        }

        $this->info('🧹 Limpiando datos de prueba de GrafiRed...');
        $this->newLine();

        // Contar antes
        $companiesCount = Company::count();
        $usersCount = User::whereNotNull('company_id')->count();
        $documentsCount = Document::count();
        $purchaseOrdersCount = PurchaseOrder::count();
        $productionOrdersCount = ProductionOrder::count();
        $collectionAccountsCount = CollectionAccount::count();

        $this->info("📊 Datos actuales:");
        $this->line("   • Empresas: {$companiesCount}");
        $this->line("   • Usuarios (con empresa): {$usersCount}");
        $this->line("   • Cotizaciones: {$documentsCount}");
        $this->line("   • Órdenes de Pedido: {$purchaseOrdersCount}");
        $this->line("   • Órdenes de Producción: {$productionOrdersCount}");
        $this->line("   • Cuentas de Cobro: {$collectionAccountsCount}");
        $this->newLine();

        // Eliminar en orden correcto (por foreign keys)
        
        $this->info('🗑️  Eliminando cuentas de cobro...');
        CollectionAccount::truncate();
        
        $this->info('🗑️  Eliminando órdenes de producción...');
        ProductionOrder::query()->delete();
        
        $this->info('🗑️  Eliminando órdenes de pedido...');
        PurchaseOrder::query()->delete();
        
        $this->info('🗑️  Eliminando cotizaciones y sus items...');
        Document::query()->delete();
        
        $this->info('🗑️  Eliminando usuarios de empresas...');
        User::whereNotNull('company_id')->delete();
        
        $this->info('🗑️  Eliminando empresas...');
        Company::query()->delete();

        $this->newLine();
        $this->info('✅ Datos de prueba eliminados correctamente');
        $this->newLine();

        // Verificar super admin
        $superAdmin = User::whereNull('company_id')
            ->whereHas('roles', function($q) {
                $q->where('name', 'Super Admin');
            })
            ->first();

        if ($superAdmin) {
            $this->info("✓ Super Admin preservado: {$superAdmin->email}");
        } else {
            $this->warn('⚠️  Super Admin no encontrado. Ejecuta el seeder para crearlo.');
        }

        $this->newLine();
        $this->comment('💡 Ahora ejecuta: php artisan db:seed --class=MinimalProductionSeeder --force');
        
        return self::SUCCESS;
    }
}
