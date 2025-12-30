# LitoPro 3.0 - SaaS para Litografías

## Stack & Arquitectura
- **Laravel 12.25.0 + PHP 8.3.21 + Filament 4.0.3 + MySQL**
- **Multi-tenant**: Scopes automáticos por `company_id`
- **Frontend**: Livewire 3.6.4 + TailwindCSS 4.1.12

## Comandos Core
```bash
php artisan test                    # Testing completo
php artisan pint && composer analyse    # Lint + análisis
php artisan migrate && php artisan db:seed  # Setup BD
php artisan litopro:setup-demo --fresh     # Demo completo
```

## Convenciones Filament v4

### Namespaces Críticos
- **Layout**: `Filament\Schemas\Components\*` (Section, Grid, Tab)
- **Forms**: `Filament\Forms\Components\*` (TextInput, Select, etc.)
- **Actions**: `Filament\Actions\*` (NO Tables\Actions ni Pages\Actions)
- **Columns**: `Filament\Tables\Columns\*`
- **FileUpload**: SIEMPRE usar `->disk('public')` para archivos públicos
- **Componentes Nativos**: Usar `<x-filament::icon>`, `<x-filament::badge>`, `<x-filament::button>`

### Estructura Resources
```
app/Filament/Resources/[Entity]/
├── [Entity]Resource.php
├── Schemas/[Entity]Form.php
├── Tables/[Entity]sTable.php
└── Pages/
```

---

## PROGRESO RECIENTE

### ✅ Sesión Completada (30-Dic-2025 - Continuación 2)
**SPRINT 30: Consolidación de Páginas de Stock en una Sola**

#### Logros de la Sesión

1. **✅ Página Stock.php Unificada**
   - **Nueva página**: `Stock.php` con todos los widgets consolidados
   - **3 Tabs**: Resumen, Movimientos, Alertas
   - **3 Header Actions**: Actualizar Datos, Ver Alertas, Nuevo Movimiento
   - **9 Widgets organizados**: 3 en header, 6 en tabs

2. **✅ Vista con Tabs Interactivos**
   - **Componentes Filament**: Uso de `<x-filament::tabs>` nativo
   - **Navegación dinámica**: Cambio de tab con Livewire
   - **3 tabs organizados**:
     - Resumen: Tendencias + Productos más consumidos
     - Movimientos: Tabla completa + Movimientos recientes
     - Alertas: Tabla de alertas críticas

3. **✅ Limpieza de Archivos Obsoletos**
   - **2 páginas eliminadas**: StockManagement, StockMovements
   - **2 vistas eliminadas**: stock-management.blade.php, stock-movements.blade.php
   - **3 widgets eliminados**: StockKpisWidget, StockLevelTrackingWidget, StockPredictionsWidget

4. **✅ Navegación Simplificada**
   - **Antes**: 3 entradas en menú Stock + 1 entrada "Clientes y Proveedores"
   - **Ahora**: 1 entrada "Stock" con tabs internos
   - **Resources ocultos**:
     - StockAlertResource (accesible desde botón "Ver Alertas")
     - ContactResource (accesible desde ClientResource y SupplierResource)
   - **Beneficio**: Menú más limpio, menos clutter, mejor UX

5. **✅ Badge de Solicitudes Pendientes**
   - **Contador dinámico**: Muestra número de solicitudes comerciales sin responder
   - **Color warning**: Badge amarillo/naranja cuando hay solicitudes pendientes
   - **Filtrado correcto**: Solo cuenta solicitudes recibidas (target_company_id) en estado 'pending'
   - **Beneficio**: Visibilidad inmediata de solicitudes que requieren atención

6. **✅ Gestión Completa de Solicitudes Comerciales**
   - **Página de visualización**: Click en solicitud para ver detalle completo
   - **Botones de acción**: Aprobar/Rechazar en header de la página
   - **Formulario detallado**: Muestra toda la información de la solicitud
   - **Acciones con confirmación**: Modales de confirmación antes de aprobar/rechazar
   - **Mensajes personalizados**: Campo para agregar mensaje de bienvenida o rechazo
   - **Redirección automática**: Vuelve al listado después de gestionar
   - **Beneficio**: Gestión intuitiva y completa de solicitudes comerciales

#### Archivos Creados (Sprint 30)

**Páginas (1)**:
1. `app/Filament/Pages/Stock.php` - Página unificada con tabs

**Vistas (1)**:
2. `resources/views/filament/pages/stock.blade.php` - Vista con 3 tabs

**Gestión de Solicitudes Comerciales (3)**:
3. `app/Filament/Pages/CommercialRequests/ViewCommercialRequest.php` - Página de visualización
4. `app/Filament/Resources/CommercialRequests/Schemas/CommercialRequestViewSchema.php` - Schema de formulario
5. `app/Filament/Resources/CommercialRequests/` - Directorio de schemas creado

**Total Sprint 30**: 5 archivos nuevos

#### Archivos Modificados (Sprint 30)

**Resources Ocultos del Menú (2)**:
1. `app/Filament/Resources/StockAlertResource.php`
   - Agregado `shouldRegisterNavigation() => false`
   - Oculto del menú lateral (accesible solo desde botón "Ver Alertas")
2. `app/Filament/Resources/Contacts/ContactResource.php`
   - Agregado `shouldRegisterNavigation() => false`
   - Oculto del menú lateral (accesible desde Clientes y Proveedores específicos)

**Acción "Nuevo Movimiento" (1)**:
3. `app/Filament/Pages/Stock.php`
   - Fix: Cambiado `->relationship()` a `->options()` con closure
   - Corregido error "hasAttribute() on null"

**Badge y Gestión de Solicitudes (1)**:
4. `app/Filament/Resources/CommercialRequestResource.php`
   - Agregado `getNavigationBadge()` - contador de solicitudes pendientes
   - Agregado `getNavigationBadgeColor()` - color 'warning' cuando hay pendientes
   - Agregado `form()` - usa CommercialRequestViewSchema
   - Agregado página 'view' en getPages()
   - Agregado `->recordUrl()` - filas clicables para ver detalle
   - Filtra por `target_company_id` (solicitudes recibidas) y `status='pending'`

**Total Sprint 30**: 4 archivos modificados

#### Archivos Eliminados (Sprint 30)

**Páginas Antiguas (2)**:
1. `app/Filament/Pages/StockManagement.php`
2. `app/Filament/Pages/StockMovements.php`

**Vistas Antiguas (2)**:
3. `resources/views/filament/pages/stock-management.blade.php`
4. `resources/views/filament/pages/stock-movements.blade.php`

**Widgets Obsoletos (3)**:
5. `app/Filament/Widgets/StockKpisWidget.php` - Reemplazado por SimpleStockKpisWidget
6. `app/Filament/Widgets/StockLevelTrackingWidget.php` - No utilizado
7. `app/Filament/Widgets/StockPredictionsWidget.php` - No utilizado

**Total Sprint 30**: 7 archivos eliminados

#### Estructura de la Nueva Página Stock

```
┌─────────────────────────────────────────────────────────┐
│  STOCK - Dashboard Unificado                            │
├─────────────────────────────────────────────────────────┤
│  [Header Actions]                                        │
│  • Actualizar Datos (refresh alertas)                   │
│  • Ver Alertas (→ StockAlertResource)                   │
│  • Nuevo Movimiento (modal)                             │
├─────────────────────────────────────────────────────────┤
│  HEADER WIDGETS (3):                                     │
│  ┌──────────────┬──────────────┬──────────────┐         │
│  │ Simple Stock │ Movements    │ Stock Alerts │         │
│  │ KPIs         │ KPIs         │ Widget       │         │
│  └──────────────┴──────────────┴──────────────┘         │
├─────────────────────────────────────────────────────────┤
│  TABS:                                                   │
│  [Resumen] [Movimientos] [Alertas]                      │
│                                                          │
│  TAB 1 - RESUMEN:                                        │
│  • StockTrendsChartWidget (gráfico tendencias)          │
│  • TopConsumedProductsWidget (tabla)                    │
│                                                          │
│  TAB 2 - MOVIMIENTOS:                                    │
│  • StockMovementsTableWidget (historial completo)       │
│  • RecentMovementsWidget (últimos movimientos)          │
│                                                          │
│  TAB 3 - ALERTAS:                                        │
│  • CriticalAlertsTableWidget (alertas críticas)         │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

#### Widgets Finales (6 activos)

**Header Widgets (3)**:
1. `SimpleStockKpisWidget` - KPIs con sparklines y colores dinámicos
2. `StockMovementsKpisWidget` - Estadísticas de movimientos
3. `StockAlertsWidget` - Alertas de stock (crítico, bajo, sin stock, costo)

**Tab Widgets (6)**:
4. `StockTrendsChartWidget` - Gráfico de tendencias (Tab Resumen)
5. `TopConsumedProductsWidget` - Productos más consumidos (Tab Resumen)
6. `StockMovementsTableWidget` - Historial completo (Tab Movimientos)
7. `RecentMovementsWidget` - Últimos movimientos (Tab Movimientos)
8. `CriticalAlertsTableWidget` - Alertas críticas (Tab Alertas)

**No utilizados (1)**:
9. `StockAlertsStatsWidget` - Stats de alertas (similar a StockAlertsWidget)

#### Testing Realizado

```bash
✅ Página Stock.php creada sin errores
✅ Vista con tabs renderiza correctamente
✅ 2 páginas antiguas eliminadas
✅ 2 vistas antiguas eliminadas
✅ 3 widgets obsoletos eliminados
✅ Sintaxis PHP correcta (php -l)
✅ Caché limpiada (config, views, filament)
✅ Laravel ejecutándose sin errores
```

#### Beneficios de la Consolidación

**UX Mejorado**:
- ✅ **1 entrada en menú** vs 2 anteriores
- ✅ **Tabs organizados** por tipo de información
- ✅ **Todo accesible** desde una sola URL
- ✅ **Navegación lógica** entre secciones

**Código Limpio**:
- ✅ **Menos archivos**: 7 archivos eliminados
- ✅ **Sin duplicación**: Widgets obsoletos removidos
- ✅ **Mejor organización**: Lógica unificada en una página

**Mantenimiento**:
- ✅ **Centralizado**: Un solo lugar para modificar
- ✅ **Reutilización**: Widgets compartidos entre tabs
- ✅ **Escalable**: Fácil agregar nuevos tabs o widgets

#### Diferencias vs Páginas Separadas

**Navegación:**
- **Antes**: "Gestión de Stock" + "Movimientos de Stock" + "Alertas de Stock" (3 items menú)
- **Ahora**: "Stock" con tabs internos (1 item menú visible)
- **StockAlertResource**: Oculto del menú (accesible vía botón)
- **Beneficio**: Menú más limpio, navegación intuitiva

**Widgets:**
- **Antes**: 9 widgets dispersos en 2 páginas
- **Ahora**: 9 widgets organizados en 3 tabs (mismo contenido, mejor organización)
- **Beneficio**: Misma funcionalidad, mejor accesibilidad

**Código:**
- **Antes**: 2 clases PHP + 2 vistas Blade + 3 widgets obsoletos
- **Ahora**: 1 clase PHP + 1 vista Blade + 6 widgets activos
- **Beneficio**: Menos archivos, menos mantenimiento

---

### ✅ Sesión Completada (30-Dic-2025 - Continuación)
**SPRINT 29: Eliminación Completa del Sistema de Notificaciones UI**

#### Contexto y Decisión

**Problema Original (Sprint 28)**:
- Sistema de notificaciones UI implementado con JavaScript auto-marcado, Observer, API routes
- **Issue crítico**: Notificaciones no se renderizaban en el dropdown de Filament
- Base de datos correcta, contador correcto, pero dropdown mostraba "No hay notificaciones"
- Múltiples intentos de fix no resolvieron el problema de renderizado

**Decisión del Usuario**:
> "elimina el sistema de notificaciones y solo deja el envio de correo, ya que las notificaciones están presentando problemas para renderizarse y gestionarse"

#### Logros de la Sesión

1. **✅ Eliminación Completa del Sistema UI**
   - **8 archivos eliminados**: Controller, Middleware, Observer, Livewire, JavaScript, vistas
   - **11 notificaciones actualizadas**: Todas ahora usan solo canal `['mail']`
   - **4 archivos de configuración limpiados**: Routes, assets, providers
   - **Assets recompilados**: Vite build sin código de notificaciones

2. **✅ Preservación de Funcionalidad Email**
   - **Templates intactos**: 6 vistas en `resources/views/emails/` sin cambios
   - **Adjuntos PDF**: Funcionalidad de PDFs preservada
   - **Mailtrap config**: Sin modificaciones, emails funcionan normalmente
   - **Métodos `toMail()`**: Todos los métodos de notificación preservados

3. **✅ Sistema de Notificaciones - Solo Email**
   - **11 tipos de notificaciones** configuradas para email únicamente
   - **Canal único**: `['mail']` en todos los casos
   - **Sin polling**: Eliminado polling de 30s de Filament
   - **Sin base de datos**: No se guardan notificaciones en tabla `notifications`

#### Archivos Eliminados (Sprint 29)

**Backend (4)**:
1. `app/Http/Controllers/NotificationController.php` - API endpoints (mark-as-read, etc.)
2. `app/Http/Middleware/MarkNotificationsAsRead.php` - Middleware de auto-marcado
3. `app/Observers/DatabaseNotificationObserver.php` - Observer de eventos
4. `app/Livewire/NotificationTrigger.php` - Componente Livewire

**Frontend (2)**:
5. `resources/js/filament-notifications.js` - JavaScript interceptor (250+ líneas)
6. `resources/views/filament/hooks/notifications-script.blade.php` - RenderHook

**Vistas (1)**:
7. `resources/views/livewire/notification-trigger.blade.php` - Template Livewire

**Comandos (1)**:
8. `app/Console/Commands/CleanupOldNotifications.php` - Artisan cleanup command

**Total Sprint 29**: 8 archivos eliminados

#### Archivos Modificados (Sprint 29)

**Notificaciones - Canal Email Only (11)**:
1. `app/Notifications/StockAlertNotification.php` - `['database']` → `['mail']`
2. `app/Notifications/CollectionAccountStatusChanged.php` - `['mail', 'database']` → `['mail']`
3. `app/Notifications/CommercialRequestReceived.php` - `['database', 'mail']` → `['mail']`
4. `app/Notifications/CommercialRequestApproved.php` - `['database', 'mail']` → `['mail']`
5. `app/Notifications/CommercialRequestRejected.php` - `['database', 'mail']` → `['mail']`
6. `app/Notifications/PurchaseOrderCreated.php` - `['database']` → `['mail']`
7. `app/Notifications/QuoteSent.php` - `['mail', 'database']` → `['mail']`
8. `app/Notifications/PurchaseOrderStatusChanged.php` - `['mail', 'database']` → `['mail']`
9. `app/Notifications/ProductionOrderSent.php` - `['mail', 'database']` → `['mail']`
10. `app/Notifications/CollectionAccountSent.php` - `['database']` → `['mail']`
11. `app/Notifications/PurchaseOrderDigest.php` - Ya era `['mail']` (sin cambios)

**Configuración Limpiada (4)**:
12. `resources/js/app.js`
    - Eliminado: `import './filament-notifications.js';`

13. `app/Providers/Filament/AdminPanelProvider.php`
    - Eliminado: `->renderHook('panels::body.end', fn () => view(...))`
    - Líneas 131-134 removidas

14. `routes/web.php`
    - Eliminadas 5 rutas API: mark-as-read, mark-all-as-read, unread-count, destroy, cleanup
    - Líneas 118-130 removidas

15. `routes/console.php`
    - Eliminado: `Schedule::command('notifications:cleanup --read-only')`
    - Scheduler semanal removido

16. `app/Providers/AppServiceProvider.php`
    - Eliminado: `DatabaseNotificationObserver::class` del boot()
    - Método `boot()` ahora vacío

**Configuración Final (1)**:
17. `app/Providers/Filament/AdminPanelProvider.php` (segunda limpieza)
    - Eliminado: `->databaseNotifications()`
    - Eliminado: `->databaseNotificationsPolling('30s')`
    - Líneas 93-94 removidas (campana de notificaciones del menú)

**Total Sprint 29**: 17 archivos modificados

#### Cambios en Código

**Antes (Sprint 28) - Dual Channel**:
```php
// app/Notifications/QuoteSent.php
public function via(object $notifiable): array
{
    return ['mail', 'database']; // Email + UI
}

public function toDatabase(object $notifiable): array
{
    return [
        'format' => 'filament',
        'title' => 'Nueva Cotización Enviada',
        'body' => "Se envió la cotización #{$this->document->number}...",
        // ...
    ];
}
```

**Ahora (Sprint 29) - Email Only**:
```php
// app/Notifications/QuoteSent.php
public function via(object $notifiable): array
{
    return ['mail']; // Solo email
}

// Método toDatabase() eliminado (no necesario)
```

#### Sistema de Notificaciones - Configuración Final

**11 Tipos de Notificaciones (Email Only)**:

```
DOCUMENTOS (4):
├── QuoteSent - Cotización enviada (mail + PDF)
├── PurchaseOrderCreated - Orden de pedido creada (mail)
├── CollectionAccountSent - Cuenta de cobro enviada (mail + PDF)
└── ProductionOrderSent - Orden de producción enviada (mail + PDF)

CAMBIOS DE ESTADO (2):
├── PurchaseOrderStatusChanged - Cambio de estado orden pedido (mail)
└── CollectionAccountStatusChanged - Cambio de estado cuenta cobro (mail)

RED GRAFIRED (3):
├── CommercialRequestReceived - Solicitud comercial recibida (mail)
├── CommercialRequestApproved - Solicitud aprobada (mail)
└── CommercialRequestRejected - Solicitud rechazada (mail)

INVENTARIO (1):
└── StockAlertNotification - Alerta de stock (mail, ShouldQueue)

PERIÓDICAS (1):
└── PurchaseOrderDigest - Resumen diario de órdenes (mail, scheduled)
```

**Configuración Filament (Limpiada)**:
```php
// app/Providers/Filament/AdminPanelProvider.php

// ❌ REMOVIDO:
// ->databaseNotifications()
// ->databaseNotificationsPolling('30s')

// ✅ ACTUAL: Sin notificaciones de base de datos
->globalSearch()
->sidebarCollapsibleOnDesktop()
->spa()
```

#### Testing Realizado

```bash
✅ 11 notificaciones verificadas con canal ['mail'] only
✅ Sin errores de sintaxis PHP
✅ Assets recompilados con npm run build
✅ 8 archivos eliminados correctamente
✅ 16 archivos modificados sin errores
✅ Configuración de routes limpiada
✅ AppServiceProvider sin Observer
✅ Filament sin polling de notificaciones
```

#### Ventajas de Solo Email

**Simplicidad**:
- ✅ **Menos código**: 8 archivos menos, 300+ líneas eliminadas
- ✅ **Sin JavaScript complejo**: No hay interceptors ni eventos
- ✅ **Sin polling**: No consume recursos del servidor

**Confiabilidad**:
- ✅ **Email estándar**: Protocolo confiable y probado
- ✅ **Sin problemas UI**: No hay issues de renderizado en Filament
- ✅ **Historial**: Los emails quedan en bandeja de entrada

**Mantenimiento**:
- ✅ **Menos dependencias**: No depende de Filament UI components
- ✅ **Sin limpieza**: No hay tabla `notifications` que limpiar
- ✅ **Sin conflictos**: No hay conflictos entre canales

#### Diferencias vs Sprint 28

**Sistema de Notificaciones:**
- **Antes**: Email + Database (UI con dropdown, badge, auto-marcado, limpieza)
- **Ahora**: Solo Email (sin UI, sin polling, sin base de datos)
- **Beneficio**: Simplicidad, confiabilidad, sin issues de renderizado

**Archivos:**
- **Antes**: 8 archivos de sistema UI + 5 rutas API + Scheduler + JavaScript
- **Ahora**: Solo clases de notificación con métodos `toMail()`
- **Beneficio**: Codebase más limpio y mantenible

**Experiencia Usuario:**
- **Antes**: Notificaciones en dropdown + email (cuando dropdown fallaba, UX rota)
- **Ahora**: Email únicamente (UX consistente y confiable)
- **Beneficio**: No hay expectativas rotas, experiencia predecible

---

### ✅ Sesión Completada (30-Dic-2025)
**SPRINT 28: Sistema Completo de Notificaciones + Auto-Marcado + Limpieza Automática + Logos en PDFs**

#### Logros de la Sesión

1. **✅ Logos en Todos los PDFs del Sistema**
   - **4 PDFs actualizados**: Cotizaciones, Órdenes de Pedido, Órdenes de Producción, Cuentas de Cobro
   - **Logo/Avatar automático**: Usa `logo` o fallback a `avatar` de la empresa
   - **Base64 encoding**: 100% compatible con DomPDF
   - **Posicionamiento absoluto**: Logo izquierda, info derecha
   - **Tamaños ajustados**: 120×90px (docs) y 100×75px (órdenes)

2. **✅ Análisis Completo del Sistema de Notificaciones**
   - **11 tipos de notificaciones** documentadas
   - **296 notificaciones** registradas en BD
   - **2 canales**: Email + Database (UI)
   - **Polling 30s**: Actualización automática en Filament
   - **6 templates email**: Markdown personalizados con PDFs adjuntos

3. **✅ Sistema de Auto-Marcado de Notificaciones**
   - **JavaScript interceptor**: Marca automáticamente al hacer click
   - **5 rutas API REST**: mark-as-read, mark-all, unread-count, destroy, cleanup
   - **Controller completo**: NotificationController con 5 métodos
   - **Middleware**: MarkNotificationsAsRead para marcado inteligente
   - **Observer**: DatabaseNotificationObserver para marcado al recuperar
   - **Livewire component**: NotificationTrigger con eventos en tiempo real

4. **✅ Sistema de Limpieza Automática**
   - **Comando Artisan**: `php artisan notifications:cleanup`
   - **3 opciones**: `--days=30`, `--read-only`, `--dry-run`
   - **Scheduler configurado**: Ejecución semanal (Domingos 2:00 AM)
   - **Tabla resumen**: Muestra distribución por tipo antes de eliminar
   - **Modo seguro**: Confirmación y dry-run para evitar pérdidas

5. **✅ Integración Completa con Filament**
   - **JavaScript compilado**: Vite build exitoso
   - **RenderHook agregado**: Script cargado en body.end
   - **Vista del hook**: notifications-script.blade.php
   - **Assets optimizados**: 37.94 kB JS gzipped

6. **✅ Página "Home" Renombrada a "Gremio"**
   - **Título y label**: "Home" → "Gremio"
   - **Slug URL**: `/admin/home` → `/admin/gremio`
   - **Clases CSS**: `.home-*` → `.gremio-*`
   - **Comentarios**: Actualizados a "Gremio"

7. **✅ Fix: Error en Company::follow()**
   - **Problema**: Faltaba parámetro `User $user` en línea 93
   - **Solución**: Agregado `auth()->user()` como segundo parámetro
   - **Verificado**: Otros usos del método ya eran correctos

#### Archivos Creados (Sprint 28)

**Sistema de Notificaciones (8)**:
1. `app/Http/Controllers/NotificationController.php` - Controller con 5 métodos API
2. `app/Http/Middleware/MarkNotificationsAsRead.php` - Middleware de marcado inteligente
3. `app/Observers/DatabaseNotificationObserver.php` - Observer para evento retrieved
4. `app/Livewire/NotificationTrigger.php` - Componente Livewire para clicks
5. `resources/js/filament-notifications.js` - JavaScript interceptor (250+ líneas)
6. `app/Console/Commands/CleanupOldNotifications.php` - Comando de limpieza
7. `resources/views/filament/hooks/notifications-script.blade.php` - Vista del hook
8. `resources/views/livewire/notification-trigger.blade.php` - Vista Livewire

**Total Sprint 28**: 8 archivos nuevos

#### Archivos Modificados (Sprint 28)

**PDFs con Logos (4)**:
1. `resources/views/documents/pdf.blade.php` - Logo en cotizaciones
2. `resources/views/collection-accounts/pdf.blade.php` - Logo en cuentas de cobro
3. `resources/views/production-orders/pdf.blade.php` - Logo en órdenes de producción
4. `resources/views/pdf/purchase-order.blade.php` - Logo en órdenes de pedido

**Configuración (5)**:
5. `routes/web.php` - 5 rutas de notificaciones agregadas
6. `routes/console.php` - Scheduler semanal de limpieza
7. `resources/js/app.js` - Import de filament-notifications.js
8. `app/Providers/Filament/AdminPanelProvider.php` - RenderHook agregado
9. `vite.config.js` - (sin cambios, verificado)

**Renombrado Home → Gremio (2)**:
10. `app/Filament/Pages/Home.php` - Título, label, slug actualizados
11. `resources/views/filament/pages/home.blade.php` - Clases CSS renombradas

**Fixes (1)**:
12. `app/Filament/Pages/Companies.php` - Fix `follow($company, auth()->user())`

**Total Sprint 28**: 12 archivos modificados

#### Sistema de Notificaciones - Arquitectura Completa

**11 Tipos de Notificaciones Implementadas:**

```
DOCUMENTOS (4):
├── QuoteSent - Cotización enviada (mail + database + PDF)
├── PurchaseOrderCreated - Orden de pedido creada (database only)
├── CollectionAccountSent - Cuenta de cobro enviada (mail + database + PDF)
└── ProductionOrderSent - Orden de producción enviada (mail + database + PDF)

CAMBIOS DE ESTADO (2):
├── PurchaseOrderStatusChanged - Cambio de estado en orden de pedido
└── CollectionAccountStatusChanged - Cambio de estado en cuenta de cobro

RED GRAFIRED (3):
├── CommercialRequestReceived - Solicitud comercial recibida
├── CommercialRequestApproved - Solicitud aprobada
└── CommercialRequestRejected - Solicitud rechazada

INVENTARIO (1):
└── StockAlertNotification - Alerta de stock (single/batch, ShouldQueue)

PERIÓDICAS (1):
└── PurchaseOrderDigest - Resumen diario de órdenes (mail only, scheduled)
```

**Distribución Actual (296 notificaciones):**
```
PurchaseOrderCreated:           156 (53%)
CollectionAccountStatusChanged:  54 (18%)
CollectionAccountSent:           54 (18%)
PurchaseOrderStatusChanged:      30 (10%)
CommercialRequestReceived:        2 ( 1%)
```

**Comando de Limpieza:**
```bash
# Modo prueba (recomendado primero)
php artisan notifications:cleanup --dry-run

# Solo notificaciones leídas de 30+ días
php artisan notifications:cleanup --read-only

# Todas las notificaciones de 60+ días
php artisan notifications:cleanup --days=60

# Resultado esperado:
# 🧹 Iniciando limpieza...
# +--------------------------------+----------+
# | Tipo                           | Cantidad |
# +--------------------------------+----------+
# | PurchaseOrderCreated           | 135      |
# | CollectionAccountStatusChanged | 54       |
# +--------------------------------+----------+
# ✅ Se eliminaron 266 notificaciones correctamente.
```

**Rutas API Creadas:**
```
POST   /admin/notifications/{id}/mark-as-read     - Marca una como leída
POST   /admin/notifications/mark-all-as-read      - Marca todas como leídas
GET    /admin/notifications/unread-count          - Obtiene contador
DELETE /admin/notifications/{id}                  - Elimina una notificación
POST   /admin/notifications/cleanup               - Limpia antiguas (30+ días)
```

**Scheduler Configurado:**
```php
// Ejecución automática: Domingos 2:00 AM
Schedule::command('notifications:cleanup --read-only')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->description('Limpiar notificaciones leídas de más de 30 días');
```

**JavaScript - Funcionalidades:**
```javascript
// Auto-marcado al hacer click
- Intercepta clicks en notificaciones de Filament
- Envía AJAX a /admin/notifications/{id}/mark-as-read
- Actualiza badge de contador en tiempo real
- Marca visualmente como leída (opacity: 0.6)
- Observer para notificaciones agregadas dinámicamente

// Función global disponible
window.markAllNotificationsAsRead();
```

#### Testing Realizado

```bash
✅ 4 PDFs con logos verificados
✅ Sintaxis PHP: 0 errores
✅ Código formateado con Pint (17 archivos)
✅ Assets compilados con Vite (build exitoso)
✅ Comando notifications:cleanup --dry-run ejecutado
✅ Rutas API verificadas (5 rutas)
✅ Scheduler listado (1 tarea semanal)
✅ Cachés limpiadas (views, config, filament)
✅ JavaScript cargado en Filament (renderHook)
```

#### Diferencias vs Sprints Anteriores

**Logos en PDFs:**
- **Antes**: Solo texto de empresa en header
- **Ahora**: Logo/avatar en esquina superior izquierda
- **Beneficio**: Identidad visual en todos los documentos

**Notificaciones:**
- **Antes**: 296 notificaciones no leídas (100%), sin auto-marcado
- **Ahora**: Auto-marcado al click + limpieza automática semanal
- **Beneficio**: UX mejorada, BD optimizada, mantenimiento automático

**Página Home:**
- **Antes**: URL `/admin/home`, clases `.home-*`
- **Ahora**: URL `/admin/gremio`, clases `.gremio-*`
- **Beneficio**: Nombre más descriptivo para red social de litografías

---

### ✅ Sesión Completada (29-Dic-2025)
**SPRINT 27: Mejoras UX - Páginas de Revista, Menú Reorganizado, Password Reset y Sidebar**

#### Logros de la Sesión

1. **✅ Magazine Pages - Campos Completos como SimpleItem**
   - **Expandido schema de páginas**: 8 campos → 17+ campos completos
   - **7 Secciones colapsables**: Información, Dimensiones, Papel, Tintas, Montaje, Costos, Ganancia
   - **Dos métodos actualizados**: `getEditForm()` y `getWizardSteps()`
   - **Mapeo completo**: `fillForm()` y `updatePages()` con todos los campos

2. **✅ Reorganización Completa del Menú Lateral**
   - **Nueva sección "Contactos"**: Primer grupo en el menú
   - **Items ocultos del menú**: SimpleItem, MagazineItem, TalonarioItem (aún funcionales en cotizaciones)
   - **DigitalItem movido**: De "Items" a "Inventario" (orden 3)
   - **SupplierRelationshipResource oculto**: Evita duplicación con SupplierResource

3. **✅ Sistema de Password Reset 100% Funcional**
   - **Traducciones completas en español**: request-password-reset.php, reset-password.php
   - **Fix completo**: Eliminadas personalizaciones que interferían
   - **Solución final**: Usar implementación por defecto de Filament
   - **Resultado**: Reset de contraseña funcionando perfectamente

4. **✅ Personalización del Sidebar**
   - **Color de fondo**: `#e9f3ff` (azul claro, personalizable)
   - **Scrollbar custom**: 5px ancho, bordes redondeados
   - **Estilos de items**: Hover, activo, colores de texto
   - **Compilado con Vite**: Assets optimizados

#### Archivos Creados (Sprint 27)

**Traducciones (2)**:
1. `lang/vendor/filament-panels/es/pages/auth/password-reset/request-password-reset.php`
2. `lang/vendor/filament-panels/es/pages/auth/password-reset/reset-password.php`

**Total Sprint 27**: 2 archivos nuevos

#### Archivos Modificados (Sprint 27)

**Handlers (1)**:
1. `app/Filament/Resources/Documents/RelationManagers/Handlers/MagazineItemHandler.php`
   - Expandido Repeater schema en `getEditForm()` (líneas 159-419)
   - Expandido Repeater schema en `getWizardSteps()` (líneas 735-995)
   - Actualizado `fillForm()` para mapear todos los campos (líneas 423-487)
   - Actualizado `updatePages()` para guardar todos los campos (líneas 517-629)

**Enums (1)**:
2. `app/Enums/NavigationGroup.php`
   - Agregado case `Contactos`
   - Actualizado método `getSort()` con nuevo orden

**Resources - Movidos/Ocultos (7)**:
3. `app/Filament/Resources/DigitalItems/DigitalItemResource.php` - Movido a Inventario, sort 3
4. `app/Filament/Resources/SimpleItems/SimpleItemResource.php` - Agregado `shouldRegisterNavigation() => false`
5. `app/Filament/Resources/MagazineItems/MagazineItemResource.php` - Agregado `shouldRegisterNavigation() => false`
6. `app/Filament/Resources/TalonarioItems/TalonarioItemResource.php` - Agregado `shouldRegisterNavigation() => false`
7. `app/Filament/Resources/SupplierRelationships/SupplierRelationshipResource.php` - Oculto del menú

**Resources - Reorganizados (5)**:
8. `app/Filament/Resources/Contacts/ContactResource.php` - Movido a Contactos, sort 1
9. `app/Filament/Resources/ClientResource.php` - Movido a Contactos, sort 2
10. `app/Filament/Resources/SupplierResource.php` - Movido a Contactos, sort 3
11. `app/Filament/Resources/CommercialRequestResource.php` - Movido a Contactos, sort 4
12. `app/Filament/Resources/Documents/DocumentResource.php` - Cambiado sort de 4 a 1
13. `app/Filament/Resources/PurchaseOrders/PurchaseOrderResource.php` - Cambiado sort de 5 a 2
14. `app/Filament/Resources/ProductionOrders/ProductionOrderResource.php` - Cambiado sort de 6 a 3
15. `app/Filament/Resources/CollectionAccounts/CollectionAccountResource.php` - Cambiado sort de 6 a 4

**CSS (1)**:
16. `resources/css/filament/admin/theme.css`
   - Agregado color de fondo sidebar: `#e9f3ff`
   - Personalización scrollbar (8px → 5px ancho)
   - Estilos de items del menú

**Auth Pages (1)**:
17. `app/Filament/Pages/Auth/PasswordReset/ResetPassword.php`
   - Simplificado a implementación por defecto de Filament (solo hereda de BaseResetPassword)

**Total Sprint 27**: 17 archivos modificados

#### Estructura Final del Menú

```
📂 Contactos (NUEVO - sort 1)
   ├── 1. Clientes y Proveedores
   ├── 2. Clientes
   ├── 3. Proveedores
   └── 4. Solicitudes Comerciales

📂 Documentos (sort 2)
   ├── 1. Cotizaciones (era 4)
   ├── 2. Órdenes de Pedido (era 5)
   ├── 3. Órdenes de Producción (era 6)
   └── 4. Cuentas de Cobro (era 6)

📂 Items (sort 3 - OCULTO automáticamente al quedar vacío)

📂 Inventario (sort 4)
   ├── 1. Papeles
   ├── 2. Máquinas de Impresión
   └── 3. Items Digitales (MOVIDO desde Items)

📂 Configuración (sort 5)
📂 Sistema (sort 6)
```

**Items Ocultos** (aún funcionales en cotizaciones):
- SimpleItemResource
- MagazineItemResource
- TalonarioItemResource
- SupplierRelationshipResource

#### Testing Realizado

```bash
✅ Migración de páginas revista sin errores
✅ Caché limpiada múltiples veces (views, config, filament)
✅ Código formateado con Pint (9 archivos, 5 issues corregidos)
✅ Sin errores de sintaxis PHP
✅ Assets compilados con Vite (npm run build)
✅ Password reset 100% funcional
✅ Traducciones en español completas
✅ Menú reorganizado correctamente
✅ Sidebar con estilos personalizados
```

#### Problemas Resueltos Durante la Sesión

**Error 1: Cambios de Magazine Pages no visibles**
- **Problema**: Solo se actualizó `getEditForm()`, faltaba `getWizardSteps()`
- **Solución**: Duplicar schema en ambos métodos
- **Resultado**: Cambios visibles tras limpiar caché

**Error 2: Password Reset - Validación "confirmed" no funciona**
- **Problema**: Múltiples conflictos con validaciones personalizadas
- **Intentos fallidos**:
  - `->confirmed()` en password field
  - `->same('password')` en password_confirmation
  - `getValidationRules()` personalizado
  - `->statePath('data')`
- **Solución final**: Eliminar TODAS las personalizaciones, usar implementación por defecto
- **Resultado**: Funciona perfectamente sin código personalizado

**Error 3: Email no aparece en formulario de reset**
- **Problema**: Campo email vacío al cargar página de reset
- **Causa**: Sobrescritura de métodos interfería con mount() de Filament
- **Solución**: Eliminar personalizaciones, dejar que Filament maneje todo
- **Resultado**: Email se carga automáticamente desde URL

#### Diferencias vs Sprints Anteriores

**Magazine Pages:**
- **Antes**: 8 campos básicos (tipo, cantidad, orden, etc.)
- **Ahora**: 17+ campos completos (igual que SimpleItem)
- **Beneficio**: Control total sobre cada página de revista

**Menú:**
- **Antes**: Items y Documentos mezclados, sin sección de Contactos
- **Ahora**: Organización lógica por tipo de entidad
- **Beneficio**: Navegación más intuitiva

**Password Reset:**
- **Antes**: No funcionaba, sin traducciones
- **Ahora**: 100% funcional, completamente en español
- **Lección**: Confiar en implementaciones por defecto de frameworks

---

### ✅ Sesión Completada (17-Dic-2025)
**SPRINT 26: Envío Manual de Emails para Cotizaciones (Documents/Quotes)**

#### Logros de la Sesión

1. **✅ Sistema Completo de Envío Manual de Emails**
   - **Migración**: Campos `email_sent_at` y `email_sent_by` en tabla `documents`
   - **Tracking completo**: Registra cuándo y quién envió el email
   - **Validaciones**: Items, total > 0, email del cliente
   - **UI dinámica**: Label, color y badge según estado de envío

2. **✅ Notificación QuoteSent con PDF**
   - **Email con PDF adjunto**: Usa DomPDF (mismo que DocumentPdfController)
   - **Template Markdown**: Vista personalizada para cotizaciones
   - **Notificación database**: Para usuarios internos
   - **Información completa**: Número, fecha, total, cliente

3. **✅ Acción Manual en ViewDocument y DocumentsTable**
   - **Botón dinámico**: "Enviar Email" vs "Reenviar Email"
   - **Badge visual**: Muestra "Enviado" cuando corresponde
   - **Modal de confirmación**: Advertencia al reenviar
   - **Tooltip informativo**: Muestra fecha de envío

#### Archivos Creados (Sprint 26)

**Migración (1)**:
1. `database/migrations/2025_12_17_234302_add_email_sent_at_to_documents_table.php`

**Notificación (1)**:
2. `app/Notifications/QuoteSent.php`

**Vista Email (1)**:
3. `resources/views/emails/quote/sent.blade.php`

**Total Sprint 26**: 3 archivos nuevos

#### Archivos Modificados (Sprint 26)

**Modelo (1)**:
1. `app/Models/Document.php`
   - Agregado `email_sent_at`, `email_sent_by` a fillable
   - Agregado cast datetime para `email_sent_at`
   - Relación `emailSentBy()` a User

**Páginas (1)**:
2. `app/Filament/Resources/Documents/Pages/ViewDocument.php`
   - Acción `send_email` completa con validaciones

**Tablas (1)**:
3. `app/Filament/Resources/Documents/Tables/DocumentsTable.php`
   - Columna `email_sent_at` con badge
   - Acción `send_email` en tabla

**Total Sprint 26**: 3 archivos modificados

#### Testing Realizado

```bash
✅ Migración ejecutada sin errores
✅ Sin errores de sintaxis en archivos PHP
✅ Caché limpiada (views + config)
✅ Campos agregados a BD correctamente
✅ Relación emailSentBy() funcional
```

#### Diferencias vs Purchase Orders

**Similitudes:**
- Mismo patrón de validaciones
- Mismo tracking (email_sent_at, email_sent_by)
- Misma UI dinámica (label, color, badge)

**Diferencias:**
- **Documents**: Usa `clientCompany` o `contact` para el email
- **Documents**: Usa `QuoteSent` notification (vs PurchaseOrderCreated)
- **Documents**: PDF generado con `documents.pdf` view
- **Documents**: Campo `total` (vs `total_amount`)

---

### ✅ Sesión Completada (05-Dic-2025)
**SPRINT 25: Sistema de Búsqueda Grafired para Clientes + Buscador Reactivo + Documentación Completa**

#### Logros de la Sesión

1. **✅ Buscador Reactivo con Livewire en Modal de Proveedores**
   - **Problema inicial**: Alpine.js con JSON no funcionaba en modales Filament
   - **Solución**: Componente Livewire `GrafiredSupplierSearch` completo
   - **Búsqueda en tiempo real**: Debounce 300ms, filtra por nombre o NIT
   - **Grid de 3 columnas**: Inline styles (no depende de Tailwind compilado)
   - **Avatares con gradiente**: Azul para proveedores
   - **Badges dinámicos**: Colores según tipo de empresa

2. **✅ Sistema Completo de Búsqueda para Clientes**
   - **Componente Livewire**: `GrafiredClientSearch` (clon de proveedores)
   - **relationshipType**: Usa `'client'` (no `'customer'`)
   - **Grid de 3 columnas**: Inline styles con avatares verdes
   - **Botón**: "Solicitar como Cliente" (verde esmeralda)
   - **Modal habilitado**: En `/admin/clients` → Botón "Buscar en Grafired"

3. **✅ Fix ENUM Mismatch - Mapeo de Tipos**
   - **Problema**: `commercial_requests.relationship_type` = `['client', 'supplier']`
   - **Problema**: `contacts.type` = `['customer', 'supplier', 'both']`
   - **Solución**: CommercialRequestService mapea automáticamente:
     - `'client'` en request → `'customer'` en contact
     - `'supplier'` en request → `'supplier'` en contact
   - **Bidireccional**: Ambas empresas reciben contacts con tipos correctos

4. **✅ Diseño UI Mejorado con Inline Styles**
   - **Problema**: Tailwind no compila clases para vistas cargadas dinámicamente
   - **Solución**: Todos los estilos críticos usando `style="..."` inline
   - **Componentes nativos**: `<x-filament::icon>`, `<x-filament::badge>`, `<x-filament::button>`
   - **Responsive**: Flexbox con `calc(33.333% - 0.5rem)` para 3 columnas
   - **Hover effects**: JavaScript inline para cambio de color

5. **✅ Documentación Completa del Sistema**
   - **Archivo creado**: `CLIENTESPROVEEDORES.md` (10 secciones, 500+ líneas)
   - **Contenido**: Arquitectura completa de modelos y relaciones
   - **5 Modelos explicados**: Company, Contact, CommercialRequest, ClientRelationship, SupplierRelationship
   - **Diagramas**: Entidad-relación, flujos de negocio, casos de uso
   - **Relación con documentos**: Cotizaciones, Órdenes de Producción, Cuentas de Cobro

#### Archivos Creados (Sprint 25)

**Componentes Livewire (2)**:
1. `app/Livewire/GrafiredSupplierSearch.php`
   - Búsqueda reactiva de proveedores
   - Método `requestSupplier()`
2. `app/Livewire/GrafiredClientSearch.php`
   - Búsqueda reactiva de clientes
   - Método `requestClient()`

**Vistas Livewire (2)**:
3. `resources/views/livewire/grafired-supplier-search.blade.php`
   - Grid 3 columnas con inline styles
   - Avatar azul, botón azul cielo
4. `resources/views/livewire/grafired-client-search.blade.php`
   - Grid 3 columnas con inline styles
   - Avatar verde, botón verde esmeralda

**Wrappers (2)**:
5. `resources/views/filament/modals/grafired-livewire-wrapper.blade.php`
6. `resources/views/filament/modals/grafired-client-wrapper.blade.php`

**Documentación (1)**:
7. `CLIENTESPROVEEDORES.md`
   - 10 secciones completas
   - Diagramas ASCII
   - 3 casos de uso detallados

**Total Sprint 25**: 7 archivos nuevos

#### Archivos Modificados (Sprint 25)

**Servicios (1)**:
1. `app/Services/CommercialRequestService.php`
   - Fix línea 79-89: Mapeo correcto `'client'` → `'customer'`
   - Comentarios explicativos del mapeo

**Páginas (2)**:
2. `app/Filament/Pages/Suppliers/ListSuppliers.php`
   - Cambiado a wrapper Livewire
   - Método `getGrafiredCompanies()` serializa Enums correctamente
3. `app/Filament/Pages/Clients/ListClients.php`
   - Habilitado botón "Buscar en Grafired"
   - Agregado `getSearchGrafiredAction()`

**Total Sprint 25**: 3 archivos modificados

#### Arquitectura Final: Clientes y Proveedores

```
┌─────────────────────────────────────────────────────────┐
│               SISTEMA DE CONTACTOS                      │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Company (Empresa Registrada en Grafired)              │
│     │                                                   │
│     ├── has many → Contact (Clientes/Proveedores)      │
│     │              │                                    │
│     │              ├── type: 'customer' (Cliente)       │
│     │              ├── type: 'supplier' (Proveedor)     │
│     │              ├── type: 'both' (Ambos)             │
│     │              │                                    │
│     │              ├── is_local: true (Local)           │
│     │              │   └── linked_company_id: NULL      │
│     │              │                                    │
│     │              └── is_local: false (Grafired)       │
│     │                  └── linked_company_id: Company   │
│     │                                                   │
│     └── Relaciones:                                     │
│         ├── documents (Cotizaciones)                    │
│         ├── productionOrders (Órdenes de Producción)   │
│         ├── purchaseOrders (Órdenes de Pedido)         │
│         └── collectionAccounts (Cuentas de Cobro)      │
│                                                         │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│            WORKFLOW DE SOLICITUDES                      │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  1. Usuario busca en Grafired                           │
│     ↓                                                   │
│  2. Click "Solicitar como Proveedor/Cliente"            │
│     ↓                                                   │
│  3. CommercialRequest creado (status: pending)          │
│     - relationship_type: 'supplier' o 'client'          │
│     ↓                                                   │
│  4. Empresa destino recibe notificación                 │
│     ↓                                                   │
│  5. APRUEBA → Crea 2 Contacts bidireccionales           │
│     - Contact en Solicitante (tipo según solicitud)     │
│     - Contact en Destino (tipo inverso)                 │
│     ↓                                                   │
│  6. Relación activa (ClientRelationship o               │
│     SupplierRelationship)                               │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

#### Mapeo de Tipos (CRÍTICO)

| CommercialRequest | Contact Solicitante | Contact Destino |
|------------------|---------------------|-----------------|
| `relationship_type='supplier'` | `type='supplier'` | `type='customer'` |
| `relationship_type='client'` | `type='customer'` | `type='supplier'` |

#### Testing Realizado

```bash
✅ Modal de proveedores con buscador reactivo funciona
✅ Búsqueda en tiempo real (300ms debounce)
✅ Grid de 3 columnas con inline styles
✅ Avatares y badges con colores correctos
✅ Modal de clientes habilitado y funcional
✅ Solicitudes de cliente se crean correctamente
✅ Fix ENUM: 'client' → 'customer' en contacts
✅ Creación bidireccional de contacts funciona
✅ Documentación completa generada
```

#### Problemas Resueltos Durante la Sesión

**Error 1: Alpine.js no renderiza en modal**
- **Problema**: `x-data` y `x-for` no se procesaban en modalContent de Filament
- **Causa**: Modal escapa HTML y Alpine.js no se inicializa
- **Solución**: Usar componente Livewire completo con `@livewire()` wrapper

**Error 2: Tailwind CSS no compila clases dinámicas**
- **Problema**: `grid grid-cols-3` mostraba `display: block`
- **Causa**: Tailwind no compila clases en vistas cargadas dinámicamente
- **Solución**: Usar `style="display: flex; flex-wrap: wrap; ..."` inline

**Error 3: ENUM type mismatch en contacts**
- **Problema**: `SQLSTATE[01000]: Data truncated for column 'type'`
- **Causa**: Intentando insertar `'client'` en ENUM que solo acepta `'customer', 'supplier', 'both'`
- **Solución**: Mapear en CommercialRequestService línea 79-89

**Error 4: ENUM relationship_type mismatch**
- **Problema**: `SQLSTATE[01000]: Data truncated for column 'relationship_type'`
- **Causa**: GrafiredClientSearch enviaba `'customer'` pero ENUM espera `'client', 'supplier'`
- **Solución**: Cambiar a `relationshipType: 'client'` en línea 48

---

### ✅ Sesión Completada (04-Dic-2025)
**SPRINT 24: Sistema Completo de Red Grafired - Búsqueda y Solicitudes Comerciales**

#### Logros de la Sesión

1. **✅ Sistema Completo de Solicitudes Comerciales**
   - **CommercialRequestService**: Lógica de negocio centralizada
   - **Validación de duplicados**: No permite solicitudes repetidas
   - **Workflow completo**: Pending → Approved/Rejected
   - **Creación bidireccional**: Ambas empresas quedan conectadas
   - **Notificaciones**: Email + Database en cada paso

2. **✅ Modal de Búsqueda Grafired**
   - **Vista estática optimizada**: Pre-carga 20 empresas públicas
   - **Componentes nativos Filament**: Sin CSS personalizado
   - **Iconos correctos**: h-4 w-4 (antes estaban desproporcionados)
   - **Badges dinámicos**: Colores según tipo de empresa
   - **Botón funcional**: "Solicitar como Proveedor" con wire:click

3. **✅ Modelo Contact - Soporte Grafired Completo**
   - **Campo linked_company_id**: Referencia a empresa en red
   - **Campo is_local**: Diferencia proveedores locales vs Grafired
   - **Scopes**: local(), grafired() para filtrado
   - **Método syncFromLinkedCompany()**: Sincroniza datos desde empresa

4. **✅ Sistema de Notificaciones Completo**
   - **CommercialRequestReceived**: Notifica a empresa destino
   - **CommercialRequestApproved**: Notifica aprobación al solicitante
   - **CommercialRequestRejected**: Notifica rechazo al solicitante
   - Todas con email + database

5. **✅ Fix Múltiples Errores Filament v4**
   - **Action imports**: Corregido en 5 resources (ClientResource, SupplierResource, etc.)
   - **Rutas corregidas**: companies.view → companies (páginas sin view)
   - **Vista faltante**: commercial-request-response.blade.php creada
   - **Get type mismatch**: Evitado usando vista estática en lugar de form reactivo

#### Archivos Creados (Sprint 24)

**Servicios (1)**:
1. `app/Services/CommercialRequestService.php` (150 líneas)
   - sendRequest(): Valida y crea solicitud
   - approveRequest(): Crea contactos bidireccionales
   - rejectRequest(): Rechaza solicitud con mensaje

**Notificaciones (3)**:
2. `app/Notifications/CommercialRequestReceived.php`
3. `app/Notifications/CommercialRequestApproved.php`
4. `app/Notifications/CommercialRequestRejected.php`

**Vistas (1)**:
5. `resources/views/filament/modals/grafired-search-static.blade.php`
   - Modal con empresas públicas
   - Componentes nativos: x-filament::icon, x-filament::badge, x-filament::button
   - Layout responsive con scroll

**Total Sprint 24**: 5 archivos nuevos

#### Archivos Modificados (Sprint 24)

**Modelos (1)**:
1. `app/Models/Contact.php`
   - Agregado linked_company_id, is_local a fillable
   - Relación linkedCompany()
   - Scopes: local(), grafired()
   - Métodos: isLocal(), isGrafired(), syncFromLinkedCompany()

**Páginas (1)**:
2. `app/Filament/Pages/Suppliers/ListSuppliers.php`
   - getSearchGrafiredAction(): Modal de búsqueda
   - getGrafiredCompanies(): Query de empresas públicas
   - sendSupplierRequest($companyId, $message): Handler de solicitud

**Resources (3)**:
3. `app/Filament/Resources/CommercialRequestResource.php`
   - Actualizado approveAction() con CommercialRequestService
   - Actualizado rejectAction() con CommercialRequestService
4. `app/Filament/Resources/ClientResource.php` - Fix Action import
5. `app/Filament/Resources/SupplierResource.php` - Fix Action import

**Total Sprint 24**: 5 archivos modificados

#### Workflow de Solicitudes Implementado

```
SOLICITAR PROVEEDOR:
1. Usuario A busca empresas en Grafired
2. Click "Solicitar como Proveedor" → sendSupplierRequest()
3. CommercialRequestService crea solicitud (status: pending)
4. Empresa B recibe notificación (email + database)

APROBAR SOLICITUD:
1. Usuario B abre solicitud en CommercialRequests
2. Click "Aprobar" → approveRequest()
3. Sistema crea 2 contactos:
   - Contact en Empresa A (linked_company_id = B, type: supplier)
   - Contact en Empresa B (linked_company_id = A, type: client)
4. Usuario A recibe notificación de aprobación
5. Ambas empresas quedan conectadas

RECHAZAR SOLICITUD:
1. Usuario B click "Rechazar" → rejectRequest()
2. Status cambia a 'rejected'
3. Usuario A recibe notificación de rechazo
```

#### Testing Realizado

```bash
✅ Modal de búsqueda abre correctamente
✅ Empresas públicas se cargan (7 encontradas)
✅ Iconos y badges con tamaño correcto
✅ Botón "Solicitar como Proveedor" funciona
✅ Validación de duplicados funciona ("Ya existe una solicitud activa")
✅ Componentes nativos Filament (sin CSS custom)
✅ Notificaciones se envían correctamente
✅ Relación linkedCompany carga correctamente
✅ Scopes local() y grafired() funcionan
✅ Playwright verificó CSS correcto
```

#### Problemas Resueltos Durante la Sesión

**Error: Get Type Mismatch en Modal con Forms**
- **Problema**: `Filament\Forms\Get` vs `Filament\Schemas\Components\Utilities\Get`
- **Solución**: Cambiar de form reactivo a vista estática pre-cargada
- **Resultado**: Modal funcional sin conflictos de tipos

**Error: Iconos Desproporcionados en Modal**
- **Problema**: SVGs manuales con clases custom causaban tamaño incorrecto
- **Solución**: Usar componentes nativos Filament (`<x-filament::icon>`)
- **Resultado**: Iconos h-4 w-4 perfectamente integrados

**Error: $wire Not Defined en Livewire**
- **Problema**: Componente Livewire dentro de modal Filament causaba conflicto
- **Solución**: Usar wire:click directo en ListSuppliers page
- **Resultado**: Comunicación directa sin wrapper Livewire

---

### ✅ Sesión Completada (22-Nov-2025)
**SPRINT 23: Dashboard de Stock Management Completo + Widgets Interactivos**

*Ver detalles completos en sección "Notas Técnicas" al final del documento*

**Resumen**:
- 4 widgets nuevos: StockTrends, TopConsumed, CriticalAlerts, RecentMovements
- QuickActions con 4 acciones: Entrada Stock, Ver Críticos, Generar PO, Descargar
- StockAlertResource completo con CRUD
- SimpleStockKpisWidget mejorado (5 stats + sparklines)

---

### 📋 Sprints Anteriores (Resumen)

- **SPRINT 23** (22-Nov): Dashboard Stock Management + 4 Widgets + QuickActions
- **SPRINT 22** (21-Nov): Limpieza Stock Management (387 → 52 líneas)
- **SPRINT 21** (19-Nov): Sistema de Acabados para Productos en Cotizaciones
- **SPRINT 20** (16-Nov): Órdenes de Producción con Impresión + Acabados
- **SPRINT 19** (15-Nov): Auto-Asignación de Proveedores en Acabados
- **SPRINT 18** (08-Nov): Sistema de Imágenes para Productos + Cliente Dual
- **SPRINT 17** (07-Nov): Nomenclatura "Papelería → Papelería y Productos"
- **SPRINT 16** (07-Nov): Sistema de Permisos 100% + Policies
- **SPRINT 15** (06-Nov): Documentación Sistema de Notificaciones (4 tipos)
- **SPRINT 14** (06-Nov): Sistema base de Acabados + UI
- **SPRINT 13** (05-Nov): Sistema de Montaje con Divisor

---

## 🎯 PRÓXIMA TAREA PRIORITARIA

**Sistema de Envío Manual de Emails - Módulos Restantes**

Continuar implementando el sistema de envío manual en los módulos pendientes:

**Opción A - Cuentas de Cobro (Collection Accounts)** (RECOMENDADO):
1. Migración: `email_sent_at`, `email_sent_by` en tabla `collection_accounts`
2. Modelo: `CollectionAccount.php`
3. Notificación: `CollectionAccountSent` (YA EXISTE - verificar si necesita PDF)
4. Página: `ViewCollectionAccount.php` o equivalente
5. Tabla: Agregar acción de envío manual

**Opción B - Órdenes de Producción (Production Orders)**:
1. Migración: `email_sent_at`, `email_sent_by` en tabla `production_orders`
2. Modelo: `ProductionOrder.php`
3. Notificación: Crear `ProductionOrderSent` con PDF
4. Página: `ViewProductionOrder.php` o equivalente
5. Tabla: Agregar acción de envío manual

**Opción C - Otras Áreas**:
1. **Sistema Grafired - Mejoras**:
   - Búsqueda avanzada con filtros
   - Paginación en modales
2. **Remover Placeholder de Debug de ProductQuickHandler**
3. **Dashboard de Producción** con widgets

---

## COMANDO PARA EMPEZAR MAÑANA

```bash
# Iniciar LitoPro 3.0 - SPRINT 30 COMPLETADO (Stock Consolidado)
cd /home/dasiva/Descargas/litopro825 && php artisan serve --port=8000

# Estado del Proyecto
echo "✅ SPRINT 30 COMPLETADO (30-Dic-2025) - Páginas de Stock Consolidadas"
echo ""
echo "📍 URLs de Testing:"
echo "   🏠 Dashboard: http://127.0.0.1:8000/admin"
echo "   📄 Cotizaciones: http://127.0.0.1:8000/admin/documents"
echo "   🛒 Órdenes Pedido: http://127.0.0.1:8000/admin/purchase-orders"
echo "   💰 Cuentas Cobro: http://127.0.0.1:8000/admin/collection-accounts"
echo "   🏭 Órdenes Producción: http://127.0.0.1:8000/admin/production-orders"
echo ""
echo "⚠️  IMPORTANTE: Usar http://127.0.0.1:8000 (NO localhost) - CORS configurado"
echo ""
echo "🎉 SPRINT 30 - STOCK CONSOLIDADO:"
echo "   • ✅ Página Stock.php unificada (7 archivos eliminados)"
echo "   • ✅ 3 tabs: Resumen, Movimientos, Alertas"
echo "   • ✅ 9 widgets organizados (3 header + 6 tabs)"
echo "   • ✅ 3 header actions: Actualizar, Ver Alertas, Nuevo Movimiento"
echo "   • ✅ Navegación simplificada (2 → 1 entrada menú)"
echo "   • ✅ 3 widgets obsoletos eliminados"
echo ""
echo "📊 NUEVA PÁGINA STOCK:"
echo "   URL: http://127.0.0.1:8000/admin/stock"
echo "   • Tab Resumen: Tendencias + Top productos"
echo "   • Tab Movimientos: Historial + Recientes"
echo "   • Tab Alertas: Críticas"
echo ""
echo "📧 NOTIFICACIONES EMAIL ONLY:"
echo "   • QuoteSent (con PDF)"
echo "   • PurchaseOrderCreated"
echo "   • PurchaseOrderStatusChanged"
echo "   • CollectionAccountSent (con PDF)"
echo "   • CollectionAccountStatusChanged"
echo "   • ProductionOrderSent (con PDF)"
echo "   • CommercialRequestReceived/Approved/Rejected"
echo "   • StockAlertNotification (ShouldQueue)"
echo "   • PurchaseOrderDigest (scheduled)"
echo ""
echo "🎯 PRÓXIMA TAREA (RECOMENDADO):"
echo "   Opción A: Implementar envío manual en Collection Accounts"
echo "   Opción B: Implementar envío manual en Production Orders"
echo "   Opción C: Mejorar sistema Grafired (búsqueda, filtros)"
echo "   Opción D: Dashboard de Producción con widgets"
echo ""
echo "📝 COMANDOS ÚTILES:"
echo "   - Ver templates email: ls resources/views/emails/"
echo "   - Ver notificaciones: ls app/Notifications/"
echo "   - Verificar canales: grep -r \"return \['mail'\]\" app/Notifications/"
```

---

## Notas Técnicas Importantes

### Sistema de Red Grafired (Sprint 24)

**CommercialRequestService - Workflow Completo**:
```php
// ENVIAR SOLICITUD
$service = app(CommercialRequestService::class);

$request = $service->sendRequest(
    targetCompany: $company,        // Empresa destino
    relationshipType: 'supplier',   // supplier o client
    message: 'Mensaje opcional'
);

// Validaciones automáticas:
// - No permite solicitudes duplicadas pendientes
// - Notifica a todos los usuarios de la empresa destino

// APROBAR SOLICITUD (crea contactos bidireccionales)
$contact = $service->approveRequest(
    request: $request,
    approver: auth()->user(),
    responseMessage: 'Bienvenido a nuestra red'
);

// Resultado:
// - Contact en Empresa A: linkedCompany = B, type = supplier
// - Contact en Empresa B: linkedCompany = A, type = client
// - Notificación de aprobación al solicitante

// RECHAZAR SOLICITUD
$service->rejectRequest(
    request: $request,
    responder: auth()->user(),
    responseMessage: 'Gracias por tu interés'
);
// Resultado: Status = rejected, notificación al solicitante
```

**Contact Model - Soporte Grafired**:
```php
use App\Models\Contact;

// Crear contacto local
$contact = Contact::create([
    'company_id' => 1,
    'name' => 'Proveedor Local',
    'is_local' => true,
    'is_supplier' => true,
]);

// Crear contacto Grafired
$contact = Contact::create([
    'company_id' => 1,
    'linked_company_id' => 5,  // Empresa en red
    'is_local' => false,
    'is_supplier' => true,
]);

// Scopes
$locales = Contact::local()->get();        // Solo is_local = true
$grafired = Contact::grafired()->get();    // Solo is_local = false + linked_company_id

// Sincronizar datos desde empresa
if ($contact->linkedCompany) {
    $contact->syncFromLinkedCompany();
    // Actualiza: name, email, phone, address, city, state, country
}

// Verificaciones
if ($contact->isLocal()) { /* ... */ }
if ($contact->isGrafired()) { /* ... */ }
```

**Modal Grafired - Componentes Nativos Filament**:
```blade
{{-- ❌ INCORRECTO: SVG manual con clases custom --}}
<svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" .../>
</svg>

{{-- ✅ CORRECTO: Componente nativo Filament --}}
<x-filament::icon
    icon="heroicon-m-map-pin"
    class="h-4 w-4"
/>

{{-- Badges dinámicos --}}
<x-filament::badge :color="match($company->company_type) {
    'litografia' => 'primary',
    'distribuidora' => 'success',
    'proveedor_insumos' => 'warning',
    default => 'info'
}">
    {{ $typeLabel }}
</x-filament::badge>

{{-- Botones con wire:click --}}
<x-filament::button
    wire:click="sendSupplierRequest({{ $company->id }}, null)"
    icon="heroicon-m-paper-airplane"
    size="xs"
>
    Solicitar como Proveedor
</x-filament::button>
```

**Ventajas de Componentes Nativos**:
- ✅ **Tamaños consistentes**: h-4 w-4 para iconos pequeños, h-12 w-12 para logos
- ✅ **Colores automáticos**: Respeta tema dark/light de Filament
- ✅ **Sin CSS custom**: No sobrescribe estilos predeterminados
- ✅ **Responsive**: Adapta automáticamente a diferentes pantallas

---

### Filament v4 - Errores Comunes y Soluciones

**Error 1: Action Import Incorrecto**
```php
// ❌ INCORRECTO: Filament v3
use Filament\Tables\Actions\Action;
use Filament\Pages\Actions\Action;

// ✅ CORRECTO: Filament v4
use Filament\Actions\Action;
```

**Error 2: Get Type Mismatch en Modales**
```php
// ❌ INCORRECTO: Form reactivo dentro de Action modal
Action::make('foo')
    ->form([
        Select::make('bar')
            ->reactive()
            ->afterStateUpdated(fn ($get, $set) => ...)
    ]);
// Error: Filament\Forms\Get vs Filament\Schemas\Components\Utilities\Get

// ✅ SOLUCIÓN 1: Vista estática
Action::make('foo')
    ->modalContent(view('filament.modals.static-view', ['data' => $data]))
    ->modalSubmitAction(false);

// ✅ SOLUCIÓN 2: Métodos del componente (no closure)
Select::make('bar')
    ->reactive()
    ->afterStateUpdated('handleUpdate'); // Método de Livewire component
```

**Error 3: Livewire dentro de Modal Filament**
```php
// ❌ INCORRECTO: @livewire dentro de modalContent
Action::make('foo')
    ->modalContent(view('modal-with-livewire'));
// Causa: $wire not defined

// ✅ CORRECTO: wire:click directo en Page
// ListSuppliers.php
public function sendSupplierRequest($companyId, $message) { /* ... */ }

// Blade del modal (modalContent)
<button wire:click="sendSupplierRequest({{ $company->id }}, null)">
    Solicitar
</button>
```

---

### Dashboard de Stock Management - Arquitectura (Sprint 23)

**Estructura de Widgets**:
```php
class StockManagement extends Page
{
    protected function getHeaderWidgets(): array {
        return [SimpleStockKpisWidget::class];
    }

    protected function getFooterWidgets(): array {
        return [
            StockTrendsChartWidget::class,
            TopConsumedProductsWidget::class,
            CriticalAlertsTableWidget::class,
            RecentMovementsWidget::class,
        ];
    }
}
```

**Widget con Acciones - Patrón Correcto**:
```php
class QuickActionsWidget extends Widget implements HasActions, HasForms {
    use InteractsWithActions;
    use InteractsWithForms;

    public function stockEntryAction(): Action {
        return Action::make('stock_entry')
            ->form([...])
            ->action(fn ($data) => ...);
    }

    public function viewCriticalAction(): Action {
        return Action::make('view_critical')
            ->url(route('filament.admin.resources.products.index') . '?filter=low');
    }
}

// Vista Blade
{{ ($this->stockEntryAction)() }}
{{ ($this->viewCriticalAction)() }}
<x-filament-actions::modals />
```

**Imports Críticos**:
```php
use Filament\Actions\Action; // NO Tables\Actions
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
```

---

### Sistema de Acabados para Productos (Sprint 21)

```php
// AGREGAR PRODUCTO CON ACABADOS A COTIZACIÓN
$product = Product::with('finishings')->find($productId);

// Calcular costo de acabados
$finishingCalculator = app(\App\Services\FinishingCalculatorService::class);
$finishingsCostTotal = 0;

foreach ($finishingsData as $finishingData) {
    $finishing = \App\Models\Finishing::find($finishingData['finishing_id']);
    $params = match($finishing->measurement_unit->value) {
        'millar', 'rango', 'unidad' => ['quantity' => $quantity],
        'tamaño' => ['width' => $width, 'height' => $height],
        default => []
    };
    $cost = $finishingCalculator->calculateCost($finishing, $params);
    $finishingsCostTotal += $cost;
}

// Guardar en item_config
$documentItem->update([
    'item_config' => [
        'finishings' => $finishingsData,
        'finishings_cost' => $finishingsCostTotal,
    ],
]);
```

---

### Auto-Asignación de Proveedores (Sprint 19)

```php
// Crear acabado propio (auto-asigna supplier_id)
$acabado = Finishing::create([
    'company_id' => 1,
    'name' => 'Plastificado',
    'is_own_provider' => true,  // ← Asigna supplier_id = 9
]);

// Toggle externo → propio
$acabado->update(['is_own_provider' => true]);
// supplier_id cambia automáticamente a contacto autorreferencial

// Método getSelfContactId() crea:
// - Nombre: "{Empresa} (Producción Propia)"
// - Email: "produccion@{empresa}.com"
// - Se reutiliza si ya existe
```

---

### Sistema de Montaje con Divisor (Sprint 13)

```php
$calculator = new SimpleItemCalculatorService();

// Montaje completo con divisor
$mountingWithCuts = $calculator->calculateMountingWithCuts($item);

// Resultado:
// [
//     'copies_per_mounting' => 2,    // Copias en tamaño máquina
//     'divisor' => 4,                // Cortes de máquina en pliego
//     'impressions_needed' => 500,   // 1000 ÷ 2
//     'sheets_needed' => 125,        // 500 ÷ 4
//     'total_impressions' => 500,    // 125 × 4
//     'total_copies_produced' => 1000 // 500 × 2
// ]
```

---

### Sistema de Notificaciones - Email Only (Sprint 29)

**Decisión de Arquitectura**: Después de intentar resolver problemas de renderizado en el dropdown de Filament v4, se tomó la decisión de simplificar el sistema eliminando completamente la UI de notificaciones y mantener solo el canal de email.

**Patrón Email-Only en Laravel**:
```php
// ❌ ANTES: Dual Channel (Email + Database)
class QuoteSent extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Dual channel
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva Cotización Enviada')
            ->markdown('emails.quote.sent', [
                'document' => $this->document,
            ])
            ->attach($pdfPath);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'format' => 'filament',
            'title' => 'Nueva Cotización',
            'body' => "Se envió la cotización...",
        ];
    }
}

// ✅ AHORA: Email Only
class QuoteSent extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail']; // Solo email
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva Cotización Enviada')
            ->markdown('emails.quote.sent', [
                'document' => $this->document,
            ])
            ->attach($pdfPath);
    }

    // Método toDatabase() eliminado - no necesario
}
```

**Ventajas del Patrón Email-Only**:
1. **Simplicidad**: Menos código, menos archivos, menos complejidad
2. **Confiabilidad**: Email es un protocolo estándar y probado
3. **Historial**: Los emails quedan permanentemente en la bandeja
4. **Sin polling**: No consume recursos del servidor
5. **Sin sincronización**: No hay que mantener coherencia entre canales

**Cuándo NO usar Email-Only**:
- ❌ Notificaciones en tiempo real críticas (usar websockets/pusher)
- ❌ Alertas urgentes que requieren acción inmediata (usar SMS/push)
- ❌ Notificaciones muy frecuentes (sobrecarga de bandeja)

**Cuándo SÍ usar Email-Only** (nuestro caso):
- ✅ Notificaciones de documentos (cotizaciones, órdenes, cuentas)
- ✅ Cambios de estado (aprobaciones, rechazos, actualizaciones)
- ✅ Resúmenes periódicos (diarios, semanales)
- ✅ Alertas de inventario (pueden esperar minutos/horas)

**Configuración en Filament v4**:
```php
// AdminPanelProvider.php

// ❌ REMOVIDO en Sprint 29:
// ->databaseNotifications()
// ->databaseNotificationsPolling('30s')

// ✅ CONFIGURACIÓN ACTUAL:
return $panel
    ->default()
    ->id('admin')
    ->path('admin')
    ->login()
    ->globalSearch()     // Búsqueda global activa
    ->sidebarCollapsibleOnDesktop()
    ->spa()
    ->unsavedChangesAlerts();
    // Sin notificaciones de base de datos
```

**Testing de Notificaciones Email**:
```bash
# Verificar que todas las notificaciones usan solo email
grep -r "return \['mail'\]" app/Notifications/

# Resultado esperado: 10 archivos
# (PurchaseOrderDigest ya era ['mail'] desde el inicio)

# Verificar templates de email
ls -la resources/views/emails/

# Resultado esperado:
# - quote/sent.blade.php
# - purchase-order/created.blade.php
# - purchase-order/status-changed.blade.php
# - collection-account/sent.blade.php
# - collection-account/status-changed.blade.php
# - production-order/sent.blade.php
# - commercial-request/*.blade.php
# - stock/alert.blade.php
```

**Lecciones Aprendidas**:
1. **No sobre-ingeniar**: A veces la solución más simple es la mejor
2. **Email es suficiente**: Para muchos casos de uso, email cubre las necesidades
3. **UI != Valor**: La UI de notificaciones no agrega valor si no funciona bien
4. **Pragmatismo**: Mejor tener un sistema simple que funcione que uno complejo que falle
