<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "🚀 Enviando email de prueba a Mailtrap...\n\n";

try {
    Mail::raw('✅ Email de prueba desde Grafired - Sistema de notificaciones configurado correctamente!', function ($message) {
        $message->to('test@ejemplo.com')
                ->subject('Prueba Mailtrap - Grafired');
    });

    echo "✅ Email enviado exitosamente!\n";
    echo "📧 Revisa tu inbox en Mailtrap: https://mailtrap.io/inboxes\n\n";

} catch (\Exception $e) {
    echo "❌ Error al enviar email:\n";
    echo $e->getMessage() . "\n";
}
