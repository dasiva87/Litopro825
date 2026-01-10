<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\User;
use App\Services\CommercialRequestService;

echo "🚀 Probando Sistema de Notificaciones Comerciales de Grafired\n";
echo "=" . str_repeat("=", 60) . "\n\n";

try {
    // Obtener empresas públicas para simular solicitud
    $companies = Company::where('is_public', true)
                        ->where('id', '!=', 1) // Excluir la primera empresa
                        ->limit(2)
                        ->get();

    if ($companies->count() < 2) {
        echo "⚠️  Necesitamos al menos 2 empresas públicas.\n";
        echo "📝 Creando empresas de prueba...\n\n";

        // Crear empresa solicitante si no existe
        $requestingCompany = Company::firstOrCreate(
            ['nit' => '900111222'],
            [
                'company_name' => 'Litografía Test Solicitante',
                'company_type' => 'litografia',
                'is_public' => true,
                'address' => 'Calle 123 #45-67',
                'city' => 'Bogotá',
                'state' => 'Cundinamarca',
                'country' => 'Colombia',
                'phone' => '3001234567',
                'email' => 'contacto@litotest.com',
            ]
        );

        // Crear empresa destino si no existe
        $targetCompany = Company::firstOrCreate(
            ['nit' => '900333444'],
            [
                'company_name' => 'Distribuidora Test Destino',
                'company_type' => 'distribuidora',
                'is_public' => true,
                'address' => 'Carrera 45 #67-89',
                'city' => 'Medellín',
                'state' => 'Antioquia',
                'country' => 'Colombia',
                'phone' => '3009876543',
                'email' => 'ventas@distritest.com',
            ]
        );
    } else {
        $requestingCompany = $companies->first();
        $targetCompany = $companies->last();
    }

    echo "📍 Empresa Solicitante: {$requestingCompany->company_name}\n";
    echo "📍 Empresa Destino: {$targetCompany->company_name}\n\n";

    // Crear usuarios si no existen
    $requestingUser = User::firstOrCreate(
        ['email' => 'solicitante@test.com'],
        [
            'name' => 'Usuario Solicitante',
            'password' => bcrypt('password'),
            'company_id' => $requestingCompany->id,
        ]
    );

    $targetUser = User::firstOrCreate(
        ['email' => 'destino@test.com'],
        [
            'name' => 'Usuario Destino',
            'password' => bcrypt('password'),
            'company_id' => $targetCompany->id,
        ]
    );

    echo "👤 Usuario Solicitante: {$requestingUser->name} ({$requestingUser->email})\n";
    echo "👤 Usuario Destino: {$targetUser->name} ({$targetUser->email})\n\n";

    // Crear solicitud usando el servicio
    echo "📨 Enviando solicitud comercial...\n";

    // Simular autenticación del usuario solicitante
    auth()->login($requestingUser);

    $service = app(CommercialRequestService::class);

    $request = $service->sendRequest(
        targetCompany: $targetCompany,
        relationshipType: 'supplier',
        message: '¡Hola! Nos gustaría establecer una relación comercial. Somos una litografía con 10 años de experiencia y estamos interesados en sus productos.'
    );

    echo "✅ Solicitud creada exitosamente!\n\n";
    echo "📋 Detalles de la Solicitud:\n";
    echo "   - ID: {$request->id}\n";
    echo "   - Tipo: " . ($request->relationship_type === 'supplier' ? 'Proveedor' : 'Cliente') . "\n";
    echo "   - Estado: {$request->status}\n";
    echo "   - Mensaje: {$request->message}\n\n";

    echo "📧 Notificación enviada a:\n";
    echo "   - Email: {$targetUser->email}\n";
    echo "   - Tipo: CommercialRequestReceived\n\n";

    echo "🎯 ACCIÓN REQUERIDA:\n";
    echo "   1. Ve a Mailtrap: https://mailtrap.io/inboxes\n";
    echo "   2. Deberías ver un email con:\n";
    echo "      - Asunto: 'Nueva Solicitud Comercial'\n";
    echo "      - De: noreply@grafired.com\n";
    echo "      - Para: {$targetUser->email}\n";
    echo "      - Contenido: Notificación de solicitud de proveedor\n\n";

    echo "💡 Para probar el flujo completo:\n";
    echo "   - Aprobar: ID del request = {$request->id}\n";
    echo "   - Ejecuta: php test_approve_request.php {$request->id}\n\n";

} catch (\Exception $e) {
    echo "❌ Error:\n";
    echo $e->getMessage() . "\n";
    echo "\n" . $e->getTraceAsString() . "\n";
}
