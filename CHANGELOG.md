# Changelog

Todos los cambios notables de GrafiRed 3.0 serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Versionamiento Semántico](https://semver.org/lang/es/).

## [Unreleased]

### Por Desarrollar
- Dashboard de analytics con gráficas
- Reportes exportables a Excel
- Notificaciones push en tiempo real
- Módulo de facturación electrónica

---

## [1.0.0] - 2026-01-04

### 🎉 Lanzamiento Inicial

Primer release de producción de GrafiRed 3.0 - SaaS Multi-tenant para Litografías.

### Agregado

#### Módulos Core
- **Multi-tenancy**: Sistema completo de tenants por `company_id`
- **Autenticación**: Login, registro, recuperación de contraseña en español
- **Perfiles de Usuario**: Gestión de perfil con avatar
- **Sistema de Permisos**: Roles y políticas con Spatie Permission

#### Gestión de Contactos
- **Clientes y Proveedores**: CRUD completo con información de contacto
- **Dual Contact**: Contactos que pueden ser cliente y proveedor simultáneamente
- **Solicitudes Comerciales**: Workflow de aprobación/rechazo
- **Integración Grafired**: Búsqueda de clientes desde API externa

#### Documentos y Ventas
- **Cotizaciones**: Creación, edición, estados (Draft, Sent, In Progress, Completed, Cancelled)
- **Órdenes de Pedido**: Workflow completo con estados unificados
- **Órdenes de Producción**: Gestión de impresión y acabados
- **Cuentas de Cobro**: Workflow de aprobación y pago (Draft, Sent, Approved, Paid, Cancelled)
- **Envío Manual de Emails**: Todas las órdenes y cuentas con botón de envío manual
- **Generación de PDFs**: PDFs personalizados con logo de empresa para todos los documentos

#### Inventario
- **Papeles**: Gestión de papeles con precios y stock
- **Máquinas**: Catálogo de máquinas de impresión
- **Items Digitales**: Productos digitales del catálogo
- **Items Simples**: Productos básicos para cotizaciones
- **Magazine Items**: Items complejos con 17+ campos (tintas, barniz, formato, etc.)
- **Talonarios**: Items especializados para talonarios

#### Stock y Movimientos
- **Dashboard de Stock**: Página consolidada con 3 tabs (Resumen, Movimientos, Alertas)
- **Widgets de Stock**:
  - Stock Overview (total items, valor)
  - Top 5 Items (más stock)
  - Valor por Categoría
  - Movimientos Recientes
  - Resumen Mensual
  - Items con Bajo Stock
  - Historial Completo
  - Alertas Críticas
  - Quick Actions (entrada/salida rápida)
- **Alertas de Stock**: Sistema de notificación por bajo inventario
- **Movimientos**: Registro completo de entradas y salidas

#### Sistema de Acabados (Finishing)
- **Acabados**: Catálogo de acabados (laminado, barniz, troquel, etc.)
- **Asignación a Productos**: Acabados en órdenes de producción
- **Proveedores de Acabados**: Gestión automática de proveedores especializados
- **Pricing**: Cálculo de costos de acabados

#### Notificaciones y Comunicación
- **Notificaciones Internas**: Sistema de notificaciones en base de datos
- **Auto-marcado**: Notificaciones se marcan como leídas automáticamente al ver
- **Limpieza Automática**: Notificaciones leídas >30 días se eliminan diariamente
- **Emails Manuales**: Envío controlado de emails (no automáticos)
- **Templates de Email**: Diseños personalizados para cada tipo de documento

#### Panel de Super Admin
- **Activity Logs**: Registro completo de actividades del sistema
- **Gestión de Empresas**: Administración de tenants
- **Configuración Global**: Settings del sistema

#### UX y UI
- **Vistas Limpias**: Layout de 2 columnas sin títulos de sección
- **Fondo Azul en Items**: Color distintivo (#e9f3ff) para tablas de items
- **ActionGroup**: Menús desplegables de 3 puntos para acciones
- **Sidebar Personalizado**: Color y scrollbar custom
- **Responsive**: Diseño adaptable a móviles y tablets
- **Tema Nord**: Paleta de colores profesional

### Técnico

#### Stack
- **Laravel**: 12.37.0
- **PHP**: 8.3.21
- **Filament**: 4.2.0
- **Livewire**: 3.6.4
- **TailwindCSS**: 4.1.12
- **MySQL**: Base de datos principal

#### Paquetes Principales
- `filament/filament`: ^4.0 - Panel de administración
- `spatie/laravel-permission`: ^6.21 - Permisos y roles
- `barryvdh/laravel-dompdf`: ^3.1 - Generación de PDFs
- `laravel/cashier`: ^15.7 - Pagos (preparado para futuro)
- `lab404/laravel-impersonate`: ^1.7 - Suplantación de usuarios

#### Comandos Artisan Custom
- `grafired:setup-demo --fresh`: Setup completo con datos demo
- `grafired:clean-notifications`: Limpieza de notificaciones antiguas

#### Migraciones Importantes
- Multi-tenant scopes automáticos
- Sistema de estados unificado (ENUM)
- Tracking de emails enviados (`email_sent_at`, `email_sent_by`)
- Activity logs con Spatie Activitylog

#### Testing
- 150+ pruebas manuales documentadas en `pruebas-manuales.md`
- PHPUnit configurado
- Laravel Pint para code style

### Seguridad
- Políticas de acceso por tenant (ningún tenant ve datos de otro)
- Validaciones exhaustivas en formularios
- Protección CSRF en todos los forms
- Password hashing con bcrypt
- Autenticación con Laravel Sanctum
- Activity logs de todas las acciones importantes

### Optimizaciones
- Eager loading para prevenir N+1 queries
- Cachés de configuración, rutas y vistas
- Assets compilados y minificados
- Autoload optimizado con Composer

### Documentación
- `README.md`: Documentación del proyecto
- `CLAUDE.md`: Instrucciones de desarrollo y sprints completados
- `DEPLOYMENT-GUIDE.md`: Guía completa de despliegue
- `pruebas-manuales.md`: Checklist de testing
- `CHANGELOG.md`: Este archivo

### Conocidos Issues
- CompanyType enum usa método `label()` legacy (no afecta funcionalidad)
- Algunos enums pendientes de migrar a interfaces Filament

---

## Formato de Versiones Futuras

### [X.Y.Z] - YYYY-MM-DD

#### Agregado
- Nuevas features

#### Cambiado
- Cambios en features existentes

#### Deprecado
- Features que se eliminarán próximamente

#### Eliminado
- Features eliminadas

#### Corregido
- Bug fixes

#### Seguridad
- Parches de seguridad

---

**Mantenido por**: GrafiRed Team
**Licencia**: Propietario
