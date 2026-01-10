# 🚀 Guía de Despliegue a Producción - GrafiRed 3.0

## 📋 Índice
1. [Estrategia Git Flow](#estrategia-git-flow)
2. [Versionamiento Semántico](#versionamiento-semántico)
3. [Preparación para Producción](#preparación-para-producción)
4. [Despliegue en Railway](#despliegue-en-railway)
5. [Workflow de Desarrollo Post-Producción](#workflow-de-desarrollo)
6. [Ambientes de Testing](#ambientes-de-testing)
7. [Checklist Pre-Deploy](#checklist-pre-deploy)

---

## 🌳 Estrategia Git Flow

### Estructura de Ramas Recomendada

```
main (producción)
  ↑
  └── staging (pre-producción / QA)
       ↑
       └── develop (desarrollo activo)
            ↑
            └── feature/nombre-feature (features individuales)
```

### Configuración Inicial de Ramas

```bash
# 1. Asegúrate de estar en main y tener todo actualizado
git checkout main
git pull origin main

# 2. Crear rama develop desde main
git checkout -b develop
git push -u origin develop

# 3. Crear rama staging desde main
git checkout main
git checkout -b staging
git push -u origin staging

# 4. Volver a develop para trabajar
git checkout develop
```

### Reglas de Oro

- **main**: SOLO código en producción. NUNCA commits directos.
- **staging**: Pre-producción. Testing final antes de producción.
- **develop**: Desarrollo activo. Integración de features.
- **feature/\***: Features individuales. Se crean desde develop.

---

## 🔢 Versionamiento Semántico

### Formato: `MAJOR.MINOR.PATCH`

**Ejemplo**: `1.0.0`

- **MAJOR** (1.x.x): Cambios incompatibles, rediseño completo
- **MINOR** (x.1.x): Nuevas funcionalidades compatibles
- **PATCH** (x.x.1): Corrección de bugs, hotfixes

### Tu Primera Versión

Dado que el proyecto está completo y listo para producción:

```
Versión Inicial: v1.0.0
```

**Justificación**:
- ✅ Funcionalidad completa (cotizaciones, órdenes, stock, etc.)
- ✅ Testing manual documentado
- ✅ Sistema multi-tenant operativo
- ✅ Emails y notificaciones funcionando
- ✅ PDFs generados correctamente

### Futuras Versiones

```
v1.0.0 → Lanzamiento inicial
v1.0.1 → Fix bug en cálculo de totales
v1.0.2 → Fix error en emails
v1.1.0 → Nuevo módulo de reportes
v1.2.0 → Dashboard de producción mejorado
v2.0.0 → Migración a multi-moneda (breaking change)
```

---

## 🛠️ Preparación para Producción

### Paso 1: Crear Tag de Versión

```bash
# En rama main
git checkout main
git pull origin main

# Crear tag anotado
git tag -a v1.0.0 -m "Release v1.0.0 - Lanzamiento inicial de GrafiRed 3.0

Features principales:
- Sistema multi-tenant completo
- Gestión de cotizaciones, órdenes de pedido, órdenes de producción
- Cuentas de cobro con workflow de estados
- Sistema de inventario (papeles, máquinas, items digitales)
- Stock con alertas y movimientos
- Acabados y finishing para productos
- Notificaciones y emails manuales
- PDFs personalizados con logo de empresa
- Sistema de permisos y roles
- Activity logs en super-admin
"

# Subir tag a GitHub
git push origin v1.0.0

# Ver tags creados
git tag -l
```

### Paso 2: Actualizar composer.json

```bash
# Agregar versión al composer.json
```

Edita `composer.json`:

```json
{
    "name": "grafired/grafired",
    "version": "1.0.0",
    "type": "project",
    "description": "GrafiRed 3.0 - SaaS Multi-tenant para Litografías",
    ...
}
```

```bash
# Commit del cambio
git add composer.json
git commit -m "chore: Bump version to v1.0.0"
git push origin main
```

### Paso 3: Crear Archivo de Versión

```bash
# Crear archivo VERSION en la raíz del proyecto
echo "1.0.0" > VERSION
git add VERSION
git commit -m "chore: Add VERSION file"
git push origin main
```

### Paso 4: Optimizaciones para Producción

Crea un script de deploy:

```bash
touch deploy.sh
chmod +x deploy.sh
```

---

## 🚂 Despliegue en Railway

### Variables de Entorno Críticas

Configura en Railway Dashboard:

```env
# Aplicación
APP_NAME="GrafiRed 3.0"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-app.railway.app
APP_LOCALE=es
APP_FALLBACK_LOCALE=es

# Base de Datos (Railway provee estas automáticamente si agregas MySQL)
DB_CONNECTION=mysql
DB_HOST=${MYSQLHOST}
DB_PORT=${MYSQLPORT}
DB_DATABASE=${MYSQLDATABASE}
DB_USERNAME=${MYSQLUSER}
DB_PASSWORD=${MYSQLPASSWORD}

# Session y Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Filesystem (usar S3 o railway volumes para archivos)
FILESYSTEM_DISK=local

# Email (configura con tu proveedor: SendGrid, Mailgun, SES, Mailtrap)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu-username
MAIL_PASSWORD=tu-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@grafired.com"
MAIL_FROM_NAME="GrafiRed"

# Seguridad
APP_KEY=  # Railway puede generar esto automáticamente

# Logs
LOG_CHANNEL=stack
LOG_LEVEL=info
```

### Archivo railway.json

Crea en la raíz del proyecto:

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "NIXPACKS",
    "buildCommand": "composer install --no-dev --optimize-autoloader && npm ci && npm run build"
  },
  "deploy": {
    "startCommand": "php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan storage:link && php artisan serve --host=0.0.0.0 --port=${PORT}",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

### Archivo Procfile (alternativa)

```
web: php artisan serve --host=0.0.0.0 --port=${PORT}
```

### nixpacks.toml (configuración Railway)

```toml
[phases.setup]
nixPkgs = ['php83', 'php83Packages.composer', 'nodejs_20']

[phases.install]
cmds = [
  'composer install --no-dev --optimize-autoloader --no-interaction',
  'npm ci',
]

[phases.build]
cmds = [
  'npm run build',
  'php artisan config:cache',
  'php artisan route:cache',
  'php artisan view:cache',
]

[start]
cmd = 'php artisan migrate --force && php artisan storage:link && php artisan serve --host=0.0.0.0 --port=${PORT}'
```

### Pasos en Railway

1. **Conectar GitHub**:
   - Dashboard → New Project → Deploy from GitHub repo
   - Seleccionar: `dasiva87/Litopro825`
   - Rama: `main`

2. **Agregar Base de Datos**:
   - New Service → Database → MySQL
   - Railway conectará automáticamente las variables

3. **Configurar Variables de Entorno**:
   - Settings → Variables
   - Agregar todas las variables listadas arriba

4. **Generar APP_KEY**:
   ```bash
   # Localmente
   php artisan key:generate --show
   # Copiar el resultado a Railway como APP_KEY
   ```

5. **Deploy**:
   - Railway detectará cambios en `main` y desplegará automáticamente

6. **Verificar**:
   - Logs → Ver que migraciones se ejecuten
   - Abrir URL de la app

### Comandos Post-Deploy (una sola vez)

```bash
# Conectarte via Railway CLI o SSH
railway login
railway link
railway run php artisan grafired:setup-demo  # Si quieres datos demo
```

---

## 🔄 Workflow de Desarrollo Post-Producción

### Escenario 1: Nueva Feature

```bash
# 1. Crear rama feature desde develop
git checkout develop
git pull origin develop
git checkout -b feature/dashboard-analytics

# 2. Desarrollar la feature
# ... hacer commits ...
git add .
git commit -m "feat: Add analytics dashboard with charts"

# 3. Subir a GitHub
git push -u origin feature/dashboard-analytics

# 4. Crear Pull Request en GitHub
# feature/dashboard-analytics → develop

# 5. Después de aprobar y mergear, eliminar rama
git checkout develop
git pull origin develop
git branch -d feature/dashboard-analytics
git push origin --delete feature/dashboard-analytics
```

### Escenario 2: Hotfix Urgente en Producción

```bash
# 1. Crear rama hotfix desde main
git checkout main
git pull origin main
git checkout -b hotfix/fix-total-calculation

# 2. Hacer el fix
git add .
git commit -m "fix: Correct total calculation in quotations"

# 3. Mergear a main (producción)
git checkout main
git merge hotfix/fix-total-calculation

# 4. Crear tag de patch
git tag -a v1.0.1 -m "Hotfix v1.0.1 - Fix cálculo de totales"
git push origin main --tags

# 5. Mergear también a develop (para que no se pierda)
git checkout develop
git merge hotfix/fix-total-calculation
git push origin develop

# 6. Eliminar rama hotfix
git branch -d hotfix/fix-total-calculation
```

### Escenario 3: Release Nueva Versión

```bash
# 1. Mergear develop a staging para testing final
git checkout staging
git pull origin staging
git merge develop
git push origin staging

# 2. Probar en ambiente de staging (Railway puede tener un segundo proyecto)

# 3. Si todo está OK, mergear staging a main
git checkout main
git pull origin main
git merge staging

# 4. Crear tag de versión
git tag -a v1.1.0 -m "Release v1.1.0 - Dashboard de analytics

Features:
- Dashboard con gráficas de ventas
- Reportes exportables a Excel
- Filtros avanzados por fecha
"

# 5. Subir a producción
git push origin main --tags

# Railway desplegará automáticamente
```

---

## 🧪 Ambientes de Testing

### Opción A: Railway Multiple Projects (Recomendado)

**Configuración**:

1. **Proyecto Production** (main):
   - URL: `grafired-production.railway.app`
   - Rama: `main`
   - BD: MySQL Production

2. **Proyecto Staging** (staging):
   - URL: `grafired-staging.railway.app`
   - Rama: `staging`
   - BD: MySQL Staging

3. **Proyecto Development** (develop):
   - URL: `grafired-dev.railway.app`
   - Rama: `develop`
   - BD: MySQL Development

**Ventajas**:
- ✅ 3 ambientes separados
- ✅ Testing completo antes de producción
- ✅ No afectas datos reales

**Costo**: ~$15-30/mes (Railway tiene plan gratuito limitado)

### Opción B: Local + Staging + Production

**Configuración**:

1. **Local Development**:
   - Tu máquina (`localhost:8000`)
   - Rama: `develop` o `feature/*`
   - BD: MySQL local

2. **Staging en Railway**:
   - URL: `grafired-staging.railway.app`
   - Rama: `staging`
   - BD: MySQL Staging

3. **Production en Railway**:
   - URL: `grafired.railway.app`
   - Rama: `main`
   - BD: MySQL Production

**Ventajas**:
- ✅ Costo reducido (solo 2 proyectos Railway)
- ✅ Desarrollo local rápido
- ✅ Staging para QA final

**Costo**: ~$10-20/mes

### Opción C: Solo Local + Production (Mínimo)

**Configuración**:

1. **Local Development**:
   - Tu máquina
   - Ramas: `develop`, `feature/*`

2. **Production en Railway**:
   - Rama: `main`

**Proceso de Testing**:
```bash
# 1. Desarrollar localmente
git checkout develop
# ... desarrollar y probar ...

# 2. Mergear a staging temporal local
git checkout staging
git merge develop
# Probar exhaustivamente local

# 3. Si todo OK, mergear a main
git checkout main
git merge staging
git push origin main  # Deploy automático
```

**Ventajas**:
- ✅ Costo mínimo
- ✅ Suficiente para startups pequeñas

**Desventajas**:
- ⚠️ No hay ambiente de staging público
- ⚠️ Testing de producción limitado

---

## ✅ Checklist Pre-Deploy

### Antes del Primer Deploy

- [ ] **Código**:
  - [ ] Todos los tests pasan (`php artisan test`)
  - [ ] Código formateado (`php artisan pint`)
  - [ ] Sin errores de análisis (`composer analyse`)
  - [ ] Archivo `pruebas-manuales.md` completado

- [ ] **Base de Datos**:
  - [ ] Migraciones sin errores
  - [ ] Seeders funcionan correctamente
  - [ ] Backup de BD de desarrollo

- [ ] **Configuración**:
  - [ ] `.env.example` actualizado con todas las variables
  - [ ] `APP_DEBUG=false` en producción
  - [ ] `APP_ENV=production`
  - [ ] `APP_KEY` generado

- [ ] **Assets**:
  - [ ] `npm run build` ejecutado
  - [ ] Archivos compilados en `public/build`
  - [ ] Imágenes optimizadas

- [ ] **Seguridad**:
  - [ ] HTTPS habilitado
  - [ ] CORS configurado
  - [ ] Validaciones en todos los formularios
  - [ ] Políticas de permisos revisadas

- [ ] **Git**:
  - [ ] Tag `v1.0.0` creado
  - [ ] `CHANGELOG.md` creado (opcional pero recomendado)
  - [ ] Ramas `develop` y `staging` creadas

- [ ] **Railway**:
  - [ ] Proyecto creado y conectado a GitHub
  - [ ] MySQL agregado
  - [ ] Variables de entorno configuradas
  - [ ] `railway.json` o `nixpacks.toml` creado

- [ ] **Emails**:
  - [ ] Proveedor de email configurado (no usar Mailtrap en producción)
  - [ ] Templates de email probados
  - [ ] Direcciones de email verificadas

- [ ] **Monitoreo**:
  - [ ] Logs configurados (`LOG_CHANNEL=stack`)
  - [ ] Sentry o similar para error tracking (opcional)

### Después del Deploy

- [ ] Verificar que la app carga correctamente
- [ ] Ejecutar migraciones: `php artisan migrate --force`
- [ ] Crear usuario admin inicial
- [ ] Probar login
- [ ] Probar crear cotización completa
- [ ] Verificar envío de emails
- [ ] Verificar PDFs se generan
- [ ] Revisar logs de errores
- [ ] Probar en móvil/tablet

---

## 📝 Archivos Adicionales Recomendados

### CHANGELOG.md

```markdown
# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Versionamiento Semántico](https://semver.org/lang/es/).

## [1.0.0] - 2026-01-04

### Agregado
- Sistema multi-tenant completo
- Gestión de cotizaciones con estados
- Órdenes de pedido con workflow
- Órdenes de producción con acabados
- Cuentas de cobro con aprobación
- Sistema de inventario (papeles, máquinas, items digitales)
- Gestión de stock con alertas
- Sistema de acabados (finishing)
- Notificaciones internas
- Envío manual de emails con PDFs
- Activity logs en super-admin
- Sistema de permisos y roles
- PDFs personalizados con logo de empresa

### Seguridad
- Políticas de acceso por tenant
- Validaciones de formularios
- Protección CSRF
- Autenticación con Sanctum
```

### .github/workflows/tests.yml (CI/CD Opcional)

```yaml
name: Tests

on:
  push:
    branches: [ develop, staging, main ]
  pull_request:
    branches: [ develop, staging, main ]

jobs:
  tests:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.3
        extensions: mbstring, bcmath, pdo_mysql

    - name: Install Dependencies
      run: composer install --no-interaction --prefer-dist

    - name: Run Tests
      run: php artisan test

    - name: Run Pint
      run: php artisan pint --test
```

---

## 🚀 Comando de Deploy Rápido

Crea un script para automatizar deploys:

```bash
#!/bin/bash
# deploy.sh

echo "🚀 Deploy Script - GrafiRed 3.0"
echo ""

# Verificar rama actual
BRANCH=$(git branch --show-current)
echo "📍 Rama actual: $BRANCH"

if [ "$BRANCH" != "main" ]; then
    echo "❌ ERROR: Debes estar en la rama 'main' para hacer deploy"
    exit 1
fi

# Verificar que no haya cambios sin commit
if [ -n "$(git status --porcelain)" ]; then
    echo "❌ ERROR: Hay cambios sin commit"
    git status
    exit 1
fi

# Solicitar versión
echo ""
echo "Ingresa la nueva versión (actual: $(git describe --tags --abbrev=0 2>/dev/null || echo 'ninguna')):"
read VERSION

if [ -z "$VERSION" ]; then
    echo "❌ ERROR: Debes ingresar una versión"
    exit 1
fi

# Confirmar
echo ""
echo "¿Desplegar versión $VERSION a producción? (y/n)"
read CONFIRM

if [ "$CONFIRM" != "y" ]; then
    echo "❌ Deploy cancelado"
    exit 0
fi

# Actualizar VERSION file
echo "$VERSION" > VERSION
git add VERSION

# Actualizar composer.json
sed -i "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" composer.json
git add composer.json

# Commit y tag
git commit -m "chore: Bump version to v$VERSION"
git tag -a "v$VERSION" -m "Release v$VERSION"

# Push
git push origin main --tags

echo ""
echo "✅ Deploy completado!"
echo "🏷️  Tag creado: v$VERSION"
echo "🚂 Railway desplegará automáticamente en unos minutos"
echo ""
echo "Verifica el deploy en: https://railway.app/dashboard"
```

---

## 📞 Soporte y Recursos

- **Railway Docs**: https://docs.railway.app/
- **Laravel Deployment**: https://laravel.com/docs/deployment
- **Semantic Versioning**: https://semver.org/lang/es/
- **Git Flow**: https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow

---

**Última Actualización**: 04 de Enero 2026
**Versión del Documento**: 1.0
