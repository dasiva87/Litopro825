# 🔐 Fix: Problema de Login - Doble Hashing de Contraseñas

## 🚨 Problema Identificado

Los usuarios creados en producción **NO pueden hacer login** después de registrarse.

### Causa Raíz:
**Doble hashing de contraseñas** causado por:

1. **Laravel 11+**: El modelo `User` tiene `'password' => 'hashed'` en `casts()` que hashea automáticamente
2. **Código de registro**: Usaba `Hash::make($password)` manualmente
3. **Resultado**: La contraseña se hasheaba DOS veces, haciendo imposible el login

---

## ✅ Solución Aplicada

### Archivos Corregidos:

1. ✅ `app/Filament/Pages/Auth/Register.php`
2. ✅ `app/Http/Controllers/SimpleRegistrationController.php`
3. ✅ `app/Http/Controllers/RegistrationController.php`
4. ✅ `database/seeders/TestDataSeeder.php`
5. ✅ `database/seeders/DashboardDemoSeeder.php`
6. ✅ `database/factories/UserFactory.php`

### Cambio Realizado:

```php
// ❌ ANTES (doble hashing)
'password' => Hash::make($data['password'])

// ✅ AHORA (single hashing automático)
'password' => $data['password'] // El cast 'hashed' lo hashea automáticamente
```

---

## 🔧 Migración de Usuarios Existentes

### Opción 1: Resetear contraseñas manualmente (RECOMENDADO)

Los usuarios afectados deben usar "Olvidé mi contraseña" para resetear su password.

**Ventajas:**
- Seguro
- No requiere acceso a base de datos
- Los usuarios crean nuevas contraseñas fuertes

**Proceso:**
1. Usuario va a `/admin/login`
2. Click en "¿Olvidaste tu contraseña?"
3. Ingresa su email
4. Recibe link de reset
5. Crea nueva contraseña
6. ✅ Login funciona correctamente

---

### Opción 2: Script SQL para resetear passwords (TEMPORAL)

**⚠️ SOLO si tienes muchos usuarios afectados**

```sql
-- Ver usuarios afectados (creados antes del fix)
SELECT id, name, email, created_at
FROM users
WHERE created_at < '2026-01-07 00:00:00';

-- Resetear password a valor temporal conocido
-- NOTA: Estos passwords ya estarán correctamente hasheados por el modelo
UPDATE users
SET password = '$2y$12$...' -- Usar un hash bcrypt válido temporal
WHERE created_at < '2026-01-07 00:00:00';

-- O mejor: Forzar reset de password
UPDATE users
SET password = NULL, email_verified_at = NULL
WHERE created_at < '2026-01-07 00:00:00';
```

**⚠️ NO RECOMENDADO**: Mejor usar Opción 1

---

### Opción 3: Comando Artisan de Reseteo (SEGURO)

Crear un comando temporal para notificar a usuarios:

```bash
php artisan grafired:notify-password-reset
```

Este comando:
1. Lista usuarios afectados
2. Envía email automático con link de reset
3. Informa a cada usuario del cambio necesario

---

## 🧪 Testing del Fix

### 1. Crear nuevo usuario de prueba:

```bash
# Ir a /admin/register
# Crear cuenta con:
Email: test@ejemplo.com
Password: Test1234!
```

### 2. Hacer logout

### 3. Intentar login con mismas credenciales

```
Email: test@ejemplo.com
Password: Test1234!
```

### 4. Verificar resultado:

✅ **Login exitoso** = Fix funcionando
❌ **"Credenciales incorrectas"** = Problema persiste

---

## 📋 Checklist de Deployment

Después de hacer deploy del fix a producción:

- [ ] Hacer push del código corregido a Railway
- [ ] Esperar que el build complete
- [ ] Ejecutar: `railway run php artisan grafired:clear-cache --production`
- [ ] Verificar que no hay errores en logs
- [ ] Crear usuario de prueba nuevo
- [ ] Confirmar que el nuevo usuario puede hacer login
- [ ] Notificar a usuarios existentes que deben resetear password
- [ ] Proveer link de reset: `https://tu-app.railway.app/admin/password-reset/request`

---

## 🆘 Para Usuarios Afectados (Email Template)

```
Asunto: Actualización de Seguridad - Reset de Contraseña Requerido

Estimado/a usuario/a,

Hemos implementado una mejora de seguridad en GrafiRed que requiere que
restablezcas tu contraseña.

Por favor sigue estos pasos:

1. Ve a: https://tu-app.railway.app/admin/password-reset/request
2. Ingresa tu email: [tu-email]
3. Revisa tu bandeja de entrada
4. Click en el link de restablecimiento
5. Crea una nueva contraseña

Este cambio es necesario para mejorar la seguridad de tu cuenta.

Disculpa las molestias,
Equipo GrafiRed
```

---

## 🔍 Debugging

### Verificar hash de password en base de datos:

```sql
-- Ver hash actual de un usuario
SELECT id, email, LEFT(password, 20) as password_hash
FROM users
WHERE email = 'usuario@ejemplo.com';

-- Un hash bcrypt válido empieza con: $2y$10$ o $2y$12$
-- Si ves algo diferente, hay un problema
```

### Verificar que el cast está activo:

```bash
php artisan tinker
>>> $user = App\Models\User::first();
>>> $user->getCasts();
# Debe mostrar: ["password" => "hashed", ...]
```

### Crear usuario manualmente para testing:

```bash
php artisan tinker
>>> $user = new App\Models\User();
>>> $user->name = 'Test User';
>>> $user->email = 'test@test.com';
>>> $user->password = 'password123'; // Se hashea automáticamente
>>> $user->company_id = 1;
>>> $user->save();
>>> exit
```

Luego intentar login con:
- Email: test@test.com
- Password: password123

---

## 📊 Impacto

### Usuarios Afectados:
- ✅ Nuevos usuarios (después del fix): Login funciona
- ⚠️ Usuarios existentes (antes del fix): Requieren password reset

### Solución a Largo Plazo:
El fix está en el código, por lo que:
- Nuevos registros funcionarán correctamente
- Usuarios existentes deben resetear una sola vez
- No volverá a ocurrir el problema

---

## 💡 Prevención Futura

### Al crear usuarios programáticamente:

```php
// ✅ CORRECTO - Dejar que el cast maneje el hashing
User::create([
    'email' => 'user@example.com',
    'password' => 'plaintext-password', // Se hashea automático
]);

// ❌ INCORRECTO - No hashear manualmente
User::create([
    'email' => 'user@example.com',
    'password' => Hash::make('plaintext-password'), // DOBLE HASH!
]);
```

### Alternativa (si quieres ser explícito):

```php
// Opción: Desactivar el cast y hashear manualmente siempre
// En User.php:
protected function casts(): array {
    return [
        'email_verified_at' => 'datetime',
        // NO incluir 'password' => 'hashed'
    ];
}

// Entonces sí usar Hash::make() en todos lados
```

**Recomendación**: Mantener el cast `'hashed'` (más limpio y moderno)

---

**Última actualización:** 06-Ene-2026
**Fix aplicado:** v3.0.35
**Status:** ✅ Resuelto para nuevos usuarios
