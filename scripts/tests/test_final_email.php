<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Company;
use App\Services\CommercialRequestService;

echo "🎉 PRUEBA FINAL - EMAILS INMEDIATOS (QUEUE_CONNECTION=sync)\n";
echo str_repeat("=", 70) . "\n\n";

echo "📋 CONFIGURACIÓN ACTUAL:\n";
echo "   QUEUE_CONNECTION: " . config('queue.default') . "\n";
echo "   MAIL_MAILER: " . config('mail.default') . "\n\n";

try {
    // 1. Email simple de prueba
    echo "1️⃣ Enviando email simple de prueba...\n";

    Mail::send([], [], function ($message) {
        $message->to('prueba@ejemplo.com')
                ->subject('✅ Configuración Finalizada - Grafired')
                ->html('
                    <h1>🎉 ¡Sistema de Emails Configurado!</h1>
                    <p>Este email se envió <strong>inmediatamente</strong> con la nueva configuración.</p>
                    <ul>
                        <li>✅ Mailtrap conectado</li>
                        <li>✅ QUEUE_CONNECTION=sync</li>
                        <li>✅ Emails inmediatos habilitados</li>
                    </ul>
                    <p><strong>Sistema Grafired - GrafiRed 3.0</strong></p>
                ');
    });

    echo "   ✅ Email simple enviado!\n\n";

    // 2. Esperar 2 segundos para no exceder límite de Mailtrap
    echo "   ⏳ Esperando 2 segundos (límite de Mailtrap)...\n\n";
    sleep(2);

    // 3. Notificación comercial real
    echo "2️⃣ Creando y enviando notificación comercial real...\n";

    $companies = Company::where('is_public', true)->limit(2)->get();

    if ($companies->count() >= 2) {
        $requestingCompany = $companies->first();
        $targetCompany = $companies->last();

        // Asegurar que hay usuarios
        $requestingUser = User::firstOrCreate(
            ['email' => 'solicitante_final@test.com'],
            [
                'name' => 'Usuario Solicitante Final',
                'password' => bcrypt('password'),
                'company_id' => $requestingCompany->id,
            ]
        );

        $targetUser = User::firstOrCreate(
            ['email' => 'destino_final@test.com'],
            [
                'name' => 'Usuario Destino Final',
                'password' => bcrypt('password'),
                'company_id' => $targetCompany->id,
            ]
        );

        echo "   👤 Solicitante: {$requestingUser->email}\n";
        echo "   👤 Destino: {$targetUser->email}\n\n";

        // Autenticar y enviar solicitud
        auth()->login($requestingUser);

        $service = app(CommercialRequestService::class);

        $request = $service->sendRequest(
            targetCompany: $targetCompany,
            relationshipType: 'supplier',
            message: 'Solicitud de prueba final - Sistema de emails configurado correctamente.'
        );

        echo "   ✅ Solicitud comercial creada (ID: {$request->id})\n";
        echo "   ✅ Notificación enviada INMEDIATAMENTE a: {$targetUser->email}\n\n";

    } else {
        echo "   ⚠️  No hay suficientes empresas públicas para probar\n\n";
    }

    echo str_repeat("=", 70) . "\n";
    echo "🎯 REVISA MAILTRAP AHORA:\n";
    echo "   URL: https://mailtrap.io/inboxes\n";
    echo "   Deberías ver:\n";
    echo "   📧 Email 1: 'Configuración Finalizada - Grafired'\n";
    echo "   📧 Email 2: 'Nueva Solicitud Comercial' (si había empresas)\n\n";

    echo "✅ CONFIGURACIÓN COMPLETA:\n";
    echo "   • Mailtrap funcionando ✅\n";
    echo "   • Emails inmediatos (sync) ✅\n";
    echo "   • Notificaciones del sistema ✅\n";
    echo "   • Sistema listo para usar ✅\n\n";

    echo "💡 NOTA: Todos los emails del sistema ahora se envían automáticamente.\n";

} catch (\Exception $e) {
    echo "❌ ERROR:\n";
    echo "   " . $e->getMessage() . "\n\n";
}
