<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PurchaseOrder;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Notifications\PurchaseOrderCreated;
use Illuminate\Support\Facades\Notification;

echo "🧪 PRUEBA DE EMAIL DE ORDEN DE PEDIDO\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Obtener o crear empresas de prueba
    $clientCompany = Company::where('is_active', true)->first();

    if (!$clientCompany) {
        echo "❌ No hay empresas activas\n";
        exit(1);
    }

    // Obtener un proveedor (Contact con tipo supplier o both)
    $supplier = Contact::where('company_id', $clientCompany->id)
        ->whereIn('type', ['supplier', 'both'])
        ->where('is_active', true)
        ->first();

    if (!$supplier) {
        echo "⚠️  No hay proveedores. Creando proveedor de prueba...\n";

        $supplier = Contact::create([
            'company_id' => $clientCompany->id,
            'type' => 'supplier',
            'name' => 'Proveedor de Prueba Email',
            'email' => 'proveedor.test@email.com',
            'phone' => '3001234567',
            'address' => 'Calle Prueba 123',
            'is_local' => true,
            'is_active' => true,
        ]);

        echo "   ✅ Proveedor creado: {$supplier->name} ({$supplier->email})\n\n";
    }

    // Obtener o crear usuario
    $user = User::where('company_id', $clientCompany->id)->first();

    if (!$user) {
        echo "❌ No hay usuarios en la empresa\n";
        exit(1);
    }

    auth()->login($user);

    echo "📋 DATOS DE LA PRUEBA:\n";
    echo "   Empresa cliente: {$clientCompany->name}\n";
    echo "   Usuario: {$user->name} ({$user->email})\n";
    echo "   Proveedor: {$supplier->name}\n";
    echo "   Email proveedor: {$supplier->email}\n\n";

    // Verificar si el proveedor tiene email
    if (!$supplier->email) {
        echo "❌ El proveedor NO tiene email configurado!\n";
        echo "   Esto es probablemente por qué no se envió el email.\n\n";
        echo "💡 SOLUCIÓN: Asigna un email al proveedor en:\n";
        echo "   http://127.0.0.1:8000/admin/contacts\n\n";
        exit(1);
    }

    // Verificar si hay proveedor con linked_company_id (Grafired)
    if ($supplier->linked_company_id) {
        $linkedCompany = Company::find($supplier->linked_company_id);
        if ($linkedCompany) {
            echo "🌐 PROVEEDOR GRAFIRED DETECTADO:\n";
            echo "   Proveedor vinculado a: {$linkedCompany->name}\n";
            echo "   Email empresa: {$linkedCompany->email}\n\n";

            if ($linkedCompany->email) {
                echo "📧 El email se enviará a:\n";
                echo "   1. Email del proveedor: {$supplier->email}\n";
                echo "   2. Email de la empresa: {$linkedCompany->email}\n\n";
            }
        }
    }

    // Crear PurchaseOrder de prueba
    echo "📝 Creando Purchase Order de prueba...\n";

    $purchaseOrder = PurchaseOrder::create([
        'company_id' => $clientCompany->id,
        'supplier_id' => $supplier->id,
        'supplier_company_id' => $supplier->linked_company_id, // Puede ser null si es local
        'status' => OrderStatus::SENT, // ← IMPORTANTE: debe ser SENT para enviar email
        'order_date' => now(),
        'expected_delivery_date' => now()->addDays(7),
        'total_amount' => 1500000,
        'notes' => 'Orden de prueba para verificar envío de emails',
        'created_by' => $user->id,
    ]);

    echo "   ✅ Purchase Order creada: #{$purchaseOrder->order_number}\n";
    echo "   Estado: {$purchaseOrder->status->value}\n";
    echo "   Total: $" . number_format($purchaseOrder->total_amount, 2) . "\n\n";

    // Verificar si se envió el email
    echo "📊 VERIFICANDO ENVÍO...\n";

    // Verificar notificaciones en BD
    $dbNotifications = \Illuminate\Notifications\DatabaseNotification::where('type', 'App\Notifications\PurchaseOrderCreated')
        ->where('created_at', '>=', now()->subMinutes(1))
        ->get();

    echo "   Notificaciones en BD: {$dbNotifications->count()}\n";

    foreach ($dbNotifications as $notification) {
        $notifiable = $notification->notifiable;
        echo "   - Notificado: {$notifiable->email} (tipo: " . class_basename($notifiable) . ")\n";
    }

    echo "\n";

    // Análisis del código
    echo "🔍 ANÁLISIS:\n";
    echo "   El modelo PurchaseOrder::booted() hace lo siguiente:\n\n";

    echo "   1. static::created (líneas 64-81):\n";
    echo "      - SI status === SENT Y supplierCompany->email existe:\n";
    echo "        → Notification::route('mail', email)->notify(PurchaseOrderCreated)\n";
    echo "      - Notifica a usuarios de la empresa creadora\n\n";

    echo "   2. static::updating (líneas 106-119):\n";
    echo "      - SI status cambia a SENT:\n";
    echo "        → Notifica a usuarios del proveedor (si supplier_company_id existe)\n";
    echo "        → Email al supplierCompany->email\n\n";

    echo "📋 VERIFICACIÓN ACTUAL:\n";
    echo "   - Status: {$purchaseOrder->status->value}\n";
    echo "   - supplier_id: {$purchaseOrder->supplier_id}\n";
    echo "   - supplier_company_id: " . ($purchaseOrder->supplier_company_id ?? 'NULL') . "\n";
    echo "   - supplierCompany: " . ($purchaseOrder->supplierCompany ? $purchaseOrder->supplierCompany->name : 'NULL') . "\n";
    echo "   - supplierCompany->email: " . ($purchaseOrder->supplierCompany->email ?? 'NULL') . "\n\n";

    if (!$purchaseOrder->supplier_company_id) {
        echo "⚠️  PROBLEMA ENCONTRADO:\n";
        echo "   supplier_company_id es NULL\n";
        echo "   Esto significa que es un proveedor LOCAL (Contact), no Grafired (Company)\n\n";

        echo "   El código actual SOLO envía email si:\n";
        echo "   - supplierCompany existe (supplier_company_id no es null)\n";
        echo "   - Y supplierCompany->email tiene valor\n\n";

        echo "💡 SOLUCIÓN:\n";
        echo "   Para proveedores locales (Contact), debemos enviar al Contact->email\n";
        echo "   Voy a enviar el email manualmente para probar:\n\n";

        // Enviar email manualmente al contact
        if ($supplier->email) {
            echo "📧 Enviando email al proveedor local...\n";

            Notification::route('mail', $supplier->email)
                ->notify(new PurchaseOrderCreated($purchaseOrder->id));

            echo "   ✅ Email enviado a: {$supplier->email}\n\n";
        }
    }

    echo str_repeat("=", 70) . "\n";
    echo "🎯 REVISA MAILTRAP:\n";
    echo "   URL: https://mailtrap.io/inboxes\n";
    echo "   Busca email con asunto: 'Nueva Orden de Pedido #{$purchaseOrder->order_number}'\n\n";

    echo "✅ Purchase Order ID: {$purchaseOrder->id}\n";
    echo "✅ Order Number: {$purchaseOrder->order_number}\n\n";

} catch (\Exception $e) {
    echo "❌ ERROR:\n";
    echo "   " . $e->getMessage() . "\n\n";
    echo $e->getTraceAsString() . "\n";
}
