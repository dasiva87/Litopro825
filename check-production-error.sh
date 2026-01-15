#!/bin/bash

echo "🔍 Verificando errores en modo producción..."
echo ""

# Backup del .env actual
cp .env .env.backup-$(date +%Y%m%d-%H%M%S)

# Activar debug temporalmente para ver el error
sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env

echo "✅ Debug activado temporalmente"
echo ""
echo "Ahora:"
echo "1. Accede a la URL que da 403"
echo "2. Copia el mensaje de error completo"
echo "3. Ejecuta: bash restore-production.sh"
echo ""

cat > restore-production.sh << 'RESTORE'
#!/bin/bash
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
php artisan config:clear
php artisan cache:clear
echo "✅ Modo producción restaurado con debug=false"
RESTORE

chmod +x restore-production.sh

