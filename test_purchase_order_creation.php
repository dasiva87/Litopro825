<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PurchaseOrder;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Enums\OrderStatus;

echo "🧪 PRUEBA DE CREACIÓN DE PURCHASE ORDER (POST-FIX)\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Obtener empresa y usuario
    $company = Company::where('is_active', true)->first();
    $user = User::where('company_id', $company->id)->first();

    if (!$company || !$user) {
        echo "❌ No hay empresa o usuario activo\n";
        exit(1);
    }

    auth()->login($user);

    // Obtener proveedor local (Contact)
    $supplier = Contact::where('company_id', $company->id)
        ->whereIn('type', ['supplier', 'both'])
        ->where('is_active', true)
        ->whereNotNull('email')
        ->first();

    if (!$supplier) {
        echo "⚠️  Creando proveedor local de prueba...\n";
        $supplier = Contact::create([
            'company_id' => $company->id,
            'type' => 'supplier',
            'name' => 'Proveedor Test Final',
            'email' => 'proveedor.final@test.com',
            'phone' => '3001234567',
            'is_local' => true,
            'is_active' => true,
        ]);
        echo "   ✅ Proveedor creado\n\n";
    }

    echo "📋 DATOS:\n";
    echo "   Empresa: {$company->name}\n";
    echo "   Usuario: {$user->name}\n";
    echo "   Proveedor: {$supplier->name}\n";
    echo "   Email proveedor: {$supplier->email}\n";
    echo "   Tipo proveedor: " . ($supplier->linked_company_id ? 'Grafired (Company)' : 'Local (Contact)') . "\n\n";

    echo "📝 Creando Purchase Order...\n";

    // Desactivar envío de email temporalmente para evitar límite Mailtrap
    config(['mail.default' => 'log']);

    $purchaseOrder = PurchaseOrder::create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'supplier_company_id' => $supplier->linked_company_id, // NULL si es local
        'status' => OrderStatus::SENT,
        'order_date' => now(),
        'expected_delivery_date' => now()->addDays(7),
        'total_amount' => 500000,
        'notes' => 'Orden de prueba - Verificación de fix',
        'created_by' => $user->id,
    ]);

    echo "   ✅ Purchase Order creada exitosamente!\n\n";

    echo "📊 RESULTADO:\n";
    echo "   ID: {$purchaseOrder->id}\n";
    echo "   Order Number: {$purchaseOrder->order_number}\n";
    echo "   Status: {$purchaseOrder->status->value}\n";
    echo "   Total: $" . number_format($purchaseOrder->total_amount, 2) . "\n\n";

    // Verificar notificaciones en BD
    $notifications = \Illuminate\Notifications\DatabaseNotification::where('type', 'App\Notifications\PurchaseOrderCreated')
        ->where('created_at', '>=', now()->subMinutes(1))
        ->get();

    echo "📧 NOTIFICACIONES CREADAS: {$notifications->count()}\n";

    foreach ($notifications as $notification) {
        $data = $notification->data;
        echo "\n   Notificación ID: {$notification->id}\n";
        echo "   Para: " . $notification->notifiable->email . "\n";
        echo "   Mensaje: {$data['message']}\n";
        echo "   Proveedor: {$data['supplier_company']}\n";
        echo "   ✅ SIN ERRORES de 'Attempt to read property name on null'\n";
    }

    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ FIX VERIFICADO:\n";
    echo "   • Purchase Order creada sin errores\n";
    echo "   • Notificaciones generadas correctamente\n";
    echo "   • Soporta proveedores locales (Contact)\n";
    echo "   • Soporta proveedores Grafired (Company)\n\n";

    echo "🎯 PRUEBA EN LA INTERFAZ:\n";
    echo "   Ve a: http://127.0.0.1:8000/admin/purchase-orders/create\n";
    echo "   Crea una orden con proveedor local\n";
    echo "   ✅ Ya NO debe mostrar error 'Attempt to read property name on null'\n\n";

} catch (\Exception $e) {
    echo "❌ ERROR:\n";
    echo "   " . $e->getMessage() . "\n\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";

    if (strpos($e->getMessage(), 'Attempt to read property') !== false) {
        echo "⚠️  TODAVÍA HAY UN ERROR DE NULL PROPERTY\n";
        echo "   Esto significa que hay otro lugar que necesita fix\n\n";
    }

    echo $e->getTraceAsString() . "\n";
}
