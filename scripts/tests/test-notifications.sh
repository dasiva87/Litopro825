#!/bin/bash

echo "==================================================="
echo " PRUEBA DEL SISTEMA DE AUTO-MARCADO - GrafiRed 3.0"
echo "==================================================="
echo ""

echo "📊 ESTADO ACTUAL DE NOTIFICACIONES:"
php artisan tinker --execute="
echo 'Total: ' . \Illuminate\Notifications\DatabaseNotification::count() . PHP_EOL;
echo 'No leídas: ' . \Illuminate\Notifications\DatabaseNotification::whereNull('read_at')->count() . PHP_EOL;
echo 'Leídas: ' . \Illuminate\Notifications\DatabaseNotification::whereNotNull('read_at')->count() . PHP_EOL;
echo PHP_EOL;
echo 'Distribución por tipo:' . PHP_EOL;
\Illuminate\Notifications\DatabaseNotification::selectRaw('type, count(*) as total, SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread')
    ->groupBy('type')
    ->orderByDesc('total')
    ->get()
    ->each(function(\$item) {
        echo '  - ' . class_basename(\$item->type) . ': ' . \$item->total . ' (' . \$item->unread . ' sin leer)' . PHP_EOL;
    });
"

echo ""
echo "🔧 VERIFICACIONES DEL SISTEMA:"
echo ""

echo "1️⃣ JavaScript compilado:"
if grep -q "fi-dropdown-list-item" public/build/assets/app-*.js; then
    echo "   ✅ Código JavaScript compilado correctamente"
else
    echo "   ❌ Código JavaScript NO encontrado en bundle"
fi

echo ""
echo "2️⃣ Observer registrado:"
if grep -q "DatabaseNotificationObserver" app/Providers/AppServiceProvider.php; then
    echo "   ✅ Observer registrado en AppServiceProvider"
else
    echo "   ❌ Observer NO registrado"
fi

echo ""
echo "3️⃣ Rutas API:"
php artisan route:list | grep "notifications.mark-as-read" > /dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ Ruta de marcado registrada"
else
    echo "   ❌ Ruta de marcado NO encontrada"
fi

echo ""
echo "4️⃣ Hook de Filament:"
if grep -q "notifications-script" app/Providers/Filament/AdminPanelProvider.php; then
    echo "   ✅ Hook registrado en Filament"
else
    echo "   ❌ Hook NO registrado"
fi

echo ""
echo "==================================================="
echo " INSTRUCCIONES DE PRUEBA MANUAL"
echo "==================================================="
echo ""
echo "1. Abrir navegador en: http://127.0.0.1:8000/admin"
echo "2. Abrir DevTools (F12) → Pestaña Console"
echo "3. Buscar mensaje: 'GrafiRed: Sistema de auto-marcado de notificaciones cargado'"
echo "4. Click en el icono de notificaciones (🔔)"
echo "5. Verificar logs en consola al hacer click en una notificación"
echo "6. Recargar y verificar contador actualizado"
echo ""
echo "🧪 PARA PROBAR ENDPOINT MANUALMENTE:"
echo ""
echo "# Obtener primera notificación no leída:"
echo "php artisan tinker --execute=\"echo \Illuminate\Notifications\DatabaseNotification::whereNull('read_at')->first()->id;\""
echo ""
echo "# Luego ejecutar (reemplazar {ID} con el UUID obtenido):"
echo "curl -X POST http://127.0.0.1:8000/admin/notifications/{ID}/mark-as-read \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -H 'Accept: application/json' \\"
echo "     --cookie-jar cookies.txt \\"
echo "     --cookie cookies.txt"
echo ""
echo "==================================================="
