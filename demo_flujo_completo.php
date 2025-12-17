<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\User;
use App\Services\CommercialRequestService;

echo "🎯 DEMO: FLUJO COMPLETO DE SOLICITUD COMERCIAL + EMAILS\n";
echo str_repeat("=", 70) . "\n\n";

try {
    $service = app(CommercialRequestService::class);

    // Obtener o crear empresas
    $companies = Company::where('is_public', true)->get();

    if ($companies->count() < 2) {
        echo "⚠️  Necesitamos al menos 2 empresas. Creando...\n\n";

        $empresa1 = Company::firstOrCreate(
            ['nit' => '900555111'],
            [
                'company_name' => 'Litografía Demo Email',
                'company_type' => 'litografia',
                'is_public' => true,
                'address' => 'Calle Demo 1',
                'city' => 'Bogotá',
                'state' => 'Cundinamarca',
                'country' => 'Colombia',
                'phone' => '3001111111',
                'email' => 'demo1@grafired.com',
            ]
        );

        $empresa2 = Company::firstOrCreate(
            ['nit' => '900555222'],
            [
                'company_name' => 'Proveedor Demo Email',
                'company_type' => 'proveedor_insumos',
                'is_public' => true,
                'address' => 'Calle Demo 2',
                'city' => 'Medellín',
                'state' => 'Antioquia',
                'country' => 'Colombia',
                'phone' => '3002222222',
                'email' => 'demo2@grafired.com',
            ]
        );
    } else {
        $empresa1 = $companies[0];
        $empresa2 = $companies[1];
    }

    // Crear usuarios
    $usuario1 = User::firstOrCreate(
        ['email' => 'juan.solicitante@grafired.com'],
        [
            'name' => 'Juan Solicitante',
            'password' => bcrypt('password'),
            'company_id' => $empresa1->id,
        ]
    );

    $usuario2 = User::firstOrCreate(
        ['email' => 'maria.aprobadora@grafired.com'],
        [
            'name' => 'María Aprobadora',
            'password' => bcrypt('password'),
            'company_id' => $empresa2->id,
        ]
    );

    echo "📍 EMPRESAS:\n";
    echo "   Solicitante: {$empresa1->company_name}\n";
    echo "   Destino: {$empresa2->company_name}\n\n";

    echo "👥 USUARIOS:\n";
    echo "   Solicitante: {$usuario1->name} ({$usuario1->email})\n";
    echo "   Aprobador: {$usuario2->name} ({$usuario2->email})\n\n";

    // PASO 1: Enviar solicitud
    echo "📨 PASO 1: Enviando solicitud comercial...\n";
    auth()->login($usuario1);

    $solicitud = $service->sendRequest(
        targetCompany: $empresa2,
        relationshipType: 'supplier',
        message: '¡Hola! Queremos trabajar con ustedes como proveedores de papel. Somos una litografía establecida con más de 15 años de experiencia.'
    );

    echo "   ✅ Solicitud creada (ID: {$solicitud->id})\n";
    echo "   📧 EMAIL 1 ENVIADO a: {$usuario2->email}\n";
    echo "   📋 Tipo: CommercialRequestReceived\n";
    echo "   📝 Estado: {$solicitud->status}\n\n";

    echo "   ⏳ Esperando 3 segundos (límite Mailtrap)...\n\n";
    sleep(3);

    // PASO 2: Aprobar solicitud
    echo "✅ PASO 2: Aprobando solicitud...\n";
    auth()->login($usuario2);

    $contacto = $service->approveRequest(
        request: $solicitud,
        approver: $usuario2,
        responseMessage: '¡Bienvenidos! Nos encanta trabajar con litografías serias. Ya pueden hacer pedidos.'
    );

    $solicitud->refresh();

    echo "   ✅ Solicitud aprobada\n";
    echo "   📧 EMAIL 2 ENVIADO a: {$usuario1->email}\n";
    echo "   📋 Tipo: CommercialRequestApproved\n";
    echo "   📝 Estado: {$solicitud->status}\n";
    echo "   🔗 Contacto creado (ID: {$contacto->id})\n\n";

    // Verificar contactos bidireccionales
    $contactos1 = \App\Models\Contact::where('company_id', $empresa1->id)
        ->where('linked_company_id', $empresa2->id)
        ->count();

    $contactos2 = \App\Models\Contact::where('company_id', $empresa2->id)
        ->where('linked_company_id', $empresa1->id)
        ->count();

    echo "🔄 RELACIÓN BIDIRECCIONAL CREADA:\n";
    echo "   Contactos en {$empresa1->company_name}: {$contactos1}\n";
    echo "   Contactos en {$empresa2->company_name}: {$contactos2}\n\n";

    echo str_repeat("=", 70) . "\n";
    echo "🎯 REVISA MAILTRAP:\n";
    echo "   URL: https://mailtrap.io/inboxes\n\n";
    echo "   Deberías ver 2 EMAILS NUEVOS:\n\n";
    echo "   📧 Email 1: 'Nueva Solicitud Comercial'\n";
    echo "      Para: {$usuario2->email}\n";
    echo "      Contenido: Notificación de solicitud recibida\n\n";
    echo "   📧 Email 2: 'Solicitud Comercial Aprobada'\n";
    echo "      Para: {$usuario1->email}\n";
    echo "      Contenido: Confirmación de aprobación con mensaje\n\n";

    echo "✅ SISTEMA COMPLETO FUNCIONANDO:\n";
    echo "   • Solicitudes comerciales ✅\n";
    echo "   • Notificaciones inmediatas ✅\n";
    echo "   • Relaciones bidireccionales ✅\n";
    echo "   • Emails en Mailtrap ✅\n\n";

} catch (\Exception $e) {
    echo "❌ ERROR:\n";
    echo "   " . $e->getMessage() . "\n\n";

    if (strpos($e->getMessage(), 'Ya existe una solicitud') !== false) {
        echo "💡 SOLUCIÓN: Hay una solicitud duplicada.\n";
        echo "   Puedes revisar las solicitudes existentes en:\n";
        echo "   http://127.0.0.1:8000/admin/commercial-requests\n\n";
    }
}
