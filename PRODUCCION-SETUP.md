# 🚀 GRAFIRED 3.0 - SETUP DE PRODUCCIÓN

## 📋 DATOS MÍNIMOS NECESARIOS PARA PRODUCCIÓN

### ✅ LO QUE YA ESTÁ INCLUIDO EN EL SEED

Ejecuta el seeder de producción limpio:
```bash
php artisan db:seed --class=MinimalProductionSeeder
```

Esto creará:

#### 1. **Planes de Suscripción (4 planes)**
- ✅ Plan Gratuito ($0 - 1 usuario, 10 cotizaciones/mes)
- ✅ Plan Básico ($150,000 COP - 3 usuarios, 100 cotizaciones/mes)
- ✅ Plan Profesional ($300,000 COP - 10 usuarios, ilimitado)
- ✅ Plan Empresarial ($500,000 COP - usuarios ilimitados, todas las features)

#### 2. **Roles y Permisos (5 roles)**
- ✅ Super Admin (todos los permisos)
- ✅ Company Admin (administrador de empresa)
- ✅ Manager (gerente)
- ✅ Salesperson (vendedor)
- ✅ Operator (operador de producción)

#### 3. **Usuario Super Admin**
- ✅ Email: `admin@grafired.com`
- ✅ Password: `GrafiRed2026!`
- ⚠️  **CAMBIAR INMEDIATAMENTE DESPUÉS DEL PRIMER LOGIN**

#### 4. **Datos Geográficos**
- ✅ Países (Colombia)
- ✅ Estados/Departamentos
- ✅ Ciudades

#### 5. **Datos del Sistema**
- ✅ Tipos de documentos (CC, NIT, etc.)
- ✅ Acabados para talonarios

---

## ⚠️ LO QUE NO INCLUYE (Intencional)

- ❌ Empresas de demostración
- ❌ Usuarios de prueba
- ❌ Datos ficticios de productos
- ❌ Cotizaciones de ejemplo
- ❌ Órdenes de prueba
- ❌ Posts de red social demo

**Razón**: En producción, los clientes crearán sus propios datos reales.

---

## 📊 COMPARATIVA DE PLANES

| Característica | Gratuito | Básico | Profesional | Empresarial |
|----------------|----------|--------|-------------|-------------|
| **Precio/mes** | $0 | $150,000 | $300,000 | $500,000 |
| **Usuarios** | 1 | 3 | 10 | Ilimitados |
| **Cotizaciones/mes** | 10 | 100 | Ilimitadas | Ilimitadas |
| **Productos** | 20 | 100 | Ilimitados | Ilimitados |
| **Storage** | 100 MB | 1 GB | 5 GB | 20 GB |
| **Red Social** | ❌ | ✅ | ✅ | ✅ |
| **Reportes Avanzados** | ❌ | ❌ | ✅ | ✅ |
| **Automatización** | ❌ | ❌ | ❌ | ✅ |
| **API Access** | ❌ | ❌ | ❌ | ✅ |
| **Soporte** | Email | Email | Prioritario | 24/7 |
| **Trial** | - | 30 días | 30 días | 30 días |

---

## 🎯 RECOMENDACIONES PARA PRODUCCIÓN

### 1️⃣ **Antes de Subir a Producción**

```bash
# 1. Limpiar base de datos
php artisan migrate:fresh

# 2. Ejecutar seed de producción
php artisan db:seed --class=MinimalProductionSeeder

# 3. Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan filament:cache-components

# 4. Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2️⃣ **Configurar Variables de Entorno**

**En Railway/Producción**, asegúrate de tener:

```env
# APP
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# DATABASE
DB_CONNECTION=mysql
DB_HOST=tu-host
DB_PORT=3306
DB_DATABASE=grafired_prod
DB_USERNAME=grafired_user
DB_PASSWORD=tu-password-seguro

# STRIPE (Pagos)
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# RESEND (Emails)
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
MAIL_FROM_NAME="GrafiRed"

# SESSION
SESSION_DRIVER=database
SESSION_LIFETIME=120

# QUEUE
QUEUE_CONNECTION=database

# CACHE
CACHE_DRIVER=database
```

### 3️⃣ **Configurar Stripe**

1. Ir a Stripe Dashboard → Products
2. Crear 3 productos (Básico, Profesional, Empresarial)
3. Obtener los Price IDs de cada plan
4. Actualizar en la base de datos:

```sql
UPDATE plans SET stripe_price_id = 'price_xxxxx' WHERE slug = 'basico';
UPDATE plans SET stripe_price_id = 'price_yyyyy' WHERE slug = 'profesional';
UPDATE plans SET stripe_price_id = 'price_zzzzz' WHERE slug = 'empresarial';
```

### 4️⃣ **Primer Login como Super Admin**

1. Ir a: `https://tu-dominio.com/super-admin`
2. Login con:
   - Email: `admin@grafired.com`
   - Password: `GrafiRed2026!`
3. **CAMBIAR CONTRASEÑA INMEDIATAMENTE**
4. Verificar que los planes están activos
5. Probar flujo de registro de nueva empresa

### 5️⃣ **Probar Flujo de Registro**

1. Ir a: `https://tu-dominio.com/admin/register`
2. Registrar una empresa de prueba
3. Seleccionar Plan Gratuito
4. Verificar que se crea correctamente
5. Verificar que expira en 30 días (Plan Gratuito sin trial)
6. Eliminar empresa de prueba si todo funciona

---

## 🔐 SEGURIDAD EN PRODUCCIÓN

### ✅ Checklist de Seguridad

- [ ] `APP_DEBUG=false` en producción
- [ ] Contraseña super-admin cambiada
- [ ] Certificado SSL activo (HTTPS)
- [ ] Firewall configurado
- [ ] Backups automáticos de BD configurados
- [ ] Logs de errores monitoreados
- [ ] Rate limiting activado
- [ ] CSRF protection habilitado (viene por defecto)
- [ ] XSS protection habilitado (viene por defecto)
- [ ] SQL injection protection (Eloquent ORM)

### ✅ Passwords Recomendados

- **Super Admin**: Mínimo 16 caracteres, mayúsculas, minúsculas, números, símbolos
- **Base de Datos**: Generado aleatorio de 32 caracteres
- **Stripe Keys**: Usar variables de entorno, NUNCA en código
- **Resend API Key**: Usar variables de entorno

---

## 📈 MONITOREO EN PRODUCCIÓN

### Métricas a Monitorear

1. **Usuarios**:
   - Total de empresas registradas
   - Empresas por plan
   - Tasa de conversión de trial a pago

2. **Uso**:
   - Cotizaciones creadas por mes
   - Órdenes de producción activas
   - Storage usado por empresa

3. **Performance**:
   - Tiempo de respuesta promedio
   - Errores 500 (logs de Laravel)
   - Uptime del servidor

4. **Facturación**:
   - MRR (Monthly Recurring Revenue)
   - Churn rate
   - Suscripciones activas por plan

---

## 🚨 TROUBLESHOOTING

### Problema: "No se pueden crear cotizaciones"
**Solución**: Verificar que DocumentTypeSeeder se ejecutó correctamente
```bash
php artisan db:seed --class=DocumentTypeSeeder
```

### Problema: "Roles no funcionan"
**Solución**: Limpiar caché de permisos
```bash
php artisan permission:cache-reset
php artisan cache:clear
```

### Problema: "Planes no aparecen en registro"
**Solución**: Verificar que `is_active = true`
```sql
SELECT name, slug, is_active FROM plans;
UPDATE plans SET is_active = 1 WHERE is_active = 0;
```

### Problema: "Super admin no puede acceder"
**Solución**: Verificar que tiene el rol correcto
```bash
php artisan tinker
>>> $user = User::where('email', 'admin@grafired.com')->first();
>>> $user->assignRole('Super Admin');
>>> $user->roles;
```

---

## 📞 SOPORTE POST-DESPLIEGUE

### Comandos Útiles en Producción

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ver últimas 50 líneas de log
tail -50 storage/logs/laravel.log

# Verificar conexión a BD
php artisan db:show

# Verificar caché
php artisan cache:table
php artisan queue:table
php artisan session:table

# Limpiar sesiones expiradas
php artisan session:gc

# Ejecutar queue worker (en background)
php artisan queue:work --daemon
```

---

## ✅ CHECKLIST FINAL PRE-LAUNCH

- [ ] Seeder de producción ejecutado
- [ ] Super admin creado y contraseña cambiada
- [ ] 4 planes activos y configurados
- [ ] Stripe configurado con Price IDs
- [ ] Resend configurado para emails
- [ ] Variables de entorno en Railway configuradas
- [ ] SSL/HTTPS activo
- [ ] Dominio personalizado configurado
- [ ] APP_DEBUG=false
- [ ] Caché optimizado para producción
- [ ] Backups automáticos configurados
- [ ] Monitoreo de errores activo
- [ ] Flujo de registro probado end-to-end
- [ ] Flujo de pago probado (con tarjeta de prueba Stripe)
- [ ] Emails de notificación funcionando
- [ ] Documentación de usuario lista

---

## 🎉 LISTO PARA PRODUCCIÓN

Una vez completado el checklist, tu SaaS está listo para recibir usuarios reales.

**Próximos Pasos**:
1. Abrir registro público
2. Campaña de marketing inicial
3. Monitorear métricas de uso
4. Recolectar feedback de primeros usuarios
5. Iterar y mejorar basado en datos reales

---

**Última Actualización**: 12 de Enero 2026
**Versión de GrafiRed**: 3.0
**Estado**: ✅ Listo para Producción
