# GrafiRed 3.0 - Resumen Ejecutivo del Inventario

**Generado:** 2025-11-07  
**Documento completo:** `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md`

---

## 📊 NÚMEROS CLAVE

| Métrica | Valor |
|---------|-------|
| **Total Modelos** | 62 |
| **Recursos Filament** | 19 |
| **Servicios de Negocio** | 19 |
| **Widgets Dashboard** | 29 |
| **Páginas Personalizadas** | 11 |
| **Políticas de Seguridad** | 10 |
| **Migraciones BD** | 125 |
| **Líneas de Código (Models)** | ~10,776 |

---

## 🎯 MÓDULOS PRINCIPALES

### 1. Sistema Multi-Tenant
- **Empresas Independientes** con aislamiento total por `company_id`
- **8 Roles**: Super Admin, Company Admin, Manager, Salesperson, Operator, Customer, Employee, Client
- **56 Permisos** organizados en 12 categorías

### 2. Gestión de Documentos
- **3 Tipos**: Cotizaciones, Órdenes, Facturas
- **6 Tipos de Items Polimórficos**: SimpleItem, Product, DigitalItem, TalonarioItem, MagazineItem, CustomItem
- **Versionado** de documentos con historial
- **Cálculo Automático** de costos según tipo de item

### 3. Sistema de Cotización Avanzado
- **Cálculo de Montaje** en 3 orientaciones (horizontal, vertical, máximo)
- **Divisor de Cortes** para optimizar uso de papel
- **Sistema de Acabados** con parámetros dinámicos
- **Cálculo de Millares** sobre impresiones (no pliegos)

### 4. Gestión de Órdenes
- **Órdenes de Compra** (PurchaseOrder) - Multi-paper support
- **Órdenes de Producción** (ProductionOrder) - Control de impresión
- **Cuentas de Cobro** (CollectionAccount) - Facturación

### 5. Inventario y Stock
- **Gestión de Stock** con alertas automáticas
- **Movimientos de Inventario** con trazabilidad completa
- **Predicción de Necesidades** con StockPredictionService
- **Alertas Multinivel** (info, warning, critical)

### 6. Red Social Empresarial
- **Posts y Publicaciones** entre empresas
- **Sistema de Seguimiento** entre empresas
- **Comentarios y Reacciones** (like, love, haha, wow, sad, angry)
- **Notificaciones en Tiempo Real**

---

## 🔑 ARQUITECTURAS CLAVE

### Multi-Tenancy
```
BelongsToTenant Trait
  ↓
TenantScope (Global)
  ↓
Aislamiento por company_id
  ↓
✅ Seguridad Multi-Tenant
```

### Sistema Polimórfico de Items
```
DocumentItem
  ├── SimpleItem (Impresión sencilla)
  ├── Product (Catálogo)
  ├── DigitalItem (Servicios digitales)
  ├── TalonarioItem (Talonarios numerados)
  ├── MagazineItem (Revistas)
  └── CustomItem (Personalizado)
```

### Nuevo Sistema de Cálculo (Sprint 13)
```
Montaje (copias en máquina)
  ↓
Divisor (cortes de máquina en pliego)
  ↓
Millares sobre Impresiones
  ↓
Costo Final
```

---

## 🛡️ SEGURIDAD

### Verificación de Permisos (3 Capas)

```
1. Spatie Permission (Base de Datos)
   ↓
2. Laravel Policies (Lógica de Negocio)
   ↓
3. Filament Resources (Interfaz)
   ↓
✅ Acceso Permitido
```

### Estado Actual

| Estado | Recursos |
|--------|----------|
| ✅ Completo | Users, Roles, Papers, PrintingMachines, Finishings, CollectionAccounts, SocialPosts |
| ⚠️ Parcial | Documents, Contacts, Products, SimpleItems, PurchaseOrders |
| ❌ Sin Protección | ProductionOrders |

---

## 📦 SERVICIOS DE CÁLCULO

### SimpleItemCalculatorService
- **Método Nuevo:** `calculateFinalPricingNew()` - Usa montaje + divisor
- **Método Legacy:** `calculateFinalPricing()` - Sistema anterior
- **Integra:** MountingCalculatorService + CuttingCalculatorService + FinishingCalculatorService

### MountingCalculatorService
- **Cálculo Puro:** Cuántas copias caben en máquina
- **3 Orientaciones:** Horizontal, Vertical, Maximum
- **NO conoce:** Papel ni divisor de cortes

### CuttingCalculatorService
- **Cálculo de Divisor:** Cuántos cortes de máquina caben en pliego
- **Optimización:** Mejor orientación para minimizar desperdicio
- **Retorna:** Layout de cortes (H×V)

### FinishingCalculatorService
- **6 Tipos de Medición:** MILLAR, RANGO, TAMAÑO, UNIDAD, FIJO, CUSTOM
- **Cálculo Dinámico:** Según parámetros del item
- **Integración:** SimpleItem, DigitalItem, TalonarioItem, MagazineItem

---

## 🗄️ BASE DE DATOS

### Tablas Core (12 Categorías)

1. **Sistema:** users, companies, company_settings, permissions
2. **Documentos:** documents, document_types, document_items
3. **Items:** simple_items, products, digital_items, talonario_items, magazine_items, custom_items
4. **Catálogo:** papers, printing_machines, finishings, contacts
5. **Órdenes:** purchase_orders, production_orders, collection_accounts
6. **Inventario:** stock_movements, stock_alerts
7. **Red Social:** social_posts, social_post_comments, social_post_reactions
8. **Notificaciones:** notification_channels, notification_rules, notification_logs, social_notifications
9. **Suscripciones:** plans, subscriptions, invoices, usage_metrics
10. **Proveedores:** supplier_requests, supplier_relationships
11. **Sistema:** activity_logs, dashboard_widgets, automated_reports
12. **Geolocalización:** countries, states, cities

### Pivots Principales

| Tabla Pivot | Relación | Propósito |
|-------------|----------|-----------|
| simple_item_finishing | SimpleItem ↔ Finishing | Acabados con parámetros |
| document_item_purchase_order | DocumentItem ↔ PurchaseOrder | Items en órdenes |
| document_item_production_order | DocumentItem ↔ ProductionOrder | Items en producción |
| document_item_collection_account | DocumentItem ↔ CollectionAccount | Items en cuentas |
| purchase_order_items | PurchaseOrder ↔ DocumentItem | Multi-paper support |

---

## 🎨 WIDGETS DE DASHBOARD

### Por Categoría

| Categoría | Cantidad | Ejemplos |
|-----------|----------|----------|
| **Stock e Inventario** | 10 | StockKpisWidget, StockAlertsWidget, StockTrendsChartWidget |
| **Documentos y Órdenes** | 8 | ActiveDocumentsWidget, PurchaseOrdersOverviewWidget |
| **Red Social** | 5 | SocialFeedWidget, CreatePostWidget, SuggestedCompaniesWidget |
| **Calculadoras** | 2 | PaperCalculatorWidget, CalculadoraCorteWidget |
| **Sistema** | 4 | DashboardStatsWidget, QuickActionsWidget, OnboardingWidget |

---

## 🔄 FLUJOS DE TRABAJO PRINCIPALES

### 1. Cotización → Producción
```
1. Crear Cotización (Document)
2. Agregar Items (SimpleItem/Product/etc.)
3. Calcular Costos Automáticos
4. Enviar a Cliente (status: sent)
5. Aprobar (status: approved)
6. Crear Orden de Compra (PurchaseOrder)
7. Crear Orden de Producción (ProductionOrder)
8. Producir
9. Completar (status: completed)
```

### 2. Gestión de Stock
```
1. Compra de Papel (StockMovement: purchase)
2. Sistema Actualiza Stock Automáticamente
3. Stock Bajo Nivel Mínimo → StockAlert (warning)
4. Stock Crítico → StockAlert (critical)
5. Notificación a Usuarios (StockNotificationService)
6. Usuario Reconoce Alerta (acknowledged)
7. Compra Nuevo Stock
8. Alerta Resuelta (resolved)
```

### 3. Cálculo de SimpleItem
```
1. Usuario Ingresa: Tamaño (22×28), Cantidad (1000), Tintas (4×0)
2. Sistema Selecciona Máquina (50×35)
3. MountingCalculatorService: 2 copias por pliego
4. CuttingCalculatorService: 4 cortes de máquina en pliego 100×70
5. Impresiones Necesarias: 1000 ÷ 2 = 500
6. Pliegos Necesarios: 500 ÷ 4 = 125
7. Millares: 500 ÷ 1000 = 0.5 → 1 millar
8. Costo Papel: 125 × $500 = $62,500
9. Costo Impresión: 1 millar × 4 tintas × $350 = $1,400
10. Total + Margen → Precio Final
```

---

## 📋 TAREAS PENDIENTES PRIORITARIAS

### 1. Completar Seguridad (Alta Prioridad)
- [ ] Agregar `canViewAny()` a Documents
- [ ] Agregar `canViewAny()` a Contacts
- [ ] Agregar `canViewAny()` a Products
- [ ] Agregar `canViewAny()` a SimpleItems
- [ ] Agregar `canViewAny()` a PurchaseOrders
- [ ] Crear `ProductionOrderPolicy`
- [ ] Agregar `canViewAny()` a ProductionOrderResource

### 2. Testing (Media Prioridad)
- [ ] Testing de roles Salesperson
- [ ] Testing de aislamiento multi-tenant
- [ ] Testing de cálculo de montaje con divisor
- [ ] Testing de acabados en SimpleItem

### 3. Documentación (Media Prioridad)
- [ ] Guía de usuario del nuevo sistema de montaje
- [ ] Documentación de servicios de cálculo
- [ ] Guía de desarrollo de nuevos tipos de items
- [ ] Documentación de sistema de notificaciones

---

## 🔍 NOTAS TÉCNICAS IMPORTANTES

### Sistema de Descripción Auto-Concatenada
```php
// SimpleItem genera automáticamente:
"Volantes promocionales tamaño 10x15 impresión 4x0 en papel Bond 90gr"

// Componentes:
// 1. base_description: "Volantes promocionales" (manual)
// 2. tamaño: "10x15" (automático)
// 3. impresión: "4x0" (automático)
// 4. papel: "Bond 90gr" (automático)
```

### Sistema de Acabados con Parámetros Dinámicos
```php
// Agregar acabado a SimpleItem:
$item->addFinishing($plastificado, ['quantity' => 1000]);
$item->addFinishing($barnizUV, ['width' => 20, 'height' => 13]);

// Parámetros auto-construidos según tipo:
// - MILLAR/RANGO/UNIDAD → ['quantity' => $item->quantity]
// - TAMAÑO → ['width' => $item->horizontal_size, 'height' => $item->vertical_size]
```

### Arquitectura Multi-Paper en PurchaseOrder
```php
// Revistas con varios papeles:
// Magazine Item: Cubierta Bond 90gr + Interior Bond 75gr

// Sistema crea:
// - 1 PurchaseOrder
// - 2 PurchaseOrderItems (uno por papel)
// - Ambos apuntan al mismo DocumentItem
```

---

## 🚀 COMANDOS ÚTILES

```bash
# Desarrollo
php artisan serve --port=8000
php artisan tinker

# Testing
php artisan test
php artisan test --filter SimpleItemCalculatorTest

# Migraciones
php artisan migrate:fresh --seed
php artisan grafired:setup-demo --fresh

# Caché
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Calidad de Código
php artisan pint
composer analyse
```

---

## 📚 REFERENCIAS RÁPIDAS

| Tema | Archivo |
|------|---------|
| Multi-Tenant | `app/Models/Concerns/BelongsToTenant.php` |
| Cálculo Montaje | `app/Services/MountingCalculatorService.php` |
| Cálculo Cortes | `app/Services/CuttingCalculatorService.php` |
| Cálculo SimpleItem | `app/Services/SimpleItemCalculatorService.php` |
| Cálculo Acabados | `app/Services/FinishingCalculatorService.php` |
| Inventario | `app/Models/Concerns/StockManagement.php` |
| Permisos | `database/seeders/PermissionsSeeder.php` |
| Roles | `database/seeders/RolesSeeder.php` |

---

**Para documentación completa, consultar:** `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md`
