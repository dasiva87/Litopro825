<?php

/**
 * Script de verificación del nuevo sistema de clientes y proveedores
 * 
 * Ejecutar con: php test-new-system.php
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "🔍 VERIFICACIÓN DEL SISTEMA COMERCIAL\n";
echo "=====================================\n\n";

echo "📊 1. VERIFICANDO DATOS MIGRADOS...\n";
try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->boot();

    $contactsTotal = \App\Models\Contact::count();
    $contactsLocal = \App\Models\Contact::local()->count();
    $contactsGrafired = \App\Models\Contact::grafired()->count();
    $clientes = \App\Models\Contact::customers()->count();
    $proveedores = \App\Models\Contact::suppliers()->count();
    $clientRels = \App\Models\ClientRelationship::count();
    $commercialReqs = \App\Models\CommercialRequest::count();
    
    echo "   ✅ Contactos totales: {$contactsTotal}\n";
    echo "   ✅ Contactos locales: {$contactsLocal}\n";
    echo "   ✅ Contactos Grafired: {$contactsGrafired}\n";
    echo "   ✅ Clientes: {$clientes}\n";
    echo "   ✅ Proveedores: {$proveedores}\n";
    echo "   ✅ Relaciones cliente: {$clientRels}\n";
    echo "   ✅ Solicitudes comerciales: {$commercialReqs}\n";
    
} catch (Exception $e) {
    echo "   ❌ Error verificando datos: " . $e->getMessage() . "\n";
}

echo "\n🏗️ 2. VERIFICANDO RESOURCES...\n";
try {
    $resourceFiles = [
        'app/Filament/Resources/ClientResource.php',
        'app/Filament/Resources/SupplierResource.php', 
        'app/Filament/Resources/CommercialRequestResource.php'
    ];
    
    foreach ($resourceFiles as $file) {
        if (file_exists($file)) {
            echo "   ✅ {$file}\n";
        } else {
            echo "   ❌ {$file} NO ENCONTRADO\n";
        }
    }
    
    $classes = [
        'App\Filament\Resources\ClientResource',
        'App\Filament\Resources\SupplierResource',
        'App\Filament\Resources\CommercialRequestResource'
    ];
    
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "   ✅ Clase {$class} cargada\n";
        } else {
            echo "   ❌ Clase {$class} NO ENCONTRADA\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Error verificando Resources: " . $e->getMessage() . "\n";
}

echo "\n🌐 3. VERIFICANDO RUTAS...\n";
try {
    $routes = [
        'admin/clientes',
        'admin/proveedores',
        'admin/solicitudes-comerciales'
    ];
    
    // Ejecutar comando para verificar rutas
    $output = shell_exec('php artisan route:list --name="filament.admin.resources" | grep -E "(client|supplier|commercial)"');
    $routeLines = explode("\n", trim($output));
    
    foreach ($routes as $route) {
        $found = false;
        foreach ($routeLines as $line) {
            if (strpos($line, str_replace('-', '', $route)) !== false) {
                echo "   ✅ Ruta /{$route} registrada\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "   ❌ Ruta /{$route} NO encontrada\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Error verificando rutas: " . $e->getMessage() . "\n";
}

echo "\n📋 4. VERIFICANDO PÁGINAS...\n";
try {
    $pageFiles = [
        'app/Filament/Pages/Clients/ListClients.php',
        'app/Filament/Pages/Suppliers/ListSuppliers.php',
        'app/Filament/Pages/CommercialRequests/ListCommercialRequests.php'
    ];
    
    foreach ($pageFiles as $file) {
        if (file_exists($file)) {
            echo "   ✅ {$file}\n";
        } else {
            echo "   ❌ {$file} NO ENCONTRADO\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Error verificando páginas: " . $e->getMessage() . "\n";
}

echo "\n🎯 RESULTADO FINAL\n";
echo "==================\n";

$allGood = true;

// Verificar archivos críticos
$criticalFiles = [
    'app/Filament/Resources/ClientResource.php',
    'app/Filament/Resources/SupplierResource.php',
    'app/Filament/Resources/CommercialRequestResource.php'
];

foreach ($criticalFiles as $file) {
    if (!file_exists($file)) {
        $allGood = false;
        break;
    }
}

if ($allGood && $contactsTotal > 0) {
    echo "🎉 ¡SISTEMA FUNCIONANDO CORRECTAMENTE!\n\n";
    echo "📍 Servidor disponible en: http://127.0.0.1:8003\n";
    echo "🔗 URLs para probar:\n";
    echo "   • Login: http://127.0.0.1:8003/admin/login\n";
    echo "   • Clientes: http://127.0.0.1:8003/admin/clientes\n";
    echo "   • Proveedores: http://127.0.0.1:8003/admin/proveedores\n";
    echo "   • Solicitudes: http://127.0.0.1:8003/admin/solicitudes-comerciales\n\n";
    echo "⚠️  IMPORTANTE: Debes iniciar sesión primero para acceder a las URLs\n";
} else {
    echo "⚠️  HAY PROBLEMAS EN EL SISTEMA\n";
    echo "   Revisar los errores mostrados arriba\n";
}

echo "\n🔄 Para reiniciar el servidor:\n";
echo "   php artisan serve --port=8003\n\n";

?>