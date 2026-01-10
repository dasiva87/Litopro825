<?php

/**
 * Debug avanzado para Resources de Filament
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

echo "🔍 DEBUG AVANZADO DE RESOURCES\n";
echo "==============================\n\n";

try {
    // Simular autenticación
    $user = App\Models\User::first();
    if ($user) {
        auth()->login($user);
        echo "👤 Usuario autenticado: {$user->name}\n\n";
    } else {
        echo "⚠️  No hay usuarios en la base de datos\n\n";
    }

    // Verificar Resources directamente
    echo "🏗️ VERIFICANDO RESOURCES...\n";
    $resources = [
        'App\Filament\Resources\ClientResource',
        'App\Filament\Resources\SupplierResource', 
        'App\Filament\Resources\CommercialRequestResource'
    ];
    
    foreach ($resources as $resourceClass) {
        echo "📋 Testing {$resourceClass}:\n";
        
        try {
            // Verificar clase
            if (!class_exists($resourceClass)) {
                echo "   ❌ Clase no existe\n";
                continue;
            }
            echo "   ✅ Clase existe\n";
            
            // Verificar canViewAny
            $canView = $resourceClass::canViewAny();
            echo "   " . ($canView ? "✅" : "❌") . " canViewAny: " . ($canView ? "true" : "false") . "\n";
            
            // Verificar shouldRegisterNavigation
            $shouldRegister = method_exists($resourceClass, 'shouldRegisterNavigation') ? 
                $resourceClass::shouldRegisterNavigation() : true;
            echo "   " . ($shouldRegister ? "✅" : "❌") . " shouldRegisterNavigation: " . ($shouldRegister ? "true" : "false") . "\n";
            
            // Verificar NavigationLabel
            $navLabel = $resourceClass::getNavigationLabel();
            echo "   ✅ NavigationLabel: {$navLabel}\n";
            
            // Verificar NavigationGroup
            $navGroup = $resourceClass::getNavigationGroup();
            echo "   ✅ NavigationGroup: {$navGroup}\n";
            
            // Verificar NavigationSort
            $navSort = $resourceClass::getNavigationSort();
            echo "   ✅ NavigationSort: {$navSort}\n";
            
            // Verificar Modelo
            $model = $resourceClass::getModel();
            echo "   ✅ Model: {$model}\n";
            
            // Verificar Pages
            $pages = $resourceClass::getPages();
            echo "   ✅ Pages: " . count($pages) . " páginas\n";
            foreach ($pages as $pageName => $pageClass) {
                echo "      - {$pageName}: {$pageClass}\n";
                
                // Verificar que la clase de página existe
                $pageClassName = is_string($pageClass) ? $pageClass : get_class($pageClass);
                if (str_contains($pageClassName, '\\')) {
                    $actualClassName = explode('\\', $pageClassName);
                    $actualClassName = end($actualClassName);
                    $actualClassName = str_replace('::route(\'/\')', '', $actualClassName);
                    
                    // Buscar la clase real
                    $possibleClasses = [
                        "App\\Filament\\Pages\\Clients\\List{$actualClassName}",
                        "App\\Filament\\Pages\\Suppliers\\List{$actualClassName}",
                        "App\\Filament\\Pages\\CommercialRequests\\List{$actualClassName}",
                    ];
                    
                    foreach ($possibleClasses as $possibleClass) {
                        if (class_exists($possibleClass)) {
                            echo "        ✅ Page class exists: {$possibleClass}\n";
                            break;
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "   ❌ Error: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    echo "🌐 VERIFICANDO RUTAS...\n";
    $routes = ['admin/clientes', 'admin/proveedores', 'admin/solicitudes-comerciales'];
    foreach ($routes as $route) {
        try {
            $url = "http://127.0.0.1:8003/{$route}";
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 5,
                    'ignore_errors' => true
                ]
            ]);
            
            $result = @file_get_contents($url, false, $context);
            $httpCode = null;
            
            if (isset($http_response_header)) {
                foreach ($http_response_header as $header) {
                    if (preg_match('#HTTP/\d+\.\d+ (\d+)#', $header, $matches)) {
                        $httpCode = (int) $matches[1];
                        break;
                    }
                }
            }
            
            echo "   {$route}: " . ($httpCode ?: "Unknown") . "\n";
            
        } catch (Exception $e) {
            echo "   {$route}: Error - {$e->getMessage()}\n";
        }
    }

} catch (Exception $e) {
    echo "💥 ERROR CRÍTICO: {$e->getMessage()}\n";
    echo "Archivo: {$e->getFile()}:{$e->getLine()}\n";
}
?>