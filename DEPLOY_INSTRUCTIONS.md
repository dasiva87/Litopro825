# LitoPro 3.0 - Instrucciones de Deploy

## 🚀 Deploy en Railway

### 1. Configuración Inicial
```bash
# Clonar repositorio
git clone <repo-url>
cd litopro825

# Railway se encarga automáticamente de:
# - composer install --optimize-autoloader --no-dev
# - npm ci && npm run build
# - php artisan storage:link
```

### 2. Variables de Entorno Requeridas
Configurar en Railway las siguientes variables:

```env
# App
APP_NAME="LitoPro 3.0"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.railway.app

# Base de datos
DB_CONNECTION=mysql
DB_HOST=${MYSQLHOST}
DB_PORT=${MYSQLPORT}
DB_DATABASE=${MYSQLDATABASE}
DB_USERNAME=${MYSQLUSER}
DB_PASSWORD=${MYSQLPASSWORD}

# Cache y sesiones
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Filament
FILAMENT_DOMAIN=tu-dominio.railway.app
```

### 3. Archivos de Configuración Railway

#### nixpacks.toml (Auto build config)
```toml
[phases.setup]
nixPkgs = ['nodejs_20', 'npm']

[phases.install]
cmds = [
    'npm ci',
    'composer install --optimize-autoloader --no-dev'
]

[phases.build]
cmds = [
    'npm run build',
    'php artisan storage:link'
]

[start]
cmd = 'php artisan serve --host=0.0.0.0 --port=$PORT'
```

#### railway.json (Deploy config)
```json
{
  "build": {
    "builder": "NIXPACKS",
    "buildCommand": "npm ci && npm run build && composer install --optimize-autoloader --no-dev"
  },
  "deploy": {
    "startCommand": "php artisan migrate --force && php artisan db:seed --class=ProductionSeeder && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan storage:link && php artisan serve --host=0.0.0.0 --port=$PORT"
  }
}
```

### 4. Deploy Automático
Railway ejecutará automáticamente:
1. **Build**: Node.js deps + npm run build + Composer install
2. **Deploy**: Migrations + Seeding + Cache + Start server

### 4. Seeders Disponibles

#### ProductionSeeder (Recomendado para producción)
```bash
php artisan db:seed --class=ProductionSeeder
```
**Incluye:**
- ✅ Datos geográficos (países, estados, ciudades)
- ✅ Roles y permisos
- ✅ Tipos de documentos
- ✅ Acabados para talonarios
- ❌ NO incluye datos de prueba

#### FullDemoSeeder (Solo para demos)
```bash
php artisan db:seed --class=FullDemoSeeder
```
**Incluye:**
- ✅ Todo lo de ProductionSeeder
- ✅ Empresas de prueba
- ✅ Usuarios demo (demo@litopro.test / password)
- ✅ Cotizaciones de ejemplo
- ✅ Posts sociales
- ✅ Datos para widgets

### 5. Configuración Post-Deploy

#### Crear Usuario Administrador
```bash
php artisan tinker

# En tinker:
$company = \App\Models\Company::create([
    'name' => 'Mi Empresa',
    'email' => 'admin@miempresa.com',
    'company_type' => 'lithography',
    'slug' => 'mi-empresa'
]);

$user = \App\Models\User::create([
    'name' => 'Administrador',
    'email' => 'admin@miempresa.com',
    'password' => bcrypt('tu-password-seguro'),
    'company_id' => $company->id
]);

$user->assignRole('Super Admin');
```

#### Verificar Configuración
```bash
# Verificar migraciones
php artisan migrate:status

# Verificar permisos
php artisan permission:show

# Verificar cache
php artisan config:show app.env
```

### 6. Estructura de Archivos Críticos

#### Migraciones Principales
- `0001_01_01_000000_create_users_table.php` - Tabla base users
- `2025_08_23_030539_add_company_id_to_users_table.php` - Multi-tenancy
- `2025_08_23_032123_add_foreign_keys_to_users_table.php` - Foreign keys (FIJADO)

#### Seeders
- `ProductionSeeder.php` - Datos mínimos para producción
- `FullDemoSeeder.php` - Demo completo con datos de prueba
- `DatabaseSeeder.php` - Auto-detecta ambiente

### 7. Troubleshooting

#### Error: Duplicate foreign key constraint
**Solucionado** - La migración `add_foreign_keys_to_users_table.php` ahora verifica constraints existentes.

#### Error: Missing permissions
```bash
php artisan db:seed --class=RolePermissionSeeder
```

#### Error: Missing countries/cities
```bash
php artisan db:seed --class=CountrySeeder
php artisan db:seed --class=StateSeeder
php artisan db:seed --class=CitySeeder
```

### 8. URLs de Acceso Post-Deploy

#### Producción
- **Admin Panel**: `https://tu-dominio.railway.app/admin`
- **API**: `https://tu-dominio.railway.app/api`

#### Con datos demo
- **Usuario**: `demo@litopro.test`
- **Password**: `password`

### 9. Comandos de Mantenimiento

#### Limpiar cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

#### Verificar integridad
```bash
php artisan migrate:status
php artisan route:list | grep admin
```

---

## 📋 Checklist de Deploy

- [ ] Variables de entorno configuradas
- [ ] Base de datos MySQL conectada
- [ ] Migraciones ejecutadas sin errores
- [ ] ProductionSeeder ejecutado
- [ ] Cache configurado
- [ ] Usuario administrador creado
- [ ] Acceso al panel admin verificado
- [ ] Storage linked
- [ ] Permisos de Filament funcionando

---

**Nota**: Este sistema es multi-tenant. Cada empresa tiene sus propios datos aislados por `company_id`.