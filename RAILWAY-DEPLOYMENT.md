# 🚂 DEPLOYMENT EN RAILWAY - GrafiRed 3.0

Guía específica para desplegar GrafiRed 3.0 en Railway.app

---

## 📋 PRE-REQUISITOS

- [ ] Cuenta de Railway creada
- [ ] Repositorio GitHub conectado
- [ ] Base de datos MySQL provisionada en Railway
- [ ] Dominio personalizado configurado (opcional)

---

## 🔧 CONFIGURACIÓN INICIAL

### 1. Variables de Entorno en Railway

En el dashboard de Railway, agregar las siguientes variables:

#### **Aplicación**
```env
APP_NAME=GrafiRed
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.railway.app
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CO
```

#### **Base de Datos** (Railway provee automáticamente)
Railway genera automáticamente:
- `DATABASE_URL`
- `MYSQL_URL`

Si necesitas las variables individuales:
```env
DB_CONNECTION=mysql
DB_HOST=${{MYSQL.HOST}}
DB_PORT=${{MYSQL.PORT}}
DB_DATABASE=${{MYSQL.DATABASE}}
DB_USERNAME=${{MYSQL.USER}}
DB_PASSWORD=${{MYSQL.PASSWORD}}
```

#### **APP_KEY** (CRÍTICO)
Generar localmente y copiar:
```bash
php artisan key:generate --show
# Copiar el output (ejemplo: base64:abc123...)
```

En Railway:
```env
APP_KEY=base64:el_key_generado_aqui
```

#### **Email - Resend**
```env
MAIL_MAILER=resend
RESEND_API_KEY=re_tu_api_key_aqui
MAIL_FROM_ADDRESS=app@gremio.grafired.com
MAIL_FROM_NAME=GrafiRed
```

#### **Pagos - Stripe**
```env
STRIPE_KEY=pk_live_tu_key_aqui
STRIPE_SECRET=sk_live_tu_secret_aqui
STRIPE_WEBHOOK_SECRET=whsec_tu_webhook_secret_aqui
```

#### **Sesiones y Caché**
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
```

---

## 🚀 PROCESO DE DEPLOYMENT

### Paso 1: Conectar Repositorio

1. En Railway Dashboard → **New Project**
2. Seleccionar **Deploy from GitHub repo**
3. Autorizar Railway en GitHub
4. Seleccionar repositorio `grafired`
5. Seleccionar rama `main`

### Paso 2: Provisionar MySQL

1. En el proyecto Railway → **New** → **Database**
2. Seleccionar **MySQL**
3. Esperar a que se provisione (tarda ~2 minutos)
4. Railway automáticamente conecta las variables `MYSQL_*`

### Paso 3: Configurar Variables de Entorno

1. En Railway Dashboard → **Variables**
2. Agregar todas las variables listadas arriba
3. **IMPORTANTE**: Agregar `APP_KEY` generado localmente
4. **IMPORTANTE**: Configurar `APP_ENV=production` y `APP_DEBUG=false`

### Paso 4: Deploy Automático

Railway detecta `nixpacks.toml` y ejecuta automáticamente:

```bash
# Setup
- Instala PHP 8.3
- Instala Composer
- Instala Node.js 20

# Install
- composer install --no-dev --optimize-autoloader
- npm ci

# Build
- npm run build

# Start (cada vez que se inicia el servicio)
- php artisan migrate --force
- php artisan grafired:clear-cache --production
- php artisan storage:link
- php artisan serve
```

### Paso 5: Ejecutar Seeder (SOLO PRIMERA VEZ)

**Railway CLI** (Recomendado):

```bash
# Instalar Railway CLI
npm i -g @railway/cli

# Login
railway login

# Conectar al proyecto
railway link

# Ejecutar seeder
railway run php artisan db:seed --class=MinimalProductionSeeder --force
```

**Railway Dashboard**:

1. Ir a **Deployments** → Último deployment exitoso
2. Click en **View Logs**
3. Click en **Shell** (terminal)
4. Ejecutar:
```bash
php artisan db:seed --class=MinimalProductionSeeder --force
```

### Paso 6: Verificar Deployment

1. Acceder a tu URL de Railway (ejemplo: `https://grafired-production.up.railway.app`)
2. Verificar que redirige a `/admin/login`
3. Acceder a `/super-admin`
4. Login con:
   - Email: `admin@grafired.com`
   - Password: `GrafiRed2026!`
5. **CAMBIAR CONTRASEÑA INMEDIATAMENTE**

---

## 🔄 ACTUALIZACIONES POSTERIORES

Cada vez que haces `git push` a la rama `main`:

1. Railway detecta el cambio automáticamente
2. Ejecuta build automáticamente
3. Ejecuta migraciones (`migrate --force`)
4. Optimiza cachés (`grafired:clear-cache --production`)
5. Reinicia el servicio

**NO** necesitas volver a ejecutar el seeder en actualizaciones.

---

## 🏷️ CONFIGURAR DOMINIO PERSONALIZADO

### Opción A: Subdominio de Railway

Railway provee automáticamente:
```
https://tu-proyecto.up.railway.app
```

### Opción B: Dominio Personalizado

1. En Railway Dashboard → **Settings** → **Domains**
2. Click **Generate Domain** para Railway subdomain
3. O click **Custom Domain** para tu dominio
4. Si usas dominio propio:
   - Tipo: `CNAME`
   - Host: `app` (o lo que prefieras)
   - Value: `tu-proyecto.up.railway.app`
5. Railway automáticamente genera certificado SSL

Actualizar `APP_URL` en variables:
```env
APP_URL=https://app.grafired.com
```

---

## 💳 CONFIGURAR STRIPE EN PRODUCCIÓN

### 1. Crear Productos en Stripe

En Stripe Dashboard → **Products**:

1. **Plan Básico**
   - Nombre: "Plan Básico - GrafiRed"
   - Precio: $150,000 COP/mes
   - Copiar **Price ID**: `price_xxxxx`

2. **Plan Profesional**
   - Nombre: "Plan Profesional - GrafiRed"
   - Precio: $300,000 COP/mes
   - Copiar **Price ID**: `price_yyyyy`

3. **Plan Empresarial**
   - Nombre: "Plan Empresarial - GrafiRed"
   - Precio: $500,000 COP/mes
   - Copiar **Price ID**: `price_zzzzz`

### 2. Actualizar Price IDs en Base de Datos

Via Railway CLI:
```bash
railway run php artisan tinker

# En tinker:
\App\Models\Plan::where('slug', 'basico')->update(['stripe_price_id' => 'price_xxxxx']);
\App\Models\Plan::where('slug', 'profesional')->update(['stripe_price_id' => 'price_yyyyy']);
\App\Models\Plan::where('slug', 'empresarial')->update(['stripe_price_id' => 'price_zzzzz']);
```

O via SQL directo en Railway Dashboard:
```sql
UPDATE plans SET stripe_price_id = 'price_xxxxx' WHERE slug = 'basico';
UPDATE plans SET stripe_price_id = 'price_yyyyy' WHERE slug = 'profesional';
UPDATE plans SET stripe_price_id = 'price_zzzzz' WHERE slug = 'empresarial';
```

### 3. Configurar Webhook en Stripe

1. En Stripe Dashboard → **Developers** → **Webhooks**
2. Click **Add endpoint**
3. URL: `https://tu-dominio.railway.app/stripe/webhook`
4. Eventos a escuchar:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
5. Copiar **Signing secret**: `whsec_xxxxx`
6. Agregar a variables de Railway:
```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

---

## 📊 MONITOREO Y LOGS

### Ver Logs en Tiempo Real

**Railway Dashboard**:
1. Ir a **Deployments**
2. Click en deployment activo
3. Ver logs en tiempo real

**Railway CLI**:
```bash
railway logs
```

### Comandos Útiles via Railway CLI

```bash
# Ver status del servicio
railway status

# Ejecutar comando en producción
railway run php artisan [comando]

# Acceder a shell
railway run bash

# Ver variables de entorno
railway variables
```

### Logs de Laravel

Railway persiste logs en volumen:
```bash
railway run cat storage/logs/laravel.log
railway run tail -f storage/logs/laravel.log  # Tiempo real
```

---

## 🚨 TROUBLESHOOTING EN RAILWAY

### Error: "No application encryption key has been specified"

**Solución**: Generar y agregar `APP_KEY` en variables.
```bash
# Local
php artisan key:generate --show

# Copiar output y agregar a Railway variables
```

### Error: "SQLSTATE[HY000] [2002] Connection refused"

**Causa**: Base de datos no conectada correctamente.

**Solución**:
1. Verificar que MySQL está provisionado
2. Verificar que está en el mismo proyecto
3. Railway auto-conecta variables, no hace falta configurar manualmente

### Error: "Class 'Resend\Laravel\ResendServiceProvider' not found"

**Causa**: Dependencias no instaladas.

**Solución**: Verificar que `composer.json` incluye:
```json
"resend/resend-laravel": "^1.1"
```

Redeploy forzado:
```bash
railway redeploy
```

### Error: Emails no llegan

**Solución**:
1. Verificar `RESEND_API_KEY` en variables
2. Verificar dominio verificado en Resend
3. Probar envío manual:
```bash
railway run php artisan tinker
Mail::raw('Test', fn($m) => $m->to('test@email.com')->subject('Test'));
```

### Error: 500 Internal Server Error

**Solución**: Ver logs detallados:
```bash
railway logs --tail 100
railway run cat storage/logs/laravel.log | tail -50
```

---

## 🔒 SEGURIDAD EN RAILWAY

### Checklist de Seguridad

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` único y secreto
- [ ] Contraseña super-admin cambiada
- [ ] HTTPS activo (Railway lo hace automáticamente)
- [ ] Variables sensibles en Railway Variables (no en código)
- [ ] Stripe webhook secret configurado
- [ ] Backups de BD configurados

### Backups de Base de Datos

Railway no hace backups automáticos en plan gratuito.

**Opción 1: Backup Manual**
```bash
# Via Railway CLI
railway run mysqldump -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE > backup.sql
```

**Opción 2: Servicio Externo**
Usar servicios como:
- SimpleBackups.com
- BackupNinja.com
- Cron job externo

---

## 💰 COSTOS EN RAILWAY

**Plan Developer** (Recomendado para producción pequeña):
- $5/mes por servicio activo
- Incluye 500 horas de ejecución
- MySQL adicional: $5/mes

**Plan Pro** (Producción grande):
- $20/mes base
- Compute por uso
- Soporte prioritario

---

## ✅ CHECKLIST FINAL RAILWAY

Antes de declarar el deployment exitoso:

- [ ] Servicio desplegado y activo en Railway
- [ ] MySQL provisionado y conectado
- [ ] Todas las variables de entorno configuradas
- [ ] `APP_KEY` generado y agregado
- [ ] Migraciones ejecutadas correctamente
- [ ] `MinimalProductionSeeder` ejecutado (solo primera vez)
- [ ] Dominio personalizado configurado (opcional)
- [ ] SSL activo (automático en Railway)
- [ ] Resend API key configurada y emails funcionando
- [ ] Stripe keys configuradas
- [ ] Stripe productos creados y Price IDs actualizados
- [ ] Stripe webhook configurado
- [ ] Super admin accesible en `/super-admin`
- [ ] Contraseña super-admin cambiada
- [ ] Registro de nueva empresa probado
- [ ] Flujo de pago probado (modo test)
- [ ] Logs monitoreados sin errores
- [ ] Backups configurados

---

## 📞 SOPORTE

**Railway Documentation**: https://docs.railway.app/  
**Railway Discord**: https://discord.gg/railway  
**Railway Status**: https://status.railway.app/

**GrafiRed Documentation**: Ver `PRODUCCION-SETUP.md` y `README.md`

---

**Última Actualización**: Enero 2026  
**Versión**: GrafiRed 3.0  
**Deployment Target**: Railway.app
