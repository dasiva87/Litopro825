<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "🔍 DIAGNÓSTICO DE CONFIGURACIÓN DE EMAIL\n";
echo str_repeat("=", 70) . "\n\n";

// Mostrar configuración actual
echo "📋 CONFIGURACIÓN ACTUAL:\n";
echo "   MAIL_MAILER: " . config('mail.default') . "\n";
echo "   MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "   MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "   MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "   MAIL_PASSWORD: " . (config('mail.mailers.smtp.password') ? str_repeat('*', 10) : 'NO CONFIGURADO') . "\n";
echo "   MAIL_ENCRYPTION: " . config('mail.mailers.smtp.encryption') . "\n";
echo "   MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "   MAIL_FROM_NAME: " . config('mail.from.name') . "\n\n";

// Verificar que Queue está configurado
echo "📦 CONFIGURACIÓN DE QUEUE:\n";
echo "   QUEUE_CONNECTION: " . config('queue.default') . "\n\n";

if (config('queue.default') === 'database') {
    echo "⚠️  IMPORTANTE: Emails se están encolando en base de datos\n";
    echo "   Para procesarlos debes ejecutar: php artisan queue:work\n\n";
}

// Intentar enviar email
echo "📧 ENVIANDO EMAIL DE PRUEBA...\n";

try {
    // Forzar envío inmediato (sin queue)
    Mail::fake(false);

    Mail::raw('Este es un email de prueba desde Grafired. Si lo ves en Mailtrap, ¡la configuración funciona!', function ($message) {
        $message->to('test@ejemplo.com')
                ->subject('✅ Prueba de Email - Grafired');
    });

    echo "✅ Email enviado (o encolado) exitosamente\n\n";

    // Verificar jobs en queue
    if (config('queue.default') === 'database') {
        $pendingJobs = \DB::table('jobs')->count();
        echo "📊 Jobs pendientes en queue: {$pendingJobs}\n";

        if ($pendingJobs > 0) {
            echo "\n🎯 ACCIÓN REQUERIDA:\n";
            echo "   Los emails están en la queue pero NO se han enviado aún.\n";
            echo "   Para enviarlos ejecuta en otra terminal:\n\n";
            echo "   php artisan queue:work --stop-when-empty\n\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ ERROR al enviar email:\n";
    echo "   " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
