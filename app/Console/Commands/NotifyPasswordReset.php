<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;

class NotifyPasswordReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grafired:notify-password-reset
                            {--date= : Fecha límite de usuarios afectados (YYYY-MM-DD)}
                            {--dry-run : Solo mostrar usuarios sin enviar emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notificar a usuarios afectados por fix de doble hashing que deben resetear su contraseña';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoffDate = $this->option('date') ?? '2026-01-07 00:00:00';
        $isDryRun = $this->option('dry-run');

        $this->info("🔐 Usuarios afectados por fix de password hashing");
        $this->newLine();

        // Buscar usuarios creados antes del fix
        $affectedUsers = User::where('created_at', '<', $cutoffDate)
            ->whereNotNull('email')
            ->get();

        if ($affectedUsers->isEmpty()) {
            $this->info('✅ No hay usuarios afectados');
            return self::SUCCESS;
        }

        $this->warn("⚠️  Usuarios encontrados: {$affectedUsers->count()}");
        $this->newLine();

        // Mostrar lista
        $this->table(
            ['ID', 'Nombre', 'Email', 'Empresa', 'Creado'],
            $affectedUsers->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->company?->name ?? 'N/A',
                    $user->created_at->format('Y-m-d H:i'),
                ];
            })
        );

        if ($isDryRun) {
            $this->newLine();
            $this->comment('💡 Modo DRY RUN - No se enviarán emails');
            $this->comment('   Para enviar emails reales, ejecuta sin --dry-run');
            return self::SUCCESS;
        }

        $this->newLine();

        if (!$this->confirm('¿Enviar emails de reset a estos usuarios?', false)) {
            $this->info('Cancelado por el usuario');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('📧 Enviando emails de reset...');
        $this->newLine();

        $sent = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($affectedUsers->count());
        $progressBar->start();

        foreach ($affectedUsers as $user) {
            try {
                // Enviar link de reset de password
                $status = Password::sendResetLink(['email' => $user->email]);

                if ($status === Password::RESET_LINK_SENT) {
                    $sent++;
                } else {
                    $failed++;
                    $this->newLine();
                    $this->error("   ❌ Error enviando a {$user->email}: {$status}");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("   ❌ Excepción para {$user->email}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Resumen
        $this->table(
            ['Resultado', 'Cantidad'],
            [
                ['Emails enviados', "✅ {$sent}"],
                ['Fallos', $failed > 0 ? "❌ {$failed}" : "✅ 0"],
                ['Total', $affectedUsers->count()],
            ]
        );

        if ($sent > 0) {
            $this->newLine();
            $this->info("✅ Se enviaron {$sent} emails de reset de password");
            $this->comment('   Los usuarios recibirán un link para restablecer su contraseña');
        }

        if ($failed > 0) {
            $this->newLine();
            $this->warn("⚠️  {$failed} usuarios no recibieron email - revisar logs");
        }

        return self::SUCCESS;
    }
}
