# 🚂 Deployment en Railway - GrafiRed 3.0

## ⚡ Solución Rápida: Vista no se actualiza

Si la vista de restablecer contraseña (o cualquier otra vista) no se actualiza en producción Railway:

### Opción 1: Comando Artisan (RECOMENDADO)

```bash
# En Railway CLI o usando Railway Shell
php artisan grafired:clear-cache --production
```

### Opción 2: Script Bash

```bash
./clear-production-cache.sh
```

### Opción 3: Comandos Manuales

```bash
php artisan optimize:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan filament:cache-components
php artisan optimize
```

---

## 🔧 Acceso a Railway Shell

### 1. Usando Railway CLI (Local)

```bash
# Instalar Railway CLI (una sola vez)
npm i -g @railway/cli

# Login
railway login

# Conectar al proyecto
railway link

# Ejecutar comandos
railway run php artisan grafired:clear-cache --production
```

### 2. Usando Railway Dashboard (Web)

1. Ve a [Railway Dashboard](https://railway.app/dashboard)
2. Selecciona tu proyecto GrafiRed
3. Click en el servicio (app)
4. Click en "Settings" → "Service Settings"
5. Busca "Start Command" o "Deploy Command"
6. Agregar comando post-deploy:

```bash
php artisan migrate --force && php artisan grafired:clear-cache --production
```

---

## 📋 Checklist Post-Deploy

Después de cada push a main/producción:

- [ ] Verificar que el build terminó correctamente
- [ ] Ejecutar `php artisan grafired:clear-cache --production`
- [ ] Verificar que las migraciones corrieron (si hay)
- [ ] Probar login en producción
- [ ] Probar restablecer contraseña
- [ ] Revisar logs por errores

---

## 🐛 Debugging en Railway

### Ver Logs en Tiempo Real:

```bash
# Con Railway CLI
railway logs

# O en Railway Dashboard
# Deployments → [Latest] → View Logs
```

### Verificar Variables de Entorno:

```bash
railway variables
```

### Variables Críticas:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-app.up.railway.app
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

# Cache (si usas Redis en Railway)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=...
REDIS_PASSWORD=...
REDIS_PORT=6379
```

---

## 🔄 Workflow de Deploy

### 1. Desarrollo Local

```bash
# Hacer cambios
git add .
git commit -m "feat: nueva funcionalidad"

# Probar localmente
php artisan serve
php artisan test
```

### 2. Push a Railway

```bash
# Push a main (auto-deploy)
git push origin main
```

### 3. Post-Deploy Automático

Configurar en Railway → Settings → Deploy:

```bash
# Start Command
php artisan migrate --force && php artisan grafired:clear-cache --production && php artisan serve --host=0.0.0.0 --port=$PORT
```

---

## 🚨 Troubleshooting Común

### Problema: "Class not found"

**Solución:**
```bash
composer dump-autoload --optimize
php artisan optimize:clear
```

### Problema: "View not found"

**Solución:**
```bash
php artisan view:clear
php artisan filament:cache-components
```

### Problema: "Route not found"

**Solución:**
```bash
php artisan route:clear
php artisan route:cache
```

### Problema: "Config value not updated"

**Solución:**
```bash
php artisan config:clear
php artisan config:cache
```

### Problema: Database connection failed

**Verificar:**
- Variables de entorno DB_* están correctas
- Base de datos MySQL está corriendo en Railway
- Network entre app y DB está configurada

---

## 📊 Monitoreo

### Métricas en Railway:

- CPU Usage
- Memory Usage
- Network Traffic
- Request Count
- Response Times

### Logs Importantes:

```bash
# Laravel Logs
railway run cat storage/logs/laravel.log

# Últimos 100 logs
railway run tail -100 storage/logs/laravel.log

# Logs en tiempo real
railway logs --tail
```

---

## 🎯 Comandos Útiles Personalizados

### `grafired:clear-cache`

Limpia todos los cachés de la aplicación:

```bash
# Desarrollo (solo limpiar)
php artisan grafired:clear-cache

# Producción (limpiar + optimizar)
php artisan grafired:clear-cache --production
```

### `grafired:setup-demo`

Crear datos de prueba:

```bash
php artisan grafired:setup-demo --fresh
```

### `grafired:fix-prices`

Recalcular precios de items:

```bash
php artisan grafired:fix-prices
```

---

## 📝 Notas Importantes

1. **Railway auto-deploya** cuando haces push a `main`
2. **No uses `php artisan down`** en producción sin avisar
3. **Siempre prueba migraciones** en staging primero
4. **Mantén backups** de la base de datos
5. **Monitorea logs** después de cada deploy

---

## 🆘 Soporte

Si algo no funciona:

1. Revisar logs: `railway logs`
2. Verificar variables: `railway variables`
3. Ejecutar: `php artisan grafired:clear-cache --production`
4. Redeploy: Railway Dashboard → Deployments → Redeploy

---

**Última actualización:** 06-Ene-2026
**Proyecto:** GrafiRed 3.0
**Railway:** https://railway.app
