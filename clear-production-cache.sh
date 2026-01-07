#!/bin/bash

# Script para limpiar cachés en producción
# Ejecutar después de hacer deploy o cambios en vistas

echo "🧹 Limpiando cachés de producción..."
echo ""

# 1. Caché de configuración
echo "📋 Limpiando caché de configuración..."
php artisan config:clear
php artisan config:cache

# 2. Caché de rutas
echo "🛣️  Limpiando caché de rutas..."
php artisan route:clear
php artisan route:cache

# 3. Caché de vistas Blade
echo "👁️  Limpiando caché de vistas..."
php artisan view:clear

# 4. Caché de la aplicación
echo "💾 Limpiando caché de aplicación..."
php artisan cache:clear

# 5. Caché de eventos
echo "📢 Limpiando caché de eventos..."
php artisan event:clear
php artisan event:cache

# 6. Caché de Filament
echo "🎨 Limpiando caché de Filament..."
php artisan filament:cache-components

# 7. Optimizar autoloader de Composer
echo "🎼 Optimizando autoloader..."
composer dump-autoload --optimize

# 8. Optimizar aplicación
echo "⚡ Optimizando aplicación..."
php artisan optimize:clear
php artisan optimize

echo ""
echo "✅ ¡Todos los cachés han sido limpiados y regenerados!"
echo ""
echo "📝 Comandos ejecutados:"
echo "  - config:clear + config:cache"
echo "  - route:clear + route:cache"
echo "  - view:clear"
echo "  - cache:clear"
echo "  - event:clear + event:cache"
echo "  - filament:cache-components"
echo "  - composer dump-autoload --optimize"
echo "  - optimize:clear + optimize"
echo ""
echo "🚀 La aplicación está lista para producción"
