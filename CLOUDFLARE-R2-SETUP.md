# 🚀 Configuración Cloudflare R2 para GrafiRed

## ✅ COMPLETADO

- [x] Instalado driver S3 (`league/flysystem-aws-s3-v3`)
- [x] Configurado `config/filesystems.php` con disco 'r2'
- [x] Creado archivo de ejemplo `.env.cloudflare-r2-example`

---

## 📋 PASOS SIGUIENTES

### 1️⃣ Crear Cuenta y Bucket en Cloudflare R2

1. **Crear cuenta** (si no tienes): https://dash.cloudflare.com/sign-up
2. **Ir a R2**: En dashboard → **R2** (menú lateral)
3. **Crear Bucket**:
   - Click **"Create bucket"**
   - Nombre: `grafired-uploads`
   - Ubicación: **Automatic**
   - Click **"Create bucket"**

### 2️⃣ Obtener Credenciales API

1. En R2 → **"Manage R2 API Tokens"**
2. Click **"Create API Token"**
3. Configurar:
   ```
   Token Name: grafired-laravel
   Permissions: ✅ Object Read & Write
   Apply to bucket: grafired-uploads
   TTL: Sin expiración
   ```
4. Click **"Create API Token"**
5. **¡COPIAR AHORA!** (solo se muestran una vez):
   - `Access Key ID`: `xxxxxxxxxx`
   - `Secret Access Key`: `yyyyyyyyyyy`
   - `Endpoint`: `https://xxx.r2.cloudflarestorage.com`

### 3️⃣ Configurar Variables de Entorno

Agregar al final de tu archivo `.env`:

```env
# ========================================
# CLOUDFLARE R2 STORAGE
# ========================================
FILESYSTEM_DISK=r2

R2_ACCESS_KEY_ID=tu_access_key_id
R2_SECRET_ACCESS_KEY=tu_secret_access_key
R2_BUCKET=grafired-uploads
R2_ENDPOINT=https://xxx.r2.cloudflarestorage.com
R2_REGION=auto
R2_URL=https://pub-xxx.r2.dev
```

**⚠️ Reemplaza** los valores de ejemplo con tus credenciales reales.

### 4️⃣ Configurar Acceso Público (IMPORTANTE)

Para que las imágenes sean accesibles desde el navegador:

1. En Cloudflare R2 → Tu bucket `grafired-uploads`
2. Settings → **Public Access**
3. Click **"Allow Access"**
4. Copiar la URL pública: `https://pub-xxx.r2.dev`
5. Pegarla en `.env` como `R2_URL`

---

## 🔄 MIGRAR CÓDIGO DE IMÁGENES

### Archivos que suben imágenes en tu proyecto:

```php
// ANTES (local storage):
$path = $request->file('logo')->store('logos', 'public');

// DESPUÉS (R2 storage):
$path = $request->file('logo')->store('logos', 'r2');

// O mejor aún, usar el disco por defecto:
$path = $request->file('logo')->store('logos');
// (usará 'r2' automáticamente porque FILESYSTEM_DISK=r2)
```

### Archivos a Revisar:

1. **Company.php** - Logo de empresa
2. **User.php** - Avatar de usuario (si existe)
3. **Cualquier formulario Filament con FileUpload**

---

## 🧪 PROBAR CONFIGURACIÓN

### Test 1: Subir archivo desde tinker

```bash
php artisan tinker
```

```php
// Crear un archivo de prueba
Storage::disk('r2')->put('test.txt', 'Hola desde Cloudflare R2!');

// Verificar que existe
Storage::disk('r2')->exists('test.txt');
// Debe retornar: true

// Obtener URL pública
Storage::disk('r2')->url('test.txt');
// Debe retornar: https://pub-xxx.r2.dev/test.txt

// Eliminar archivo de prueba
Storage::disk('r2')->delete('test.txt');
```

### Test 2: Subir logo de empresa

1. Ir a Perfil de Empresa en Filament
2. Subir un logo
3. Verificar que aparece correctamente
4. Verificar en Cloudflare R2 dashboard que el archivo está ahí

---

## 📁 MIGRAR IMÁGENES EXISTENTES

Si ya tienes imágenes en `storage/app/public`:

### Opción A: Script Manual

```bash
php artisan tinker
```

```php
// Obtener todas las compañías con logo
$companies = \App\Models\Company::whereNotNull('logo')->get();

foreach ($companies as $company) {
    $localPath = storage_path('app/public/' . $company->logo);

    if (file_exists($localPath)) {
        // Leer archivo local
        $contents = file_get_contents($localPath);

        // Subir a R2
        Storage::disk('r2')->put($company->logo, $contents, 'public');

        echo "Migrado: {$company->logo}\n";
    }
}
```

### Opción B: Comando Artisan (recomendado)

Crear comando: `php artisan make:command MigrateToR2`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;

class MigrateToR2 extends Command
{
    protected $signature = 'storage:migrate-to-r2';
    protected $description = 'Migrate local storage files to Cloudflare R2';

    public function handle()
    {
        $this->info('Migrando archivos a Cloudflare R2...');

        // Migrar logos de empresas
        $companies = Company::whereNotNull('logo')->get();
        $bar = $this->output->createProgressBar($companies->count());

        foreach ($companies as $company) {
            $localPath = storage_path('app/public/' . $company->logo);

            if (file_exists($localPath)) {
                $contents = file_get_contents($localPath);
                Storage::disk('r2')->put($company->logo, $contents, 'public');
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Migración completada!');
    }
}
```

Ejecutar: `php artisan storage:migrate-to-r2`

---

## 🌐 CONFIGURAR EN RAILWAY

### Variables de Entorno en Railway:

1. Ir a tu proyecto en Railway
2. Settings → Variables
3. Agregar:
   ```
   FILESYSTEM_DISK=r2
   R2_ACCESS_KEY_ID=tu_access_key
   R2_SECRET_ACCESS_KEY=tu_secret_key
   R2_BUCKET=grafired-uploads
   R2_ENDPOINT=https://xxx.r2.cloudflarestorage.com
   R2_REGION=auto
   R2_URL=https://pub-xxx.r2.dev
   ```
4. Redesplegar aplicación

---

## ✅ VERIFICACIÓN FINAL

### Checklist:

- [ ] Cuenta Cloudflare R2 creada
- [ ] Bucket `grafired-uploads` creado
- [ ] API Token creado y guardado
- [ ] Variables en `.env` configuradas
- [ ] Acceso público habilitado en bucket
- [ ] Test desde tinker funciona
- [ ] Logo de empresa sube correctamente
- [ ] Imágenes existentes migradas
- [ ] Railway configurado con variables R2
- [ ] Aplicación redesplegada en Railway
- [ ] Verificado que logos se ven en producción

---

## 💰 COSTOS

### Cloudflare R2 - Plan Gratuito:
- ✅ 10 GB almacenamiento gratis/mes
- ✅ Sin costos de transferencia (egreso)
- ✅ 10 millones de operaciones Clase A gratis/mes
- ✅ 100 millones de operaciones Clase B gratis/mes

### Más allá del plan gratuito:
- $0.015 por GB adicional/mes
- Sin costos de bandwidth (¡enorme ahorro vs S3!)

---

## 🆘 TROUBLESHOOTING

### Error: "Credentials are required"
✅ Verificar que las variables R2_ACCESS_KEY_ID y R2_SECRET_ACCESS_KEY están en .env

### Error: "The specified bucket does not exist"
✅ Verificar que R2_BUCKET coincide con el nombre exacto en Cloudflare

### Error: "SignatureDoesNotMatch"
✅ Verificar que R2_ENDPOINT es correcto (debe incluir https://)
✅ Verificar que las credenciales no tengan espacios al inicio/final

### Imágenes no se ven (404)
✅ Verificar que el acceso público está habilitado en el bucket
✅ Verificar que R2_URL está configurada correctamente

### Limpiar caché después de cambios:
```bash
php artisan config:clear
php artisan cache:clear
```

---

## 📚 RECURSOS

- [Cloudflare R2 Docs](https://developers.cloudflare.com/r2/)
- [Laravel Filesystem Docs](https://laravel.com/docs/filesystem)
- [Flysystem S3 Adapter](https://flysystem.thephpleague.com/docs/adapter/aws-s3/)

---

**¿Necesitas ayuda?** Contacta a soporte de Cloudflare o revisa los logs de Laravel:
```bash
tail -f storage/logs/laravel.log
```
