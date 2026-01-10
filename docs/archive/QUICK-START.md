# 🚀 Quick Start - Deploy a Producción en 15 Minutos

Esta guía te llevará de 0 a producción en Railway en menos de 15 minutos.

## ✅ Pre-requisitos

- [ ] Cuenta en Railway: https://railway.app (puedes usar GitHub para login)
- [ ] Cuenta en GitHub (ya tienes: dasiva87/Litopro825)
- [ ] Proveedor de Email configurado (SendGrid gratis: 100 emails/día)

---

## 📝 Paso 1: Preparar Repositorio (2 minutos)

### 1.1 Crear Ramas de Trabajo

```bash
cd /home/dasiva/Descargas/grafired825

# Crear rama develop
git checkout -b develop
git push -u origin develop

# Crear rama staging
git checkout main
git checkout -b staging
git push -u origin staging

# Volver a main
git checkout main
```

### 1.2 Crear Tag Inicial v1.0.0

```bash
# Asegurarte de estar en main
git checkout main

# Crear tag
git tag -a v1.0.0 -m "Release v1.0.0 - Lanzamiento inicial GrafiRed 3.0"

# Subir tag
git push origin v1.0.0

# Verificar
git tag -l
```

### 1.3 Agregar Archivos de Deploy

```bash
# Los archivos ya están creados:
# - railway.json
# - nixpacks.toml
# - Procfile
# - deploy.sh
# - .env.production.example

# Commitear todo
git add .
git commit -m "chore: Add deployment configuration files"
git push origin main
```

---

## 🚂 Paso 2: Configurar Railway (5 minutos)

### 2.1 Crear Proyecto

1. Ir a https://railway.app/dashboard
2. Click en **"New Project"**
3. Seleccionar **"Deploy from GitHub repo"**
4. Buscar y seleccionar: **dasiva87/Litopro825**
5. Railway comenzará a detectar el proyecto

### 2.2 Agregar Base de Datos MySQL

1. En el mismo proyecto, click en **"+ New"**
2. Seleccionar **"Database" → "MySQL"**
3. Railway creará automáticamente las variables de conexión

### 2.3 Configurar Variables de Entorno

1. Click en tu servicio de Laravel (no la BD)
2. Ir a **"Variables"** tab
3. Click en **"Raw Editor"**
4. Pegar el siguiente contenido (ajustar valores):

```env
APP_NAME=GrafiRed 3.0
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=America/Bogota
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}
APP_LOCALE=es
APP_FALLBACK_LOCALE=es

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

LOG_CHANNEL=stack
LOG_LEVEL=warning

MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=TU_SENDGRID_API_KEY_AQUI
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@grafired.com
MAIL_FROM_NAME=GrafiRed 3.0

BROADCAST_CONNECTION=log

DB_CONNECTION=mysql
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}
```

4. Click en **"Add"** o **"Save"**

### 2.4 Generar APP_KEY

Opción A (Automático):
```
Railway generará APP_KEY automáticamente en el primer deploy
```

Opción B (Manual):
```bash
# En tu terminal local
php artisan key:generate --show

# Copiar el resultado (ejemplo: base64:abc123...)
# Agregarlo en Railway Variables como:
APP_KEY=base64:el-valor-que-copiaste
```

### 2.5 Configurar Dominio Público

1. Ir a **"Settings"** tab de tu servicio Laravel
2. Sección **"Networking"**
3. Click en **"Generate Domain"**
4. Railway te dará una URL como: `grafired-production.up.railway.app`
5. Copiar esa URL
6. Ir a **"Variables"** y actualizar `APP_URL` con esa URL completa

---

## 📧 Paso 3: Configurar Email - SendGrid (3 minutos)

### 3.1 Crear Cuenta SendGrid

1. Ir a https://sendgrid.com/
2. Sign Up (gratis: 100 emails/día)
3. Verificar email

### 3.2 Crear API Key

1. Dashboard SendGrid → Settings → API Keys
2. Click **"Create API Key"**
3. Nombre: "GrafiRed Production"
4. Permisos: "Full Access"
5. Click **"Create & View"**
6. **COPIAR LA API KEY** (solo se muestra una vez)

### 3.3 Agregar a Railway

1. Volver a Railway → Variables
2. Buscar `MAIL_PASSWORD`
3. Pegar tu API Key de SendGrid
4. Guardar

### 3.4 Verificar Sender Identity

1. SendGrid → Settings → Sender Authentication
2. Single Sender Verification
3. Agregar email: `no-reply@tudominio.com` o tu email personal
4. Verificar el email que te llegue
5. Actualizar `MAIL_FROM_ADDRESS` en Railway con ese email

---

## 🎬 Paso 4: Deploy (2 minutos)

### 4.1 Trigger Deploy

Railway debería deployar automáticamente, pero si no:

1. Ir a **"Deployments"** tab
2. Click en **"Deploy"** o esperar a que termine el build automático

### 4.2 Monitorear Deploy

1. Ver los logs en tiempo real
2. Buscar mensajes como:
   ```
   ✓ Running migrations...
   ✓ Caching configuration...
   ✓ Building assets...
   ✓ Server started on port 3000
   ```

3. Si hay errores, revisar:
   - Variables de entorno correctas
   - APP_KEY generado
   - Conexión a MySQL exitosa

---

## ✅ Paso 5: Verificación Post-Deploy (3 minutos)

### 5.1 Abrir la Aplicación

1. Click en la URL de tu app (ej: `https://grafired-production.up.railway.app`)
2. Deberías ver la página de login de GrafiRed

### 5.2 Crear Usuario Admin Inicial

Opción A - Via Railway Shell:
```bash
# En Railway → Service → Shell (tab)
php artisan tinker

# Ejecutar:
$user = \App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@grafired.com',
    'password' => bcrypt('password123'),
    'email_verified_at' => now(),
]);

exit
```

Opción B - Via Comando Local conectado a Railway:
```bash
# Instalar Railway CLI
npm install -g @railway/cli

# Login
railway login

# Conectar al proyecto
railway link

# Ejecutar comando
railway run php artisan tinker
# ... mismo código de arriba
```

### 5.3 Pruebas Básicas

- [ ] Login con usuario creado
- [ ] Verificar que carga el dashboard
- [ ] Crear una cotización de prueba
- [ ] Verificar que se genera PDF
- [ ] Enviar email de prueba (si configuraste SendGrid)
- [ ] Revisar logs en Railway (tab "Logs")

### 5.4 Verificar Base de Datos

1. Railway → MySQL Service → Data tab
2. Verificar que existen tablas:
   - users
   - companies
   - documents
   - purchase_orders
   - etc.

---

## 🔧 Comandos Útiles Post-Deploy

### Ver Logs en Vivo

```bash
# Via Railway Dashboard
Deployments → Click en el deploy → View Logs

# Via Railway CLI
railway logs
```

### Ejecutar Migraciones Manualmente

```bash
railway run php artisan migrate --force
```

### Limpiar Cachés

```bash
railway run php artisan config:clear
railway run php artisan cache:clear
railway run php artisan view:clear
```

### Crear Link de Storage

```bash
railway run php artisan storage:link
```

### Ver Estado de la App

```bash
railway run php artisan about
```

---

## 🎯 Próximos Pasos

### 1. Configurar Email Personalizado (Opcional)

Si tienes dominio propio:
1. Configurar DNS records en tu proveedor de dominio
2. Verificar dominio en SendGrid
3. Actualizar `MAIL_FROM_ADDRESS` con `no-reply@tudominio.com`

### 2. Configurar Dominio Custom (Opcional)

1. Railway → Settings → Custom Domain
2. Agregar tu dominio (ej: `app.grafired.com`)
3. Configurar CNAME en tu DNS:
   ```
   app.grafired.com → [tu-app].up.railway.app
   ```

### 3. Configurar Ambiente Staging

Repetir pasos de Railway pero:
- Proyecto nuevo: "GrafiRed Staging"
- Rama: `staging` en lugar de `main`
- Variables: usar `staging` en nombres/urls

### 4. Configurar Backups Automáticos

1. Railway → MySQL → Backups
2. Habilitar backups automáticos diarios

### 5. Monitoreo de Errores (Recomendado)

Instalar Sentry:
```bash
composer require sentry/sentry-laravel

# Agregar a Railway Variables:
SENTRY_LARAVEL_DSN=tu-sentry-dsn
```

---

## 📊 Checklist Final

- [ ] ✅ App funcionando en Railway
- [ ] ✅ Base de datos MySQL conectada
- [ ] ✅ Usuario admin creado
- [ ] ✅ Login funcional
- [ ] ✅ Emails configurados (SendGrid)
- [ ] ✅ PDFs se generan correctamente
- [ ] ✅ Ramas develop y staging creadas
- [ ] ✅ Tag v1.0.0 creado
- [ ] ✅ Variables de entorno configuradas
- [ ] ✅ Dominio público asignado
- [ ] ⚠️ Dominio custom configurado (opcional)
- [ ] ⚠️ Backups automáticos habilitados (recomendado)
- [ ] ⚠️ Monitoring de errores (recomendado)

---

## 🆘 Troubleshooting

### Error: "No application encryption key has been specified"

**Solución**:
```bash
# Generar key localmente
php artisan key:generate --show

# Copiar resultado y agregarlo a Railway Variables como APP_KEY
```

### Error: "SQLSTATE[HY000] [2002] Connection refused"

**Solución**:
- Verificar que MySQL service está running en Railway
- Verificar variables de DB en formato: `${{MYSQLHOST}}` (no valores hardcoded)
- Esperar 1-2 minutos después de crear MySQL service

### Error: "Mix manifest not found"

**Solución**:
```bash
# En railway.json, verificar que buildCommand incluye:
npm run build
```

### Emails no se envían

**Solución**:
- Verificar API Key de SendGrid correcta
- Verificar email sender verificado en SendGrid
- Revisar logs: `railway logs | grep -i mail`

### 500 Error en la app

**Solución**:
```bash
# Ver logs detallados
railway logs

# Habilitar debug temporalmente
# Railway Variables: APP_DEBUG=true (luego volver a false)
```

---

## 📞 Soporte

- **Railway Docs**: https://docs.railway.app/
- **Laravel Deployment**: https://laravel.com/docs/deployment
- **SendGrid Docs**: https://docs.sendgrid.com/

---

**Tiempo total estimado**: 15 minutos
**Costo estimado**: $5-10/mes (Railway) + $0 (SendGrid free tier)
**Última actualización**: 04 Enero 2026
