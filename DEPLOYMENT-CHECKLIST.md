# ✅ CHECKLIST DE DEPLOYMENT A PRODUCCIÓN

**Fecha**: Enero 2026  
**Versión**: GrafiRed 3.0  
**Estado**: Listo para Producción

---

## 📋 PRE-DEPLOYMENT (Antes de subir a servidor)

### Código y Configuración
- [x] `.env.example` actualizado con todas las variables necesarias
- [x] `README.md` creado con instrucciones de deployment
- [x] `PRODUCCION-SETUP.md` con guía completa
- [x] `MinimalProductionSeeder` creado (sin datos de prueba)
- [x] `ProductionSeeder` configurado para llamar a `MinimalProductionSeeder`
- [x] `DatabaseSeeder` detecta ambiente automáticamente
- [x] Sintaxis PHP validada sin errores
- [ ] Commit a rama `main` completado

### Archivos Modificados en Esta Sesión
1. `.env.example` - Variables de producción agregadas
2. `ProductionSeeder.php` - Simplificado para llamar a MinimalProductionSeeder
3. `README.md` - Guía de deployment creado
4. `SimpleItemQuickHandler.php` - Resumen de precios movido después de acabados
5. `SimpleItemForm.php` - Vista previa de montaje mejorada (3 columnas)

### Archivos Nuevos Creados
1. `MinimalProductionSeeder.php` - Seeder limpio de producción
2. `PRODUCCION-SETUP.md` - Documentación completa
3. `README.md` - Guía rápida
4. `DEPLOYMENT-CHECKLIST.md` - Este archivo

---

## 🚀 DEPLOYMENT EN SERVIDOR

### 1. Servidor y Requisitos
- [ ] PHP 8.3+ instalado
- [ ] MySQL 8.0+ configurado
- [ ] Composer 2.x instalado
- [ ] Node.js 18+ y npm instalados
- [ ] Dominio configurado con DNS
- [ ] Certificado SSL/HTTPS activo

### 2. Instalación Inicial
```bash
# Clonar repositorio
git clone <repo-url> grafired
cd grafired

# Instalar dependencias (sin dev)
composer install --optimize-autoloader --no-dev

# Compilar assets para producción
npm install
npm run build
```

### 3. Configuración de Ambiente
- [ ] Copiar `.env.example` a `.env`
- [ ] Generar `APP_KEY`: `php artisan key:generate`
- [ ] Configurar `APP_ENV=production`
- [ ] Configurar `APP_DEBUG=false`
- [ ] Configurar `APP_URL` con dominio real

### 4. Base de Datos MySQL
- [ ] Base de datos creada
- [ ] Usuario MySQL creado con permisos
- [ ] Variables configuradas en `.env`:
  - `DB_CONNECTION=mysql`
  - `DB_HOST=127.0.0.1`
  - `DB_DATABASE=grafired_prod`
  - `DB_USERNAME=usuario_mysql`
  - `DB_PASSWORD=password_seguro`

### 5. Migraciones y Seeders
```bash
# Ejecutar migraciones
php artisan migrate --force

# Ejecutar seeder de producción
php artisan db:seed --class=MinimalProductionSeeder
```

**Verificar que se creó**:
- [ ] 4 planes de suscripción (Free, Básico, Profesional, Empresarial)
- [ ] 5 roles (Super Admin, Company Admin, Manager, Salesperson, Operator)
- [ ] 1 usuario super-admin (`admin@grafired.com`)
- [ ] Datos geográficos (países, estados, ciudades)
- [ ] Tipos de documentos
- [ ] Acabados para talonarios

### 6. Configurar Resend (Emails)
- [ ] Cuenta de Resend creada
- [ ] API Key obtenida
- [ ] Variables configuradas en `.env`:
  - `MAIL_MAILER=resend`
  - `RESEND_API_KEY=tu_api_key`
  - `MAIL_FROM_ADDRESS="noreply@tudominio.com"`
  - `MAIL_FROM_NAME="GrafiRed"`
- [ ] Dominio verificado en Resend (si es necesario)
- [ ] Email de prueba enviado y recibido

### 7. Configurar Stripe (Pagos)
- [ ] Cuenta de Stripe creada
- [ ] Keys de producción obtenidas
- [ ] Variables configuradas en `.env`:
  - `STRIPE_KEY=pk_live_...`
  - `STRIPE_SECRET=sk_live_...`
  - `STRIPE_WEBHOOK_SECRET=whsec_...`
- [ ] 3 productos creados en Stripe:
  - Plan Básico ($150,000 COP/mes)
  - Plan Profesional ($300,000 COP/mes)
  - Plan Empresarial ($500,000 COP/mes)
- [ ] Price IDs actualizados en base de datos:
```sql
UPDATE plans SET stripe_price_id = 'price_xxxxx' WHERE slug = 'basico';
UPDATE plans SET stripe_price_id = 'price_yyyyy' WHERE slug = 'profesional';
UPDATE plans SET stripe_price_id = 'price_zzzzz' WHERE slug = 'empresarial';
```

### 8. Optimización de Producción
```bash
# Cachear configuraciones
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

# Optimizar autoloader
composer dump-autoload --optimize
```

### 9. Permisos de Archivos
```bash
# Storage y bootstrap/cache deben ser escribibles
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 10. Configurar Web Server (Nginx/Apache)
- [ ] Virtual host configurado
- [ ] Document root apuntando a `/public`
- [ ] Rewrite rules configuradas para Laravel
- [ ] SSL configurado (certbot/Let's Encrypt)
- [ ] HTTPS forzado (redirect de HTTP)

---

## 🔒 POST-DEPLOYMENT (Después de subir)

### Verificación Funcional
- [ ] Acceder a `https://tudominio.com` (redirect a login)
- [ ] Acceder a `https://tudominio.com/super-admin` (panel super-admin)
- [ ] Login con credenciales super-admin:
  - Email: `admin@grafired.com`
  - Password: `GrafiRed2026!`
- [ ] **CAMBIAR CONTRASEÑA INMEDIATAMENTE**

### Probar Funcionalidades Críticas
- [ ] Registro de nueva empresa funciona
- [ ] Selección de plan funciona
- [ ] Email de bienvenida llega
- [ ] Login como empresa creada funciona
- [ ] Crear cotización funciona
- [ ] PDF de cotización se genera
- [ ] Enviar email de cotización funciona

### Seguridad
- [ ] `APP_DEBUG=false` (verificar que no se muestren errores detallados)
- [ ] Contraseña super-admin cambiada
- [ ] HTTPS funcionando (candado verde en navegador)
- [ ] Headers de seguridad configurados
- [ ] Rate limiting activo en login
- [ ] CSRF protection verificado

### Backups
- [ ] Backup manual de base de datos inicial
- [ ] Backup automático diario configurado
- [ ] Retención de backups definida (mínimo 30 días)
- [ ] Backup de archivos/storage configurado

### Monitoreo
- [ ] Logs de Laravel funcionando (`storage/logs`)
- [ ] Monitoreo de errores configurado (opcional: Sentry)
- [ ] Uptime monitoring configurado (opcional)
- [ ] Analytics configurado (opcional: Google Analytics)

---

## 🎯 FLUJO DE PRUEBA COMPLETO

### Como Super Admin
1. Login en `/super-admin`
2. Verificar que se muestran 4 planes activos
3. Verificar activity logs funcionando
4. Crear una empresa de prueba desde panel público
5. Verificar que la empresa aparece en panel super-admin

### Como Nueva Empresa
1. Ir a `/admin/register`
2. Completar formulario de registro
3. Seleccionar "Plan Gratuito" (sin pago)
4. Verificar redirección a dashboard
5. Verificar límites del plan:
   - 1 usuario máximo
   - 10 cotizaciones/mes máximo
   - 20 productos máximo
6. Crear cotización de prueba
7. Enviar email de cotización
8. Verificar recepción de email

### Testing de Pagos (Stripe Test Mode)
1. Registrar empresa con plan de pago
2. Usar tarjeta de prueba: `4242 4242 4242 4242`
3. Verificar suscripción creada en Stripe
4. Verificar webhook funciona
5. Cancelar suscripción
6. Verificar downgrade automático

---

## 🚨 TROUBLESHOOTING

### Error: 500 Internal Server Error
**Causa**: Permisos incorrectos o configuración mal cacheada  
**Solución**:
```bash
chmod -R 775 storage bootstrap/cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Error: Base de datos no conecta
**Causa**: Credenciales incorrectas en `.env`  
**Solución**:
```bash
# Verificar conexión
php artisan db:show

# Limpiar caché de config
php artisan config:clear
```

### Error: Emails no llegan
**Causa**: Resend API key inválida o dominio no verificado  
**Solución**:
```bash
# Probar envío manual
php artisan tinker
Mail::raw('Test', fn($msg) => $msg->to('test@email.com')->subject('Test'));
```

### Error: Stripe no funciona
**Causa**: Webhook secret incorrecto  
**Solución**:
- Verificar webhook configurado en Stripe dashboard
- URL: `https://tudominio.com/stripe/webhook`
- Eventos: `customer.subscription.*`, `invoice.*`

---

## 📞 CONTACTOS DE EMERGENCIA

**Developer**: [Tu nombre]  
**Email**: [tu@email.com]  
**Documentación**: Ver `PRODUCCION-SETUP.md`

---

## ✅ CHECKLIST FINAL

Antes de declarar el deployment exitoso, verificar:

- [ ] Todas las secciones de este checklist completadas
- [ ] Super admin puede acceder y contraseña cambiada
- [ ] Nueva empresa puede registrarse
- [ ] Emails funcionan correctamente
- [ ] Pagos Stripe funcionan (modo test)
- [ ] Backups configurados
- [ ] Monitoreo activo
- [ ] Documentación entregada al cliente
- [ ] Credenciales guardadas en gestor de contraseñas

---

**Deployment completado por**: _______________  
**Fecha**: _______________  
**Firma**: _______________

