# GrafiRed 3.0 - SaaS para Litografías

Sistema SaaS multi-tenant para gestión de litografías con cotizaciones, órdenes de producción, inventario y red social de proveedores.

## 🚀 Despliegue Rápido para Producción

### 1. Requisitos del Servidor

- PHP 8.3+
- MySQL 8.0+
- Composer 2.x
- Node.js 18+ y npm

### 2. Instalación

```bash
# Clonar repositorio
git clone <repo-url> grafired
cd grafired

# Instalar dependencias
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Configurar variables de entorno
cp .env.example .env
php artisan key:generate
```

### 3. Configurar Base de Datos

Editar `.env` con credenciales de MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grafired_prod
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password_seguro
```

Ejecutar migraciones y seeder de producción:

```bash
php artisan migrate --force
php artisan db:seed --class=MinimalProductionSeeder
```

### 4. Configurar Emails (Resend)

En `.env`:

```env
MAIL_MAILER=resend
RESEND_API_KEY=tu_api_key_aqui
MAIL_FROM_ADDRESS="noreply@tudominio.com"
MAIL_FROM_NAME="GrafiRed"
```

### 5. Configurar Pagos (Stripe)

En `.env`:

```env
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

En Stripe Dashboard:
1. Crear 3 productos (Básico, Profesional, Empresarial)
2. Obtener Price IDs
3. Actualizar en base de datos:

```sql
UPDATE plans SET stripe_price_id = 'price_xxxxx' WHERE slug = 'basico';
UPDATE plans SET stripe_price_id = 'price_yyyyy' WHERE slug = 'profesional';
UPDATE plans SET stripe_price_id = 'price_zzzzz' WHERE slug = 'empresarial';
```

### 6. Optimización de Producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
```

### 7. Primer Acceso

**Super Admin Panel**: `https://tudominio.com/super-admin`

Credenciales iniciales:
- Email: `admin@grafired.com`
- Password: `GrafiRed2026!`

**⚠️ IMPORTANTE**: Cambiar contraseña inmediatamente después del primer login.

## 📋 Checklist de Producción

- [ ] `APP_ENV=production` en `.env`
- [ ] `APP_DEBUG=false` en `.env`
- [ ] `APP_URL` configurado con dominio real
- [ ] Base de datos MySQL configurada
- [ ] Migraciones ejecutadas
- [ ] `MinimalProductionSeeder` ejecutado
- [ ] Resend API key configurada
- [ ] Stripe keys de producción configuradas
- [ ] Stripe Price IDs actualizados en DB
- [ ] Contraseña super-admin cambiada
- [ ] SSL/HTTPS activo
- [ ] Cachés optimizados
- [ ] Backups automáticos configurados

## 📚 Documentación Completa

Ver `PRODUCCION-SETUP.md` para:
- Comparativa de planes de suscripción
- Seguridad y mejores prácticas
- Troubleshooting
- Monitoreo y métricas
- Comandos útiles

## 🛠️ Desarrollo Local

```bash
# Copiar .env de ejemplo
cp .env.example .env

# Configurar para desarrollo
php artisan key:generate

# Base de datos (SQLite o MySQL)
php artisan migrate:fresh --seed

# Usar FullDemoSeeder para datos de prueba
php artisan grafired:setup-demo --fresh

# Iniciar servidor
php artisan serve --port=8000

# En otra terminal: Compilar assets
npm run dev
```

**URLs Locales**:
- Dashboard: `http://127.0.0.1:8000/admin`
- Super Admin: `http://127.0.0.1:8000/super-admin`

## 📦 Estructura de Seeders

**Para Producción**:
```bash
php artisan db:seed --class=MinimalProductionSeeder
```
Crea: 4 planes, roles/permisos, super-admin, datos geográficos, tipos de documentos.

**Para Desarrollo**:
```bash
php artisan db:seed --class=FullDemoSeeder
```
Crea todo lo anterior + 2 empresas demo con datos de prueba.

## 🧪 Testing

```bash
# Ejecutar tests
php artisan test

# Lint y análisis
php artisan pint
composer analyse
```

## 🔒 Seguridad

- Laravel CSRF habilitado por defecto
- XSS protection automática con Blade
- SQL injection protegido con Eloquent ORM
- Rate limiting en rutas de autenticación
- Passwords hasheados con bcrypt (12 rounds)

## 📞 Soporte

Para reportar bugs o solicitar features, usar GitHub Issues del repositorio.

## 📄 Licencia

Propietario - GrafiRed 3.0

---

**Versión**: 3.0
**Última Actualización**: Enero 2026
