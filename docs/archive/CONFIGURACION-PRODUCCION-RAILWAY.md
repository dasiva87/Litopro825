# 🚀 Configuración de Producción - Railway

## 🚨 Problema Resuelto: Error 403 al cambiar APP_ENV a production

### Causa:
Laravel en modo producción activa trusted proxies que bloquean Railway por defecto.

### Solución Aplicada:
✅ Middleware `TrustProxies.php` creado
✅ Bootstrap configurado con `trustProxies(at: '*')`

---

## ✅ Variables de Entorno CORRECTAS para Railway

Copia y pega estas en Railway Dashboard → Variables:

```env
# === APLICACIÓN ===
APP_NAME="GrafiRed"
APP_ENV=production
APP_KEY=base64:8wO1+zOXrekhS76Uh7/NyM+SuD+gvHqhbWQ5T+0fJZs=
APP_DEBUG=false
APP_URL=https://litopro825-production.up.railway.app
ASSET_URL=https://litopro825-production.up.railway.app

# === SEGURIDAD ===
FORCE_HTTPS=true

# === SESIONES (CRÍTICO PARA LOGOUT) ===
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# === BASE DE DATOS ===
DB_CONNECTION=mysql
DB_HOST=mysql.railway.internal
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=KRulbwneCCeMzTiYQaZaxlidzNhewSfJ

# === CACHÉ Y COLA ===
CACHE_STORE=database
QUEUE_CONNECTION=database

# === MAIL (Brevo) ===
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=dasiva87@gmail.com
MAIL_PASSWORD=C2fYQcVUgXn0yBRw
MAIL_FROM_ADDRESS=dasiva87@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# === LOGS ===
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=info

# === OTROS ===
BCRYPT_ROUNDS=12
PHP_CLI_SERVER_WORKERS=4
```

---

## 📋 Checklist de Deployment

### 1. ✅ Código Actualizado

```bash
cd /home/dasiva/Descargas/litopro825
git add .
git commit -m "fix: configurar trusted proxies para Railway (producción)"
git push origin main
```

### 2. ✅ Variables Actualizadas en Railway

- [ ] `APP_ENV=production` (ya no dará 403)
- [ ] `APP_DEBUG=false`
- [ ] `SESSION_DOMAIN=null`
- [ ] `SESSION_SECURE_COOKIE=true` (nuevo)
- [ ] `SESSION_HTTP_ONLY=true` (nuevo)
- [ ] `SESSION_SAME_SITE=lax` (nuevo)
- [ ] Sin barras finales en URLs

### 3. ✅ Después del Deploy

Espera 2-3 minutos a que Railway termine el build.

### 4. ✅ Limpiar Cachés (si tienes Railway CLI)

```bash
railway run php artisan grafired:clear-cache --production
```

**O simplemente espera** - el deploy limpia cachés automáticamente.

### 5. ✅ Testing

1. Abre en modo incógnito: `https://litopro825-production.up.railway.app/admin`
2. Haz login
3. Click en perfil → Logout
4. ✅ Debe cerrar sesión sin errores

---

## 🔧 Archivos Modificados

### 1. `app/Http/Middleware/TrustProxies.php` (NUEVO)
Middleware estándar de Laravel para confiar en proxies (Railway).

```php
protected $proxies = '*'; // Confiar en Railway, Cloudflare, etc.
```

### 2. `bootstrap/app.php` (ACTUALIZADO)
Registra el middleware de trusted proxies.

```php
$middleware->trustProxies(at: '*');
$middleware->web(prepend: [
    \App\Http\Middleware\TrustProxies::class,
]);
```

---

## 🎯 ¿Por Qué Funcionará Ahora?

### ANTES (con APP_ENV=production):
```
Usuario → Railway Proxy → Laravel
                           ↓
                        ❌ "No confío en este proxy"
                        ❌ CSRF token inválido
                        ❌ Error 403 FORBIDDEN
```

### AHORA (con TrustProxies):
```
Usuario → Railway Proxy → Laravel
                           ↓
                        ✅ "Confío en Railway proxy"
                        ✅ CSRF token válido
                        ✅ Logout exitoso
```

---

## 🐛 Debugging

### Ver logs en tiempo real:
```bash
railway logs --tail
```

### Ver configuración actual:
```bash
railway run php artisan tinker
>>> config('app.env')         // "production"
>>> config('session.domain')  // null
>>> config('app.url')          // https://...
>>> request()->server->get('HTTPS')  // "on"
```

### Ver rutas de logout:
```bash
railway run php artisan route:list | grep logout
```

Debe mostrar:
```
POST  admin/logout  filament.admin.auth.logout
```

---

## 🆘 Si Sigue Dando Error

### Error 403:
```bash
# Truncar sesiones
railway run php artisan tinker --execute="DB::table('sessions')->truncate();"

# Limpiar cachés
railway run php artisan config:clear
railway run php artisan cache:clear
```

### Error 405:
El problema de 405 ya debería estar resuelto con las rutas corregidas.

### Error 500:
```bash
railway logs --tail
```

Ver qué dice el log específicamente.

---

## ✅ Resumen

| Problema | Solución | Status |
|----------|----------|--------|
| Error 405 en logout | Rutas POST configuradas | ✅ Resuelto |
| Error 403 con APP_ENV=production | TrustProxies middleware | ✅ Resuelto |
| Doble hashing passwords | Removido Hash::make() | ✅ Resuelto |
| Vistas no actualizan | Comando clear-cache | ✅ Resuelto |
| Modal "cambios no guardados" | extraAttributes Alpine | ✅ Resuelto |

---

## 📊 Variables Clave Explicadas

### `SESSION_DOMAIN=null`
✅ Permite cookies en cualquier subdominio de Railway
❌ NO usar `.up.railway.app` (causaría problemas)

### `SESSION_SECURE_COOKIE=true`
✅ Cookies solo en HTTPS (Railway usa HTTPS siempre)

### `SESSION_HTTP_ONLY=true`
✅ JavaScript no puede acceder a cookies (seguridad XSS)

### `SESSION_SAME_SITE=lax`
✅ Balance entre seguridad y funcionalidad

### `APP_ENV=production`
✅ Activa optimizaciones y seguridad de Laravel
✅ Ahora funciona con TrustProxies configurado

---

## 🚀 Próximos Pasos

1. ✅ Hacer commit y push
2. ✅ Actualizar variables en Railway
3. ✅ Esperar deploy
4. ✅ Probar logout
5. ✅ Monitorear logs

**¡Todo debería funcionar correctamente ahora!** 🎉

---

**Última actualización:** 06-Ene-2026
**Versión:** 3.0.36
**Fix aplicado:** Trusted Proxies para Railway
