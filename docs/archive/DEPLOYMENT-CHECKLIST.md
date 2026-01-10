# ✅ Checklist de Deployment - GrafiRed 3.0

## 🚨 Problema: Vistas no se actualizan en producción

### Causas Comunes

1. **Caché de vistas Blade** - Laravel cachea las vistas compiladas
2. **Caché de configuración** - Los cambios en config files no se reflejan
3. **Caché de rutas** - Rutas no actualizadas
4. **Caché de aplicación** - Datos cacheados obsoletos
5. **Autoloader de Composer** - Clases nuevas no detectadas
6. **OPcache de PHP** - Código PHP cacheado en memoria
7. **Caché de CDN/Proxy** - Railway o Cloudflare cacheando assets

---

## 🛠️ Soluciones por Orden de Prioridad

### 1️⃣ Limpieza Completa de Cachés (RECOMENDADO)

Ejecutar el script automatizado:

```bash
# En el servidor de producción (Railway)
./clear-production-cache.sh
```

**O manualmente:**

```bash
# Limpiar todos los cachés
php artisan optimize:clear

# Cachear para producción
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan filament:cache-components
php artisan optimize

# Optimizar autoloader
composer dump-autoload --optimize
```

### 2️⃣ Reiniciar Servicios (Railway)

Si Railway usa contenedores, reiniciar el deployment:

```bash
# En Railway Dashboard:
# Settings → Deployments → Trigger Redeploy
```

### 3️⃣ Verificar Variables de Entorno

Asegurarse de que `APP_ENV=production` en Railway:

```bash
# En Railway Dashboard:
# Variables → APP_ENV → production
# Variables → APP_DEBUG → false
```

### 4️⃣ Limpiar OPcache de PHP (si aplica)

Agregar en `public/index.php` temporalmente:

```php
// SOLO PARA DEBUG - REMOVER DESPUÉS
if (function_exists('opcache_reset')) {
    opcache_reset();
}
```

### 5️⃣ Verificar Assets Compilados

Si usas Vite/NPM:

```bash
npm run build
git add public/build -f
git commit -m "Update compiled assets"
git push
```

---

## 📋 Comandos Post-Deployment

### Después de cada deploy a producción, ejecutar:

```bash
# 1. Limpiar cachés antiguos
php artisan optimize:clear
php artisan view:clear
php artisan cache:clear

# 2. Regenerar cachés optimizados
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan filament:cache-components

# 3. Optimizar aplicación
php artisan optimize
composer dump-autoload --optimize

# 4. Migrar base de datos (si hay cambios)
php artisan migrate --force

# 5. Verificar permisos
chmod -R 755 storage bootstrap/cache
```

---

## 🔍 Debugging en Producción

### Ver logs en Railway:

```bash
# En Railway Dashboard:
# Deployments → [Latest] → View Logs
```

### Verificar si los archivos se subieron:

```bash
# SSH a Railway (si está habilitado)
ls -la app/Filament/Pages/Auth/PasswordReset/
cat app/Filament/Pages/Auth/PasswordReset/RequestPasswordReset.php
```

### Verificar caché de vistas:

```bash
# Ver archivos cacheados
ls -la storage/framework/views/

# Limpiar específicamente vistas
rm -rf storage/framework/views/*
php artisan view:clear
```

---

## 🎯 Solución Específica: Vista Password Reset

### Si solo la vista de password reset no se actualiza:

```bash
# 1. Verificar que el archivo existe en producción
ls -la app/Filament/Pages/Auth/PasswordReset/RequestPasswordReset.php

# 2. Limpiar caché de vistas de Filament específicamente
php artisan view:clear
php artisan filament:cache-components

# 3. Verificar que está registrado en AdminPanelProvider
grep -n "RequestPasswordReset" app/Providers/Filament/AdminPanelProvider.php

# 4. Reiniciar PHP-FPM (si aplica)
# En Railway esto sucede automáticamente al redeploy
```

---

## 🚀 Script de Deploy Automatizado

Crear archivo `deploy.sh` en el proyecto:

```bash
#!/bin/bash

echo "🚀 Iniciando deployment..."

# 1. Git pull
git pull origin main

# 2. Composer
composer install --no-dev --optimize-autoloader

# 3. NPM (si aplica)
npm ci
npm run build

# 4. Laravel
php artisan down
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan filament:cache-components
php artisan optimize
php artisan up

echo "✅ Deployment completado!"
```

---

## 📝 Notas Railway Específicas

### Railway cachea el build, para forzar rebuild limpio:

1. Railway Dashboard → Settings
2. Scroll hasta "Danger Zone"
3. Click en "Delete Service Data"
4. Redeploy

### Variables de entorno importantes:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-app.up.railway.app

# Cachés deshabilitados en desarrollo
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### En producción Railway:

```env
APP_ENV=production
APP_DEBUG=false

# Cachés habilitados
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## 🆘 Troubleshooting Rápido

| Síntoma | Solución |
|---------|----------|
| Vista antigua se muestra | `php artisan view:clear` |
| Rutas no funcionan | `php artisan route:clear && php artisan route:cache` |
| Configuración no actualizada | `php artisan config:clear && php artisan config:cache` |
| Clases no encontradas | `composer dump-autoload --optimize` |
| Componentes Filament viejos | `php artisan filament:cache-components` |
| Nada funciona | `php artisan optimize:clear && php artisan optimize` |
| Todavía no funciona | Redeploy completo en Railway |

---

## ✅ Verificación Final

Después de aplicar fixes, verificar:

- [ ] Vista de login se muestra correctamente
- [ ] Vista de password reset se muestra correctamente
- [ ] Dashboard carga sin errores
- [ ] Logs no muestran errores (`tail -f storage/logs/laravel.log`)
- [ ] Assets (CSS/JS) se cargan correctamente
- [ ] Base de datos conecta correctamente

---

**Última actualización:** 06-Ene-2026
**Aplicación:** GrafiRed 3.0
**Stack:** Laravel 12.25 + Filament 4.0.3 + Railway
