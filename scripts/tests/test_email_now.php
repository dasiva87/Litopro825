<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Notifications\CommercialRequestReceived;
use App\Models\CommercialRequest;

echo "🚀 ENVIANDO EMAIL SIN QUEUE (INMEDIATO)\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Opción 1: Email simple usando Mail::send() directamente (sin queue)
    echo "📧 Enviando email de prueba simple...\n";

    Mail::send([], [], function ($message) {
        $message->to('prueba@ejemplo.com')
                ->subject('🎯 Email Directo - Sin Queue')
                ->html('<h1>¡Hola desde Grafired!</h1><p>Este email se envió <strong>directamente</strong> sin pasar por la queue.</p><p>Si lo ves en Mailtrap, ¡la configuración SMTP funciona perfectamente!</p>');
    });

    echo "✅ Email simple enviado!\n\n";

    // Opción 2: Enviar notificación INMEDIATA (sin queue)
    echo "📨 Enviando notificación comercial inmediata...\n";

    $user = User::first();
    $request = CommercialRequest::latest()->first();

    if ($user && $request) {
        // Usar notify()->now() para enviar INMEDIATAMENTE sin queue
        $user->notify((new CommercialRequestReceived($request))->delay(now()));

        echo "✅ Notificación enviada inmediatamente!\n";
        echo "   Usuario: {$user->email}\n";
        echo "   Request ID: {$request->id}\n\n";
    } else {
        echo "⚠️  No hay usuarios o solicitudes para probar\n\n";
    }

    echo "🎯 AHORA REVISA MAILTRAP:\n";
    echo "   URL: https://mailtrap.io/inboxes\n";
    echo "   Deberías ver al menos 1 email nuevo\n\n";

    echo "💡 NOTA IMPORTANTE:\n";
    echo "   Con QUEUE_CONNECTION=database, las notificaciones normalmente\n";
    echo "   se encolan y requieren ejecutar: php artisan queue:work\n\n";
    echo "   Para enviar emails INMEDIATAMENTE sin queue, puedes:\n";
    echo "   1. Cambiar .env a: QUEUE_CONNECTION=sync\n";
    echo "   2. O mantener queue:work corriendo siempre en producción\n\n";

} catch (\Exception $e) {
    echo "❌ ERROR:\n";
    echo $e->getMessage() . "\n\n";
    echo $e->getTraceAsString() . "\n";
}
