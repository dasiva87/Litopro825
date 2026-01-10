<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CommercialRequest;
use App\Models\User;
use App\Services\CommercialRequestService;

echo "🚀 Probando Aprobación de Solicitud Comercial\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$requestId = $argv[1] ?? null;

if (!$requestId) {
    echo "❌ Error: Debes proporcionar el ID de la solicitud\n";
    echo "Uso: php test_approve_request.php <request_id>\n";
    exit(1);
}

try {
    $request = CommercialRequest::with(['requesterCompany', 'targetCompany'])->findOrFail($requestId);

    echo "📋 Solicitud Encontrada:\n";
    echo "   - ID: {$request->id}\n";
    echo "   - Solicitante: {$request->requesterCompany->company_name}\n";
    echo "   - Destino: {$request->targetCompany->company_name}\n";
    echo "   - Estado actual: {$request->status}\n";
    echo "   - Tipo: " . ($request->relationship_type === 'supplier' ? 'Proveedor' : 'Cliente') . "\n\n";

    if ($request->status !== 'pending') {
        echo "⚠️  La solicitud ya fue procesada (estado: {$request->status})\n";
        exit(0);
    }

    // Obtener usuario de la empresa destino
    $approver = User::where('company_id', $request->target_company_id)->first();

    if (!$approver) {
        echo "❌ Error: No se encontró usuario de la empresa destino\n";
        exit(1);
    }

    // Simular autenticación del usuario que aprueba
    auth()->login($approver);

    echo "👤 Aprobador: {$approver->name} ({$approver->email})\n\n";

    // Aprobar solicitud usando el servicio
    echo "✅ Aprobando solicitud...\n";

    $service = app(CommercialRequestService::class);

    $contact = $service->approveRequest(
        request: $request,
        approver: $approver,
        responseMessage: '¡Bienvenido a nuestra red comercial! Estamos listos para trabajar juntos.'
    );

    echo "✅ Solicitud aprobada exitosamente!\n\n";

    echo "📋 Resultados:\n";
    echo "   - Estado: {$request->fresh()->status}\n";
    echo "   - Contacto creado en Solicitante: ID {$contact->id}\n";
    echo "   - Empresa vinculada: {$contact->linkedCompany->company_name}\n";
    echo "   - Tipo de contacto: {$contact->type}\n\n";

    // Verificar contacto bidireccional
    $reciprocalContact = \App\Models\Contact::where('company_id', $request->target_company_id)
        ->where('linked_company_id', $request->requester_company_id)
        ->first();

    if ($reciprocalContact) {
        echo "🔄 Contacto recíproco creado:\n";
        echo "   - En empresa: {$request->targetCompany->company_name}\n";
        echo "   - Vinculado a: {$reciprocalContact->linkedCompany->company_name}\n";
        echo "   - Tipo: {$reciprocalContact->type}\n\n";
    }

    echo "📧 Notificaciones enviadas:\n";
    echo "   1. A solicitante ({$request->requesterCompany->users->first()->email}):\n";
    echo "      - Tipo: CommercialRequestApproved\n";
    echo "      - Asunto: 'Solicitud Comercial Aprobada'\n\n";

    echo "🎯 ACCIÓN REQUERIDA:\n";
    echo "   1. Ve a Mailtrap: https://mailtrap.io/inboxes\n";
    echo "   2. Deberías ver un NUEVO email con:\n";
    echo "      - Asunto: 'Solicitud Comercial Aprobada'\n";
    echo "      - Para: {$request->requesterCompany->users->first()->email}\n";
    echo "      - Mensaje de respuesta incluido\n\n";

    echo "✅ Ahora ambas empresas están conectadas en la red Grafired!\n";

} catch (\Exception $e) {
    echo "❌ Error:\n";
    echo $e->getMessage() . "\n";
    echo "\n" . $e->getTraceAsString() . "\n";
}
