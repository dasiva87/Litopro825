# 🍪 Solución: Cookies Viejas Impiden Login

## 🚨 Problema:
- ✅ Incógnito: Login/Logout funciona
- ❌ Ventana Normal: No puede volver a entrar después de logout

## 🔍 Causa:
Cookies de sesión viejas con configuración antigua de `SESSION_DOMAIN` están en caché del navegador.

---

## ✅ Solución 1: Invalidar Todas las Sesiones (PRODUCCIÓN)

### Opción A: Con Railway CLI (si está instalado)

```bash
railway run php artisan grafired:invalidate-sessions --force
```

### Opción B: Sin Railway CLI

Agregar temporalmente al `nixpacks.toml`:

```toml
[start]
cmd = 'php artisan migrate --force && php artisan tinker --execute="DB::table(\"sessions\")->truncate();" && php artisan grafired:clear-cache --production && php artisan storage:link && php artisan serve --host=0.0.0.0 --port=${PORT}'
```

Luego hacer commit y push. Después del deploy, **revertir** y volver al comando normal.

### Opción C: Redeploy completo

En Railway Dashboard:
1. Deployments → Último deployment
2. Menu (⋮) → Redeploy

---

## ✅ Solución 2: Configuración de Cookies Mejorada

### Variables de Railway que DEBEN estar así:

```env
# CRÍTICO - Cookies deben renovarse
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# NUEVO - Forzar cookies seguras
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# NUEVO - Regenerar cookies en cada request
SESSION_REGENERATE=true
```

---

## ✅ Solución 3: Agregar Middleware de Limpieza de Cookies

Crear middleware que elimina cookies viejas automáticamente.

### Archivo: `app/Http/Middleware/ClearOldSessionCookies.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClearOldSessionCookies
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si hay cookies de sesión viejas, eliminarlas
        if ($request->hasCookie('laravel_session')) {
            $sessionId = $request->cookie('laravel_session');

            // Verificar si la sesión existe en BD
            $sessionExists = \DB::table('sessions')
                ->where('id', $sessionId)
                ->exists();

            if (!$sessionExists) {
                // Sesión no existe, limpiar cookie
                \Cookie::queue(\Cookie::forget('laravel_session'));
            }
        }

        return $next($request);
    }
}
```

---

## 🎯 Solución Inmediata AHORA (Para Usuarios)

### Para que los usuarios puedan entrar:

**Opción 1: Limpiar Cookies del Navegador**

**Chrome/Edge:**
```
1. chrome://settings/siteData
2. Buscar: litopro825-production.up.railway.app
3. Click "Eliminar"
4. F5 (Refresh)
```

**Firefox:**
```
1. Ctrl+Shift+Del
2. Cookies
3. Última hora
4. Limpiar
```

**Opción 2: Hard Refresh**
```
Ctrl+Shift+R (Windows/Linux)
Cmd+Shift+R (Mac)
```

**Opción 3: Usar Incógnito Temporalmente**
```
Mientras se soluciona el problema de cookies
```

---

## 🔧 Solución Técnica Permanente

### 1. Agregar al commit actual:

**Comando nuevo creado:**
```bash
php artisan grafired:invalidate-sessions --force
```

### 2. Ejecutar después del deploy:

```bash
railway run php artisan grafired:invalidate-sessions --force
```

Esto fuerza logout de TODOS los usuarios y elimina cookies viejas.

### 3. Usuarios hacen login nuevamente

Con las nuevas configuraciones de cookies, el problema no volverá a ocurrir.

---

## 📋 Checklist de Solución

**Inmediato:**
- [ ] Ejecutar `railway run php artisan grafired:invalidate-sessions --force`
- [ ] Notificar a usuarios que deben limpiar cookies o usar incógnito
- [ ] Verificar que variables de sesión estén correctas en Railway

**Preventivo:**
- [ ] Agregar `SESSION_REGENERATE=true` en Railway
- [ ] Documentar proceso de limpieza de cookies
- [ ] Considerar middleware de limpieza automática

---

## 💡 Por Qué Ocurre Esto

1. **Antes:** Cookie con `SESSION_DOMAIN=.up.railway.app` (incorrecto)
2. **Cambio:** Actualizaste a `SESSION_DOMAIN=null`
3. **Problema:** Navegador mantiene cookie vieja que apunta a sesión inexistente
4. **Incógnito:** No tiene cookies viejas → funciona
5. **Normal:** Tiene cookie vieja → conflicto

---

## ✅ Solución Aplicada

El comando `grafired:invalidate-sessions` elimina todas las sesiones de la BD, forzando que las cookies viejas sean inválidas y los usuarios deban crear nuevas sesiones con la configuración correcta.

---

**Última actualización:** 06-Ene-2026
**Comando creado:** `grafired:invalidate-sessions`
