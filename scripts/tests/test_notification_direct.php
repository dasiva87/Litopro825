<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\CommercialRequest;
use App\Notifications\CommercialRequestReceived;
use Illuminate\Support\Facades\Notification;

echo "🔍 PRUEBA DIRECTA DE NOTIFICACIÓN\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Obtener solicitud reciente
    $request = CommercialRequest::latest()->first();

    if (!$request) {
        echo "❌ No hay solicitudes comerciales\n";
        exit(1);
    }

    echo "📋 Solicitud encontrada: ID {$request->id}\n";
    echo "   Estado: {$request->status}\n";
    echo "   Tipo: {$request->relationship_type}\n\n";

    // Obtener usuario destino
    $user = User::where('company_id', $request->target_company_id)->first();

    if (!$user) {
        echo "❌ No hay usuario en la empresa destino\n";
        exit(1);
    }

    echo "👤 Usuario destino: {$user->name} ({$user->email})\n\n";

    // Crear la notificación
    echo "📧 Creando notificación...\n";
    $notification = new CommercialRequestReceived($request);

    echo "   Canales: " . implode(', ', $notification->via($user)) . "\n\n";

    // Enviar notificación con logging detallado
    echo "📨 Enviando notificación...\n";

    // Habilitar logging detallado
    \Illuminate\Support\Facades\DB::enableQueryLog();

    try {
        $user->notify($notification);
        echo "   ✅ Método notify() ejecutado sin errores\n\n";
    } catch (\Exception $e) {
        echo "   ❌ Error en notify():\n";
        echo "   " . $e->getMessage() . "\n\n";
        throw $e;
    }

    // Verificar notificación en base de datos
    $dbNotification = \Illuminate\Notifications\DatabaseNotification::where('notifiable_id', $user->id)
        ->where('notifiable_type', get_class($user))
        ->latest()
        ->first();

    if ($dbNotification) {
        echo "✅ Notificación guardada en BD:\n";
        echo "   ID: {$dbNotification->id}\n";
        echo "   Tipo: {$dbNotification->type}\n";
        echo "   Creada: {$dbNotification->created_at}\n\n";
    } else {
        echo "⚠️  No se encontró notificación en BD\n\n";
    }

    // Verificar en queue
    $queueJobs = \Illuminate\Support\Facades\DB::table('jobs')->count();
    echo "📊 Jobs en queue: {$queueJobs}\n";

    if ($queueJobs > 0) {
        echo "   ⚠️  Hay jobs pendientes (no debería con sync)\n\n";
    } else {
        echo "   ✅ Queue vacía (correcto con sync)\n\n";
    }

    // Verificar configuración queue
    echo "⚙️  CONFIGURACIÓN:\n";
    echo "   QUEUE_CONNECTION: " . config('queue.default') . "\n";
    echo "   MAIL_MAILER: " . config('mail.default') . "\n";
    echo "   MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
    echo "   MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n\n";

    // Intentar crear y enviar MailMessage directamente
    echo "📧 PRUEBA 2: Enviando MailMessage directo...\n";

    $mailMessage = $notification->toMail($user);

    echo "   Subject: " . ($mailMessage->subject ?? 'N/A') . "\n";
    echo "   View Data keys: " . implode(', ', array_keys($mailMessage->viewData ?? [])) . "\n\n";

    // Enviar email directo bypassing notification
    echo "📨 PRUEBA 3: Email directo con Mail facade...\n";

    \Illuminate\Support\Facades\Mail::raw('Test directo desde PHP', function ($message) use ($user) {
        $message->to($user->email)
                ->subject('Test Directo - Debug');
    });

    echo "   ✅ Mail::raw() ejecutado\n\n";

    echo str_repeat("=", 70) . "\n";
    echo "🎯 REVISA MAILTRAP:\n";
    echo "   Si llegó 'Test Directo - Debug' → SMTP funciona\n";
    echo "   Si NO llegó la notificación → Problema en Notification class\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR:\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
