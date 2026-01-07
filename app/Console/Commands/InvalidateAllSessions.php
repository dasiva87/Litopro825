<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InvalidateAllSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grafired:invalidate-sessions
                            {--force : Forzar sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invalida todas las sesiones activas (útil después de cambios de configuración)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('⚠️  Esta acción cerrará la sesión de TODOS los usuarios');
        $this->newLine();

        $sessionCount = DB::table('sessions')->count();

        $this->info("Sesiones activas: {$sessionCount}");
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('¿Continuar?', false)) {
            $this->info('Cancelado');
            return self::SUCCESS;
        }

        try {
            DB::table('sessions')->truncate();

            $this->newLine();
            $this->info('✅ Todas las sesiones han sido invalidadas');
            $this->comment('   Los usuarios deberán hacer login nuevamente');
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error al invalidar sesiones: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
