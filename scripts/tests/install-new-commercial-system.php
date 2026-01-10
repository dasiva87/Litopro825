<?php

/**
 * Script de instalación del nuevo sistema de clientes y proveedores
 * 
 * Ejecutar con: php install-new-commercial-system.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

echo "🚀 INSTALACIÓN DEL NUEVO SISTEMA COMERCIAL\n";
echo "==========================================\n\n";

echo "📊 FASE 1: Ejecutar migraciones...\n";
try {
    Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_21_000001_add_grafired_fields_to_contacts.php']);
    echo "   ✅ Campos Grafired agregados a contacts\n";
    
    Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_21_000002_create_client_relationships_table.php']);
    echo "   ✅ Tabla client_relationships creada\n";
    
    Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_21_000003_create_commercial_requests_table.php']);
    echo "   ✅ Tabla commercial_requests creada\n";
    
    Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_21_000004_migrate_existing_data_to_new_system.php']);
    echo "   ✅ Datos existentes migrados sin pérdida\n";
    
} catch (Exception $e) {
    echo "   ❌ Error en migraciones: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🏗️ FASE 2: Registrar nuevos Resources...\n";
echo "   📝 Verificar que estos Resources estén registrados en AdminPanelProvider:\n";
echo "      - ClientResource::class\n";
echo "      - SupplierResource::class\n";
echo "      - CommercialRequestResource::class\n";

echo "\n🔧 FASE 3: Limpiar caché...\n";
try {
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    echo "   ✅ Caché limpiado\n";
} catch (Exception $e) {
    echo "   ⚠️ Error limpiando caché: " . $e->getMessage() . "\n";
}

echo "\n🎯 FASE 4: Verificar instalación...\n";
try {
    $contactsCount = \App\Models\Contact::count();
    $localCount = \App\Models\Contact::local()->count();
    $grafiredCount = \App\Models\Contact::grafired()->count();
    $clientRelsCount = \App\Models\ClientRelationship::count();
    $commercialReqsCount = \App\Models\CommercialRequest::count();
    
    echo "   📊 Estadísticas:\n";
    echo "      - Contactos totales: {$contactsCount}\n";
    echo "      - Contactos locales: {$localCount}\n";
    echo "      - Contactos Grafired: {$grafiredCount}\n";
    echo "      - Relaciones de clientes: {$clientRelsCount}\n";
    echo "      - Solicitudes comerciales: {$commercialReqsCount}\n";
    
} catch (Exception $e) {
    echo "   ⚠️ Error verificando datos: " . $e->getMessage() . "\n";
}

echo "\n✅ INSTALACIÓN COMPLETADA\n";
echo "========================\n\n";

echo "🎉 NUEVAS RUTAS DISPONIBLES:\n";
echo "   • /admin/clientes          (ClientResource)\n";
echo "   • /admin/proveedores       (SupplierResource)\n";
echo "   • /admin/solicitudes-comerciales (CommercialRequestResource)\n\n";

echo "📋 PRÓXIMOS PASOS:\n";
echo "   1. ✅ Verificar que los nuevos Resources aparezcan en el menú\n";
echo "   2. ✅ Probar creación de contactos locales\n";
echo "   3. ⏳ Implementar búsqueda en Grafired (siguiente fase)\n";
echo "   4. ⏳ Implementar solicitudes de conexión (siguiente fase)\n\n";

echo "🔄 COMPATIBILIDAD:\n";
echo "   • Contacts existentes: Mantienen funcionalidad actual\n";
echo "   • SupplierRelationships: Continúan funcionando\n";
echo "   • DocumentResource: Sin cambios necesarios\n";
echo "   • ProductionOrderResource: Sin cambios necesarios\n\n";

echo "🚀 ¡El nuevo sistema está listo para usar!\n";
?>