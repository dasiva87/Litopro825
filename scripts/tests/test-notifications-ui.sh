#!/bin/bash

echo "======================================================================"
echo "  PRUEBA DE NOTIFICACIONES EN FILAMENT v4 - GrafiRed 3.0"
echo "======================================================================"
echo ""
echo "🌐 SERVIDOR CORRIENDO EN:"
echo "   http://127.0.0.1:8000/admin"
echo ""
echo "======================================================================"
echo "  PASO 1: VERIFICAR NOTIFICACIONES EN BASE DE DATOS"
echo "======================================================================"
echo ""

php artisan tinker --execute="
\$user = \App\Models\User::first();
echo '👤 Usuario: ' . \$user->name . ' (ID: ' . \$user->id . ')' . PHP_EOL;
echo '📧 Total notificaciones: ' . \$user->notifications()->count() . PHP_EOL;
echo '🔴 No leídas: ' . \$user->unreadNotifications()->count() . PHP_EOL;
echo '✅ Leídas: ' . \$user->notifications()->whereNotNull('read_at')->count() . PHP_EOL;
echo PHP_EOL;

echo '📋 Últimas 3 notificaciones:' . PHP_EOL;
\$user->notifications()->latest()->take(3)->get()->each(function(\$n) {
    \$data = \$n->data;
    echo '  • ' . (\$data['title'] ?? 'Sin título') . PHP_EOL;
    echo '    ' . (\$data['body'] ?? 'Sin mensaje') . PHP_EOL;
    echo '    Estado: ' . (\$n->read_at ? '✅ Leída' : '🔴 No leída') . PHP_EOL;
    echo PHP_EOL;
});
"

echo ""
echo "======================================================================"
echo "  PASO 2: INSTRUCCIONES DE PRUEBA EN NAVEGADOR"
echo "======================================================================"
echo ""
echo "1️⃣  Abrir navegador en: http://127.0.0.1:8000/admin"
echo ""
echo "2️⃣  Abrir DevTools (F12) → Pestaña Console"
echo ""
echo "3️⃣  Buscar mensajes de GrafiRed:"
echo "    ✅ 'GrafiRed: Sistema de auto-marcado de notificaciones cargado'"
echo "    ✅ 'GrafiRed: {N} notificaciones encontradas con selector: ...'"
echo ""
echo "4️⃣  Click en el icono de notificaciones (🔔) en la esquina superior derecha"
echo ""
echo "5️⃣  VERIFICAR que aparezcan las 4 notificaciones:"
echo "    • Sistema de Notificaciones Actualizado"
echo "    • ✅ Orden de Pedido Creada"
echo "    • ⚠️ Stock Bajo"
echo "    • 💼 Nueva Solicitud Comercial"
echo ""
echo "6️⃣  Click en cualquier notificación"
echo ""
echo "7️⃣  Verificar en Console:"
echo "    ✅ 'GrafiRed: Notificación {uuid} marcada como leída'"
echo "    ✅ Badge del contador debe decrementar"
echo ""
echo "8️⃣  Recargar la página (F5)"
echo ""
echo "9️⃣  Verificar que el contador muestre 3 (una menos)"
echo ""
echo "🔟  Repetir con otra notificación"
echo ""
echo "======================================================================"
echo "  PASO 3: PRUEBAS ADICIONALES"
echo "======================================================================"
echo ""
echo "🧪 MARCAR TODAS COMO LEÍDAS (desde consola del navegador):"
echo "   window.markAllNotificationsAsRead()"
echo ""
echo "🧪 CREAR NUEVA NOTIFICACIÓN (desde terminal):"
echo "   php artisan tinker"
echo "   >>> \$user = User::first()"
echo "   >>> \$user->notify(new class extends Notification {"
echo "         public function via(\$n) { return ['database']; }"
echo "         public function toArray(\$n) {"
echo "           return ['format' => 'filament', 'title' => 'Test', 'body' => 'Prueba'];"
echo "         }"
echo "       });"
echo ""
echo "🧪 VERIFICAR NOTIFICACIONES MARCADAS:"
echo ""

php artisan tinker --execute="
\$user = \App\Models\User::first();
echo 'Estado actual:' . PHP_EOL;
echo 'No leídas: ' . \$user->unreadNotifications()->count() . PHP_EOL;
echo 'Leídas: ' . \$user->notifications()->whereNotNull('read_at')->count() . PHP_EOL;
"

echo ""
echo "======================================================================"
echo "  COMANDOS ÚTILES"
echo "======================================================================"
echo ""
echo "📊 Ver estado de notificaciones:"
echo "   ./test-notifications-ui.sh"
echo ""
echo "🧹 Limpiar todas las notificaciones:"
echo "   php artisan tinker --execute=\"\\Illuminate\\Notifications\\DatabaseNotification::query()->delete();\""
echo ""
echo "📝 Ver logs del servidor:"
echo "   tail -f /tmp/grafired-server.log"
echo ""
echo "🔄 Reiniciar servidor:"
echo "   pkill -f 'php artisan serve' && php artisan serve --port=8000"
echo ""
echo "======================================================================"
