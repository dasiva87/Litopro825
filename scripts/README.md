# 📁 Scripts Directory

Este directorio contiene scripts auxiliares para testing, debugging y deployment del proyecto LitoPro825 (GrafiRed 3.0).

---

## 📂 Estructura

```
scripts/
├── tests/          → Scripts de prueba y debugging
├── deploy/         → Scripts de deployment y mantenimiento
└── README.md       → Esta documentación
```

---

## 🧪 Tests (`/scripts/tests/`)

Scripts para probar funcionalidades específicas del sistema. **NO ejecutar en producción.**

### Scripts de Email/Notificaciones
- `test_email.php` - Prueba básica de envío de emails
- `test_email_now.php` - Prueba inmediata de configuración SMTP
- `test_final_email.php` - Prueba de email con formato final
- `test_mail_debug.php` - Debug detallado de configuración de mail
- `test_notification_direct.php` - Prueba directa de notificaciones
- `test-notifications.sh` - Script bash para testing de notificaciones
- `test-notifications-ui.sh` - Testing de notificaciones con UI

### Scripts de Órdenes de Compra
- `test_purchase_order_creation.php` - Crear orden de compra de prueba
- `test_purchase_order_email.php` - Probar email de orden de compra

### Scripts de Solicitudes Comerciales
- `test_commercial_request.php` - Crear solicitud comercial de prueba
- `test_approve_request.php` - Aprobar solicitud comercial

### Scripts de Items y Acabados
- `test_simple_item_finishings.php` - Probar cálculo de acabados en SimpleItems

### Scripts de Demo/Debug
- `demo_flujo_completo.php` - Demo del flujo completo del sistema
- `debug-resources.php` - Debug de recursos de Filament
- `test-new-system.php` - Probar sistema nuevo
- `install-new-commercial-system.php` - Instalar sistema comercial

### Uso
```bash
# Desde la raíz del proyecto
php scripts/tests/test_email.php
bash scripts/tests/test-notifications.sh
```

---

## 🚀 Deploy (`/scripts/deploy/`)

Scripts para deployment y mantenimiento en producción.

### Scripts Disponibles
- `deploy.sh` - Script principal de deployment
- `clear-production-cache.sh` - Limpiar cachés en producción
- `START_SESSION.sh` - Iniciar sesión de desarrollo

### Uso
```bash
# Deployment
bash scripts/deploy/deploy.sh

# Limpiar cachés en producción
bash scripts/deploy/clear-production-cache.sh

# Iniciar sesión de desarrollo
bash scripts/deploy/START_SESSION.sh
```

---

## ⚠️ Advertencias

1. **Scripts de tests:**
   - NO ejecutar en producción
   - Pueden crear datos de prueba en la BD
   - Algunos requieren configuración de .env

2. **Scripts de deploy:**
   - Verificar permisos antes de ejecutar
   - Hacer backup antes de deploy
   - Revisar logs después de ejecutar

3. **Seguridad:**
   - No versionar estos scripts con datos sensibles
   - No exponer endpoints de debug en producción
   - Eliminar datos de prueba regularmente

---

## 📝 Mantenimiento

- **Revisar periódicamente:** Determinar qué scripts siguen siendo útiles
- **Convertir a PHPUnit:** Scripts de prueba deberían migrarse a tests unitarios
- **Documentar cambios:** Actualizar este README al agregar nuevos scripts
- **Eliminar obsoletos:** Borrar scripts que ya no se usan

---

**Última actualización:** 10 de Enero 2026
