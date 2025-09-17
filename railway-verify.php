<?php
/**
 * Railway Deploy Verification Script
 * Verificar que todos los componentes estén funcionando correctamente
 */

echo "\n🚀 LitoPro Railway Deploy Verification\n";
echo "=====================================\n\n";

// 1. Verificar archivos de build
echo "1. Assets Build Check:\n";
$manifestPath = __DIR__ . '/public/build/manifest.json';
if (file_exists($manifestPath)) {
    echo "   ✅ manifest.json exists\n";
    $manifest = json_decode(file_get_contents($manifestPath), true);
    foreach (['resources/css/app.css', 'resources/js/app.js'] as $file) {
        if (isset($manifest[$file])) {
            echo "   ✅ {$file} compiled\n";
        } else {
            echo "   ❌ {$file} missing\n";
        }
    }
} else {
    echo "   ❌ Build manifest missing - run 'npm run build'\n";
}

// 2. Verificar directorio storage
echo "\n2. Storage Permissions:\n";
$storageDirs = ['app', 'framework/cache', 'framework/sessions', 'framework/views', 'logs'];
foreach ($storageDirs as $dir) {
    $path = __DIR__ . "/storage/{$dir}";
    if (is_dir($path) && is_writable($path)) {
        echo "   ✅ storage/{$dir} writable\n";
    } else {
        echo "   ❌ storage/{$dir} not writable\n";
    }
}

// 3. Verificar configuración clave
echo "\n3. Configuration Check:\n";
$requiredEnvVars = [
    'APP_KEY' => 'Application key',
    'DB_CONNECTION' => 'Database connection',
    'CACHE_STORE' => 'Cache store',
    'SESSION_DRIVER' => 'Session driver'
];

foreach ($requiredEnvVars as $var => $description) {
    $value = $_ENV[$var] ?? getenv($var) ?? null;
    if ($value) {
        echo "   ✅ {$description} configured\n";
    } else {
        echo "   ❌ {$description} missing ({$var})\n";
    }
}

// 4. Verificar componentes críticos si Laravel está disponible
if (class_exists('Illuminate\Foundation\Application')) {
    echo "\n4. Laravel Components:\n";
    try {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        echo "   ✅ Laravel bootstrap successful\n";

        // Test database connection
        try {
            $pdo = new PDO(
                "mysql:host=" . ($_ENV['DB_HOST'] ?? getenv('DB_HOST')),
                $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME'),
                $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD')
            );
            echo "   ✅ Database connection successful\n";
        } catch (Exception $e) {
            echo "   ❌ Database connection failed\n";
        }

    } catch (Exception $e) {
        echo "   ❌ Laravel bootstrap failed: " . $e->getMessage() . "\n";
    }
}

echo "\n5. Recommended Railway Commands:\n";
echo "   Build: npm ci && npm run build && composer install --optimize-autoloader --no-dev\n";
echo "   Deploy: php artisan migrate --force && php artisan db:seed --class=ProductionSeeder\n";
echo "   Cache: php artisan config:cache && php artisan route:cache && php artisan view:cache\n";

echo "\n✅ Verification complete!\n";
echo "🌐 Access your app: https://your-domain.railway.app/admin\n\n";
?>