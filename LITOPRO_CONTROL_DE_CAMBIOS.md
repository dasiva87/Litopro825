# LitoPro 3.0 - Documento Maestro de Control de Cambios

**Versión del Sistema:** Laravel 12.25.0 + Filament 4.0.3 + PHP 8.3.21
**Última Actualización:** 2025-11-07
**Propósito:** Control de cambios, trazabilidad y seguimiento de nuevas ideas/desarrollos

---

## 📋 ÍNDICE

1. [Información General del Proyecto](#información-general-del-proyecto)
2. [Módulos y Funcionalidades](#módulos-y-funcionalidades)
3. [Historial de Cambios por Sprint](#historial-de-cambios-por-sprint)
4. [Sistema de Usuarios y Permisos](#sistema-de-usuarios-y-permisos)
5. [Módulo de Cotizaciones](#módulo-de-cotizaciones)
6. [Módulo de Inventario](#módulo-de-inventario)
7. [Módulo de Órdenes](#módulo-de-órdenes)
8. [Sistema de Notificaciones](#sistema-de-notificaciones)
9. [Configuración y Multi-Tenant](#configuración-y-multi-tenant)
10. [Servicios de Cálculo](#servicios-de-cálculo)
11. [Red Social Empresarial](#red-social-empresarial)
12. [Tareas Pendientes y Roadmap](#tareas-pendientes-y-roadmap)
13. [Control de Versiones](#control-de-versiones)

---

## 🎯 INFORMACIÓN GENERAL DEL PROYECTO

### Descripción
LitoPro 3.0 es un **SaaS multi-tenant** para gestión de litografías y papelerías, que permite:
- Cotización automática con cálculos técnicos avanzados
- Gestión de órdenes de producción y compra
- Control de inventario con alertas inteligentes
- Red social empresarial entre litografías
- Sistema de suscripciones y facturación

### Stack Tecnológico
| Componente | Versión | Propósito |
|------------|---------|-----------|
| Laravel | 12.25.0 | Backend framework |
| PHP | 8.3.21 | Lenguaje |
| Filament | 4.0.3 | Admin panel |
| Livewire | 3.6.4 | Componentes reactivos |
| TailwindCSS | 4.1.12 | Estilos |
| MySQL | - | Base de datos |
| Spatie Permission | - | Roles y permisos |
| Laravel Cashier | - | Suscripciones Stripe |

### Estadísticas del Proyecto
- **62 Modelos** de base de datos
- **19 Recursos Filament** (CRUD completos)
- **19 Servicios** de lógica de negocio
- **29 Widgets** de dashboard
- **125 Migraciones** de BD
- **56 Permisos** organizados en 12 categorías
- **8 Roles** de usuario
- **~10,776 líneas** de código en modelos

---

## 📦 MÓDULOS Y FUNCIONALIDADES

### 1. MÓDULO DE USUARIOS Y AUTENTICACIÓN

#### Funcionalidades Principales
- ✅ Registro y login de usuarios
- ✅ Sistema de roles y permisos (Spatie)
- ✅ Multi-tenancy por empresa (company_id)
- ✅ Impersonación de usuarios (Super Admin)
- ✅ Gestión de perfil con avatar
- ✅ Recuperación de contraseña

#### Modelos Involucrados
| Modelo | Archivo | Propósito |
|--------|---------|-----------|
| User | `app/Models/User.php` | Usuario del sistema |
| Role | Spatie | Roles (8 tipos) |
| Permission | Spatie | Permisos (56 totales) |
| Company | `app/Models/Company.php` | Empresa (tenant) |

#### Relaciones Clave
```
User
├── belongsTo: Company (multi-tenant)
├── hasRoles: Role (Spatie)
├── hasPermissions: Permission (Spatie)
└── morphMany: DatabaseNotification
```

#### Roles del Sistema
1. **Super Admin**: Acceso total, gestiona empresas
2. **Company Admin**: Administrador de empresa
3. **Manager**: Gerente con acceso amplio
4. **Salesperson**: Vendedor (cotizaciones, contactos)
5. **Operator**: Operador de producción
6. **Customer**: Cliente externo
7. **Employee**: Empleado general
8. **Client**: Cliente (legacy)

#### Permisos por Rol (Sprint 14)
| Rol | Permisos Clave |
|-----|----------------|
| Super Admin | Todos (56) |
| Company Admin | view-users, create-users, edit-users, manage-settings, view-reports |
| Manager | view-documents, create-documents, view-production-orders, manage-inventory |
| Salesperson | view-contacts, create-contacts, view-documents, create-documents |

#### Recursos Filament
- **UserResource**: CRUD de usuarios
  - Form: `app/Filament/Resources/Users/Schemas/UserForm.php`
  - Table: `app/Filament/Resources/Users/Tables/UsersTable.php`
  - Verificación: `canViewAny()` - Solo Admin/Manager

- **RoleResource**: CRUD de roles
  - Form: `app/Filament/Resources/Roles/Schemas/RoleForm.php`
  - Categorías: Usuarios, Contactos, Cotizaciones, Documentos, Órdenes, Productos, Empresas, Inventario, Sistema, Reportes, Red Social
  - Verificación: `canViewAny()` - Solo Admin

#### Políticas de Seguridad
- **UserPolicy**: `app/Policies/UserPolicy.php`
  - `viewAny()`: Solo Admin
  - `create()`, `update()`, `delete()`: Solo Admin

- **RolePolicy**: `app/Policies/RolePolicy.php`
  - Similar a UserPolicy

#### Cambios Recientes
- **Sprint 14.1**: Ocultada opción "Tiro y Retiro en Misma Plancha"
- **Sprint 14.2**: Fix crítico - Salesperson bloqueado de Papers, PrintingMachines, Finishings, CollectionAccounts
- **Sprint 14.3**: Fix interfaz - Agregadas categorías de permisos faltantes (Empresas, Inventario)

#### Issues Conocidos
- ⚠️ ProductionOrderResource sin verificación de permisos

---

### 2. MÓDULO DE CONTACTOS Y CLIENTES

#### Funcionalidades Principales
- ✅ Gestión de contactos (clientes/proveedores)
- ✅ Multi-tenant por empresa
- ✅ Tipos: customer, supplier, both
- ✅ Geolocalización (país, estado, ciudad)
- ✅ Soft deletes
- ✅ Historial de documentos por contacto

#### Modelo Principal
**Contact** (`app/Models/Contact.php`)

**Campos Principales:**
- `company_id` (multi-tenant)
- `type`: customer | supplier | both
- `name`, `email`, `phone`, `mobile`
- `tax_id` (NIT/RUT)
- `address`, `city_id`, `state_id`, `country_id`
- `is_active`, `notes`

**Relaciones:**
```
Contact
├── belongsTo: Company (multi-tenant)
├── belongsTo: Country, State, City
└── hasMany: Document (cotizaciones del contacto)
```

#### Recurso Filament
**ContactResource** (`app/Filament/Resources/ContactResource.php`)
- Form: `app/Filament/Resources/Contacts/Schemas/ContactForm.php`
- Table: `app/Filament/Resources/Contacts/Tables/ContactsTable.php`
- RelationManager: `SuppliersRelationManager`

**Verificación de Permisos:**
- Policy: ✅ `ContactPolicy`
- canViewAny(): ❌ PENDIENTE

#### Permisos Asociados
- `view-contacts`
- `create-contacts`
- `edit-contacts`
- `delete-contacts`

#### Cómo Funciona
1. Usuario con permiso `create-contacts` crea contacto
2. Sistema asigna automáticamente `company_id` del usuario (BelongsToTenant)
3. Contacto solo visible para usuarios de la misma empresa
4. Puede ser seleccionado en cotizaciones (Documents)

#### Relación con Otros Módulos
- **Documents**: Un contacto puede tener múltiples cotizaciones
- **PurchaseOrders**: Si es proveedor, puede recibir órdenes de compra
- **Geolocalización**: Usa Country, State, City para dirección

---

### 3. MÓDULO DE COTIZACIONES (Documents)

#### Funcionalidades Principales
- ✅ Cotizaciones, órdenes y facturas
- ✅ Items polimórficos (6 tipos diferentes)
- ✅ Cálculo automático de costos
- ✅ Versionado de documentos
- ✅ Estados de flujo (draft → sent → approved → in_production → completed)
- ✅ Generación de PDFs
- ✅ Conversión a órdenes de compra/producción

#### Modelos Principales

##### Document (`app/Models/Document.php`)
**Propósito:** Cotización/Orden/Factura

**Campos Principales:**
- `company_id`, `user_id`, `contact_id`, `document_type_id`
- `document_number` (COT-2025-001)
- `status`: draft | sent | approved | rejected | in_production | completed | cancelled
- `subtotal`, `discount_amount`, `tax_amount`, `total`
- `version`, `parent_document_id` (versionado)

**Relaciones:**
```
Document
├── belongsTo: Company, User, Contact, DocumentType
├── hasMany: DocumentItem (items polimórficos)
├── hasMany: PurchaseOrder
├── hasMany: childVersions (versionado)
└── belongsTo: parentDocument
```

**Métodos Clave:**
- `calculateTotals()`: Calcula subtotal, descuento, impuestos, total
- `generateDocumentNumber()`: COT-2025-001
- `markAsSent()`, `markAsApproved()`: Transiciones de estado
- `createNewVersion()`: Crea nueva versión del documento

##### DocumentItem (`app/Models/DocumentItem.php`)
**Propósito:** Item polimórfico dentro de un documento

**Campos Principales:**
- `document_id`, `company_id`
- `itemable_type`, `itemable_id` (polimórfico)
- `printing_machine_id`, `paper_id`
- `quantity`, `unit_price`, `total_price`
- `order_status`: available | in_cart | ordered | received

**Relaciones:**
```
DocumentItem
├── belongsTo: Document
├── morphTo: itemable (6 tipos)
│   ├── SimpleItem
│   ├── Product
│   ├── DigitalItem
│   ├── TalonarioItem
│   ├── MagazineItem
│   └── CustomItem
├── belongsTo: PrintingMachine, Paper
├── hasMany: finishings
├── belongsToMany: PurchaseOrder (pivot)
├── belongsToMany: ProductionOrder (pivot)
└── belongsToMany: CollectionAccount (pivot)
```

**Métodos Clave:**
- `calculateTotals()`: Calcula precios según tipo de item
- `generateDescription()`: Genera descripción automática
- `updateOrderStatus()`: Actualiza estado según órdenes

#### Tipos de Items (Polimórficos)

##### 1. SimpleItem - Item de Impresión Sencillo
**Archivo:** `app/Models/SimpleItem.php`

**Propósito:** Trabajos de impresión offset tradicional (volantes, afiches, etc.)

**Características:**
- Cálculo automático de montaje (copias por pliego)
- Divisor de cortes (Sprint 13)
- Millares sobre impresiones
- Sistema de acabados (Sprint 14)
- Descripción auto-concatenada

**Campos Clave:**
- `horizontal_size`, `vertical_size` (tamaño del trabajo)
- `quantity` (cantidad a producir)
- `ink_front_count`, `ink_back_count` (tintas 4×0, 4×4, etc.)
- `paper_id`, `printing_machine_id`
- `mounting_type`: automatic | custom

**Cálculos Involucrados:**
1. **Montaje**: Cuántas copias caben en tamaño de máquina
2. **Divisor**: Cuántos cortes de máquina caben en pliego
3. **Impresiones**: Cantidad ÷ copias por montaje
4. **Pliegos**: Impresiones ÷ divisor
5. **Millares**: Impresiones ÷ 1000
6. **Costos**: Papel + Impresión + Acabados + Adicionales

**Métodos Clave:**
- `calculateAll()`: Cálculo completo (usa SimpleItemCalculatorService)
- `getMountingWithCuts()`: Montaje + divisor (Sprint 13)
- `addFinishing()`: Agregar acabado (Sprint 14)

**Relaciones:**
```
SimpleItem
├── belongsTo: Company, Paper, PrintingMachine
├── morphMany: DocumentItem
└── belongsToMany: Finishing (pivot simple_item_finishing)
```

##### 2. Product - Producto del Catálogo
**Archivo:** `app/Models/Product.php`

**Propósito:** Productos pre-configurados del catálogo (libros, revistas estándar, etc.)

**Características:**
- Precio fijo por unidad
- Stock integrado
- Sin cálculos complejos

**Campos Clave:**
- `name`, `sku`, `category`
- `cost_price`, `sale_price`
- `stock`, `min_stock`

##### 3. DigitalItem - Servicio de Impresión Digital
**Archivo:** `app/Models/DigitalItem.php`

**Propósito:** Impresión digital (banners, vinilos, lona, etc.)

**Características:**
- Precio por tamaño (m²)
- Precio por unidad
- Precio fijo

**Campos Clave:**
- `pricing_type`: fixed | size | unit
- `unit_value`, `width`, `height`
- `material`, `finish`

##### 4. TalonarioItem - Talonario Numerado
**Archivo:** `app/Models/TalonarioItem.php`

**Propósito:** Talonarios con numeración consecutiva

**Características:**
- Numeración inicial/final
- Copias por talonario (original + copias)
- Papel carbón opcional

**Campos Clave:**
- `numeracion_inicial`, `numeracion_final`
- `copias_por_talonario`
- `papel_carbon` (boolean)

**Relaciones:**
- `hasMany`: TalonarioSheet (hojas del talonario)

##### 5. MagazineItem - Revista con Múltiples Páginas
**Archivo:** `app/Models/MagazineItem.php`

**Propósito:** Revistas con páginas diferentes

**Características:**
- Cubierta diferente al interior
- Múltiples papeles
- Encuadernación

**Campos Clave:**
- `total_pages`
- `tipo_encuadernacion`
- `cubierta_diferente` (boolean)
- `papel_interior_id`, `papel_cubierta_id`

**Relaciones:**
- `hasMany`: MagazinePage

##### 6. CustomItem - Item Personalizado
**Archivo:** `app/Models/CustomItem.php`

**Propósito:** Items sin cálculo automático

**Características:**
- Precio manual
- Sin validaciones técnicas
- Flexible

**Campos Clave:**
- `description`, `quantity`
- `unit_price`, `total_price`
- `notes`

#### Recurso Filament
**DocumentResource** (`app/Filament/Resources/DocumentResource.php`)

**Páginas:**
- List: Listado de documentos
- Edit: Edición con gestión de items
- View: Vista de solo lectura

**Forms (Factory Pattern):**
- `ProductDocumentForm.php`
- `CustomItemDocumentForm.php`
- `DocumentItemFormFactory.php` (crea forms según tipo)

**Handlers (Polimórficos):**
- `ProductHandler.php`
- `SimpleItemHandler.php`
- `DigitalItemHandler.php`
- `TalonarioItemHandler.php`
- `MagazineItemHandler.php`
- `CustomItemHandler.php`

**Verificación de Permisos:**
- Policy: ✅ `DocumentPolicy`
- canViewAny(): ❌ PENDIENTE

#### Flujo Completo de Cotización

```
1. Crear Document (status: draft)
   ↓
2. Agregar DocumentItems (polimórficos)
   ├── SimpleItem → Cálculo automático
   ├── Product → Precio fijo
   └── Otros tipos
   ↓
3. Sistema calcula totales automáticamente
   ↓
4. Enviar a cliente (status: sent)
   ↓
5. Cliente revisa y responde
   ↓
6. Aprobar (status: approved)
   ↓
7. Crear PurchaseOrder para proveedores
   ↓
8. Crear ProductionOrder para producción
   ↓
9. Completar órdenes
   ↓
10. Document finalizado (status: completed)
```

#### Cómo se Relacionan los Módulos

```
Contact
  ↓ (selecciona cliente)
Document (cotización)
  ↓ (agrega items)
DocumentItem (polimórfico)
  ├── itemable_type: SimpleItem
  │   ↓ (calcula con)
  │   SimpleItemCalculatorService
  │   ├── MountingCalculatorService
  │   ├── CuttingCalculatorService
  │   └── FinishingCalculatorService
  ├── itemable_type: Product
  └── itemable_type: otros tipos
  ↓ (aprobado)
PurchaseOrder (para proveedores)
ProductionOrder (para producción interna)
```

#### Cambios Recientes
- **Sprint 13**: Nuevo sistema de montaje con divisor de cortes
- **Sprint 14**: Sistema de acabados para SimpleItem
- **Sprint 14.1**: Fix de interfaz en SimpleItemForm

---

### 4. MÓDULO DE INVENTARIO

#### Funcionalidades Principales
- ✅ Gestión de papeles (Papers)
- ✅ Gestión de máquinas de impresión (PrintingMachines)
- ✅ Gestión de acabados (Finishings)
- ✅ Control de stock con alertas
- ✅ Movimientos de inventario con trazabilidad
- ✅ Predicción de necesidades
- ✅ Reportes de valoración

#### Modelos Principales

##### Paper - Papel
**Archivo:** `app/Models/Paper.php`

**Propósito:** Catálogo de papeles disponibles

**Campos Principales:**
- `company_id` (multi-tenant)
- `name`, `type` (bond, couché, kraft, etc.)
- `weight` (gramaje: 75gr, 90gr, 150gr, etc.)
- `width`, `height` (tamaño en cm)
- `cost_per_sheet` (costo por pliego)
- `stock`, `min_stock`, `max_stock`
- `is_active`, `supplier_id`

**Relaciones:**
```
Paper
├── belongsTo: Company, Supplier
├── hasMany: DocumentItem
└── morphMany: StockMovement, StockAlert
```

**Traits:**
- `BelongsToTenant`: Multi-tenancy
- `StockManagement`: Métodos de stock

**Métodos de StockManagement:**
- `addStock($quantity, $reason)`: Agregar stock
- `removeStock($quantity, $reason)`: Remover stock
- `isLowStock()`: Verifica si está bajo stock mínimo
- `isCriticalStock()`: Verifica nivel crítico

##### PrintingMachine - Máquina de Impresión
**Archivo:** `app/Models/PrintingMachine.php`

**Propósito:** Máquinas offset disponibles

**Campos Principales:**
- `company_id` (multi-tenant)
- `name`, `model`, `brand`
- `max_width`, `max_height` (tamaño máximo en cm)
- `max_colors` (colores máximos)
- `cost_per_impression` (costo por millar)
- `setup_cost` (costo de preparación)
- `costo_ctp` (costo de planchas CTP)
- `is_active`, `supplier_id`

**Relaciones:**
```
PrintingMachine
├── belongsTo: Company, Supplier
└── hasMany: DocumentItem
```

**Métodos:**
- `calculateCostForQuantity($impressions)`: Calcula costo por millar

##### Finishing - Acabado
**Archivo:** `app/Models/Finishing.php`

**Propósito:** Acabados disponibles (laminado, barniz, corte, etc.)

**Enums:**
- `FinishingMeasurementUnit`: MILLAR, RANGO, TAMAÑO, UNIDAD, FIJO, CUSTOM
- `FinishingType`: LAMINADO, BARNIZ, CORTE, DOBLEZ, ENCUADERNACION, PERFORADO, TROQUELADO

**Campos Principales:**
- `name`
- `measurement_unit`: MILLAR | RANGO | TAMAÑO | UNIDAD | FIJO | CUSTOM
- `finishing_type`
- `fixed_cost`, `cost_per_unit`
- `is_active`, `supplier_id`

**Relaciones:**
```
Finishing
├── belongsTo: Supplier
├── hasMany: FinishingRange (rangos de precios)
└── belongsToMany: SimpleItem, DigitalItem, TalonarioItem, MagazineItem
```

**Cómo se Calcula:**
El cálculo lo hace `FinishingCalculatorService.php`:
- **MILLAR**: `$quantity ÷ 1000 × $cost_per_unit`
- **RANGO**: Busca en FinishingRange según cantidad
- **TAMAÑO**: `($width × $height) ÷ 10000 × $cost_per_unit` (m²)
- **UNIDAD**: `$quantity × $cost_per_unit`
- **FIJO**: `$fixed_cost`

##### StockMovement - Movimiento de Inventario
**Archivo:** `app/Models/StockMovement.php`

**Propósito:** Trazabilidad de movimientos de stock

**Campos Principales:**
- `company_id`, `user_id`
- `stockable_type`, `stockable_id` (polimórfico)
- `type`: purchase | sale | adjustment | transfer | damage | return
- `quantity` (+ para entrada, - para salida)
- `unit_cost`, `total_cost`
- `reference`, `notes`, `movement_date`

**Relaciones:**
```
StockMovement
├── belongsTo: Company, User
└── morphTo: stockable (Paper, Product, etc.)
```

##### StockAlert - Alerta de Stock
**Archivo:** `app/Models/StockAlert.php`

**Propósito:** Alertas de stock crítico

**Campos Principales:**
- `company_id`
- `stockable_type`, `stockable_id` (polimórfico)
- `alert_type`: low_stock | out_of_stock | expiring_soon
- `alert_level`: info | warning | critical
- `current_stock`, `min_stock`, `threshold`
- `status`: active | acknowledged | resolved
- `acknowledged_by`, `acknowledged_at`
- `resolved_by`, `resolved_at`

**Relaciones:**
```
StockAlert
├── belongsTo: Company, acknowledgedBy, resolvedBy
└── morphTo: stockable
```

#### Servicios de Inventario

##### StockMovementService
**Archivo:** `app/Services/StockMovementService.php`

**Métodos:**
- `recordMovement($stockable, $type, $quantity, $reason)`
- `purchase($stockable, $quantity, $unitCost, $reference)`
- `sale($stockable, $quantity, $unitCost, $reference)`
- `adjustment($stockable, $quantity, $reason)`
- `transfer($stockable, $quantity, $destination, $reason)`
- `getMovementHistory($stockable)`

##### StockAlertService
**Archivo:** `app/Services/StockAlertService.php`

**Métodos:**
- `checkStock($stockable)`: Verifica nivel de stock
- `createAlert($stockable, $alertType, $alertLevel)`
- `acknowledgeAlert($alert, $user)`: Usuario reconoce alerta
- `resolveAlert($alert, $user)`: Alerta resuelta
- `getActiveAlerts($company)`: Alertas activas de la empresa

##### StockNotificationService
**Archivo:** `app/Services/StockNotificationService.php`

**Propósito:** Envía notificaciones de stock crítico

**Métodos:**
- `notifyLowStock($stockable)`
- `notifyOutOfStock($stockable)`
- `notifyExpiringSoon($stockable)`
- `sendAlertNotifications($alert)`

##### StockPredictionService
**Archivo:** `app/Services/StockPredictionService.php`

**Propósito:** Predice necesidades de stock

**Métodos:**
- `predictNextMonth($stockable)`
- `getConsumptionRate($stockable)`: Tasa de consumo
- `estimateReorderPoint($stockable)`: Punto de reorden

##### StockReportService
**Archivo:** `app/Services/StockReportService.php`

**Propósito:** Reportes de inventario

**Métodos:**
- `getStockSummary($company)`: Resumen de stock
- `getLowStockItems($company)`: Items con stock bajo
- `getValuation($company)`: Valoración de inventario
- `getMovementReport($company, $startDate, $endDate)`

#### Recursos Filament

**PaperResource**
- Archivo: `app/Filament/Resources/PaperResource.php`
- Form: `app/Filament/Resources/Papers/Schemas/PaperForm.php`
- Verificación: ✅ `canViewAny()` - Solo Admin/Manager

**PrintingMachineResource**
- Archivo: `app/Filament/Resources/PrintingMachineResource.php`
- Form: `app/Filament/Resources/PrintingMachines/Schemas/PrintingMachineForm.php`
- Verificación: ✅ `canViewAny()` - Solo Admin/Manager

**FinishingResource**
- Archivo: `app/Filament/Resources/FinishingResource.php`
- Verificación: ✅ `canViewAny()` - Solo Admin/Manager

#### Widgets de Inventario (10)

1. **SimpleStockKpisWidget**: KPIs básicos
2. **StockKpisWidget**: KPIs avanzados
3. **StockMovementsKpisWidget**: KPIs de movimientos
4. **StockAlertsWidget**: Alertas activas
5. **AdvancedStockAlertsWidget**: Alertas con análisis
6. **StockTrendsChartWidget**: Gráfico de tendencias
7. **StockLevelTrackingWidget**: Seguimiento de niveles
8. **StockMovementsTableWidget**: Tabla de movimientos
9. **StockPredictionsWidget**: Predicciones
10. **RecentMovementsWidget**: Movimientos recientes

#### Flujo de Stock

```
1. Compra de Papel
   ↓
2. StockMovementService.purchase()
   ↓
3. Paper.addStock(quantity, reason)
   ↓
4. Registro en stock_movements
   ↓
5. Actualización de stock
   ↓
6. StockAlertService.checkStock()
   ↓
7. Si stock < min_stock:
   ├── Crear StockAlert (warning)
   └── StockNotificationService.notifyLowStock()
   ↓
8. Si stock == 0:
   ├── Crear StockAlert (critical)
   └── StockNotificationService.notifyOutOfStock()
```

#### Cambios Recientes
- **Sprint 14.2**: Bloqueado acceso de Salesperson a Papers, PrintingMachines, Finishings

---

### 5. MÓDULO DE ÓRDENES

#### Funcionalidades Principales
- ✅ Órdenes de compra a proveedores (PurchaseOrder)
- ✅ Órdenes de producción interna (ProductionOrder)
- ✅ Cuentas de cobro a clientes (CollectionAccount)
- ✅ Gestión de estados con historial
- ✅ Multi-paper support (revistas con varios papeles)
- ✅ Generación de PDFs

#### Modelos Principales

##### PurchaseOrder - Orden de Compra
**Archivo:** `app/Models/PurchaseOrder.php`

**Propósito:** Órdenes de compra a proveedores (litografías)

**Campos Principales:**
- `company_id` (cliente que ordena)
- `order_number` (PO-2025-001)
- `supplier_company_id` (proveedor)
- `status`: draft | sent | confirmed | in_production | completed | cancelled
- `order_date`, `expected_delivery_date`, `actual_delivery_date`
- `subtotal`, `tax_amount`, `total`
- `created_by`, `approved_by`, `approved_at`

**Relaciones:**
```
PurchaseOrder
├── belongsTo: Company, SupplierCompany
├── belongsTo: createdBy, approvedBy (User)
├── belongsToMany: DocumentItem (pivot)
├── hasMany: PurchaseOrderItem (multi-paper)
└── hasMany: statusHistories
```

**Métodos:**
- `generateOrderNumber()`: PO-2025-001
- `calculateTotals()`: Suma items
- `markAsConfirmed()`, `markAsCompleted()`

**Arquitectura Multi-Paper (Sprint 13):**
```
PurchaseOrder
  ├── PurchaseOrderItem #1 (papel Bond 90gr)
  │   └── documentItem: Magazine Interior
  └── PurchaseOrderItem #2 (papel Couché 150gr)
      └── documentItem: Magazine Cubierta
```

##### PurchaseOrderItem - Item de Orden de Compra
**Archivo:** `app/Models/PurchaseOrderItem.php`

**Propósito:** Entity pivot para soporte multi-papel

**Campos Principales:**
- `purchase_order_id`
- `document_item_id`
- `paper_id` (específico por item)
- `quantity_ordered`, `unit_price`, `total_price`
- `status`, `notes`, `paper_description`

**Relaciones:**
```
PurchaseOrderItem
├── belongsTo: PurchaseOrder
├── belongsTo: DocumentItem
└── belongsTo: Paper
```

**Métodos:**
- `getPaperNameAttribute()`: Obtiene nombre con carga dinámica

##### ProductionOrder - Orden de Producción
**Archivo:** `app/Models/ProductionOrder.php`

**Propósito:** Órdenes de producción interna

**Campos Principales:**
- `company_id`
- `order_number` (PRO-2025-001)
- `supplier_id` (si es externa)
- `status`: pending | in_progress | paused | completed | cancelled
- `priority`: low | normal | high | urgent
- `expected_start_date`, `actual_start_date`
- `expected_completion_date`, `actual_completion_date`
- `operator_id`, `quality_checked_by`, `quality_status`
- `total_impressions`, `total_sheets`

**Relaciones:**
```
ProductionOrder
├── belongsTo: Company, Supplier
├── belongsTo: operator, qualityCheckedBy (User)
└── belongsToMany: DocumentItem (pivot)
```

**Métodos:**
- `generateOrderNumber()`: PRO-2025-001
- `calculateTotals()`: Suma impresiones y pliegos

##### CollectionAccount - Cuenta de Cobro
**Archivo:** `app/Models/CollectionAccount.php`

**Propósito:** Cuentas de cobro a clientes

**Campos Principales:**
- `company_id` (proveedor que cobra)
- `account_number` (CC-2025-001)
- `client_company_id` (cliente)
- `status`: draft | sent | confirmed | in_production | completed | invoiced | cancelled
- `account_date`, `due_date`
- `subtotal`, `tax_amount`, `total`
- `created_by`, `approved_by`, `approved_at`

**Relaciones:**
```
CollectionAccount
├── belongsTo: Company, ClientCompany
├── belongsTo: createdBy, approvedBy (User)
├── belongsToMany: DocumentItem (pivot)
└── hasMany: statusHistories
```

**Métodos:**
- `generateAccountNumber()`: CC-2025-001
- `calculateTotals()`

#### Recursos Filament

**PurchaseOrderResource**
- Archivo: `app/Filament/Resources/PurchaseOrderResource.php`
- Form: `app/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderForm.php`
- Policy: ✅ `PurchaseOrderPolicy`
- canViewAny(): ❌ PENDIENTE

**ProductionOrderResource**
- Archivo: `app/Filament/Resources/ProductionOrderResource.php`
- Policy: ❌ Sin Policy
- canViewAny(): ❌ Sin verificación

**CollectionAccountResource**
- Archivo: `app/Filament/Resources/CollectionAccountResource.php`
- Verificación: ✅ `canViewAny()` - Solo Admin/Manager

#### Widgets de Órdenes (8)

1. **ActiveDocumentsWidget**: Documentos activos
2. **RecentOrdersWidget**: Órdenes recientes
3. **PurchaseOrdersOverviewWidget**: Resumen de compras
4. **PurchaseOrderNotificationsWidget**: Notificaciones
5. **ReceivedOrdersWidget**: Órdenes recibidas
6. **PendingOrdersStatsWidget**: Estadísticas pendientes
7. **DeliveryAlertsWidget**: Alertas de entrega
8. **DeadlinesWidget**: Plazos

#### Flujo Completo de Órdenes

```
Document (cotización aprobada)
  ↓ (seleccionar items)
  ↓
¿Quién produce?
  ├── Proveedor Externo
  │   ↓
  │   PurchaseOrder
  │   ├── Crear orden
  │   ├── Seleccionar items
  │   ├── Sistema crea PurchaseOrderItems (multi-paper)
  │   ├── Enviar a proveedor (status: sent)
  │   ├── Proveedor confirma (status: confirmed)
  │   ├── En producción (status: in_production)
  │   └── Completado (status: completed)
  │
  └── Producción Interna
      ↓
      ProductionOrder
      ├── Crear orden
      ├── Asignar operador
      ├── Calcular impresiones/pliegos
      ├── En progreso (status: in_progress)
      └── Completado (status: completed)
```

#### Cambios Recientes
- **Sprint 13**: Arquitectura multi-paper en PurchaseOrder

#### Issues Conocidos
- ⚠️ ProductionOrderResource sin verificación de permisos

---

## 🔔 SISTEMA DE NOTIFICACIONES

### 4 Tipos de Notificaciones

LitoPro 3.0 tiene **4 sistemas de notificaciones** independientes:

#### 1. Notificaciones Sociales (SocialNotification)

**Archivo:** `app/Models/SocialNotification.php`

**Propósito:** Notificaciones de red social interna

**Tipos:**
- `post_created`: Post creado
- `post_liked`: Post con like
- `post_commented`: Comentario en post
- `company_followed`: Empresa seguida

**Campos:**
- `company_id`, `user_id`, `sender_id`
- `type`, `title`, `message`
- `data` (JSON)
- `read_at`

**Tabla:** `social_notifications`

**Cómo Funciona:**
```php
// Crear notificación automática al crear post
SocialPost::created(function ($post) {
    // Notificar a seguidores de la empresa
    SocialNotification::create([
        'company_id' => $post->company_id,
        'type' => 'post_created',
        'sender_id' => $post->author_id,
        'title' => 'Nuevo post',
        'message' => $post->author->name . ' publicó: ' . $post->title
    ]);
});
```

#### 2. Alertas de Inventario (StockAlert)

**Archivo:** `app/Models/StockAlert.php`

**Propósito:** Alertas de stock crítico

**Tipos:**
- `low_stock`: Stock bajo
- `out_of_stock`: Sin stock
- `expiring_soon`: Próximo a vencer

**Niveles:**
- `info`: Informativo
- `warning`: Advertencia
- `critical`: Crítico

**Tabla:** `stock_alerts`

**Servicio:** `StockNotificationService.php`

**Cómo Funciona:**
```php
// Verificación automática al actualizar stock
Paper::updated(function ($paper) {
    if ($paper->stock < $paper->min_stock) {
        StockAlert::create([
            'company_id' => $paper->company_id,
            'stockable_type' => Paper::class,
            'stockable_id' => $paper->id,
            'alert_type' => 'low_stock',
            'alert_level' => 'warning',
            'current_stock' => $paper->stock,
            'min_stock' => $paper->min_stock
        ]);

        // Notificar usuarios
        app(StockNotificationService::class)->notifyLowStock($paper);
    }
});
```

#### 3. Sistema Avanzado de Notificaciones

**Modelos:**
- `NotificationChannel`: Canales configurables
- `NotificationRule`: Reglas de envío
- `NotificationLog`: Logs de envío

**Canales Soportados:**
- `email`: Email (SMTP)
- `database`: Base de datos
- `sms`: SMS (Twilio)
- `push`: Push notifications
- `custom`: Personalizado

**Tablas:**
- `notification_channels`
- `notification_rules`
- `notification_logs`

**Servicio:** `NotificationService.php`

**Cómo Funciona:**
```php
use App\Services\NotificationService;

$service = app(NotificationService::class);

// Enviar notificación multi-canal
$service->send(
    type: 'order_completed',
    userId: $user->id,
    data: ['order_id' => $order->id],
    priority: 'high' // low, medium, high, urgent
);

// Sistema determina qué canales usar según reglas
// Registra en notification_logs para auditoría
```

**Características:**
- ✅ Deduplicación de notificaciones
- ✅ Filtrado por rol y severidad
- ✅ Procesamiento asíncrono (Laravel Queue)
- ✅ Auditoría completa

#### 4. Laravel Notifications (Sistema Base)

**Archivo:** `app/Models/DatabaseNotification.php`

**Propósito:** Sistema estándar de Laravel

**Tabla:** `notifications`

**Cómo Funciona:**
```php
use App\Notifications\DocumentCreatedNotification;

// Enviar notificación
$user->notify(new DocumentCreatedNotification($document));

// Obtener notificaciones no leídas
$user->unreadNotifications

// Marcar como leída
$notification->markAsRead();
```

### Documentación Completa

Para documentación detallada del sistema de notificaciones, ver:
- `NOTIFICATION_SYSTEM_SUMMARY.md`: Guía rápida
- `NOTIFICATION_SYSTEM_ANALYSIS.md`: Análisis técnico completo
- `NOTIFICATION_FILE_REFERENCES.md`: Índice de archivos

---

## ⚙️ CONFIGURACIÓN Y MULTI-TENANT

### Sistema Multi-Tenant

#### Arquitectura
LitoPro 3.0 usa **multi-tenancy por company_id** con aislamiento total de datos.

**Trait Principal:** `BelongsToTenant`
**Archivo:** `app/Models/Concerns/BelongsToTenant.php`

```php
trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // Scope global automático
        static::addGlobalScope(new TenantScope);

        // Asigna company_id automáticamente al crear
        static::creating(function ($model) {
            $model->company_id = $model->company_id ?? auth()->user()->company_id;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
```

**TenantScope:** `app/Models/Scopes/TenantScope.php`

```php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check() && !auth()->user()->hasRole('Super Admin')) {
            $builder->where('company_id', auth()->user()->company_id);
        }
    }
}
```

#### Modelos Afectados (90% del sistema)
Todos los modelos con `BelongsToTenant` trait:
- Documents, DocumentItems, SimpleItems
- Papers, PrintingMachines, Finishings
- Contacts, Products, DigitalItems
- PurchaseOrders, ProductionOrders, CollectionAccounts
- StockMovements, StockAlerts
- SocialPosts, SocialNotifications
- Users (excepto Super Admin)

#### Company - Empresa (Tenant)

**Archivo:** `app/Models/Company.php`

**Campos Principales:**
- `name`, `slug`, `email`, `phone`
- `city_id`, `state_id`, `country_id`
- `tax_id`, `logo`, `website`, `bio`
- `subscription_plan`: free | basic | professional | enterprise
- `subscription_expires_at`
- `max_users`, `is_active`, `status`
- `company_type`: Litografía | Papelería

**Relaciones:**
```
Company
├── hasMany: Users, Contacts, Papers, PrintingMachines
├── hasMany: Products, Documents, Invoices
├── hasMany: UsageMetrics, ActivityLogs
├── hasOne: CompanySettings
├── belongsTo: Country, State, City
├── hasMany: supplierRequests, receivedSupplierRequests
├── hasMany: supplierRelationships, clientRelationships
└── hasMany: followers (CompanyFollower)
```

**Scopes:**
- `active()`, `byPlan()`, `byStatus()`
- `suspended()`, `cancelled()`, `onTrial()`, `pending()`
- `litografias()`, `papelerias()`, `byType()`

**Métodos Clave:**
- `getCurrentPlan()`: Obtiene plan actual (Sprint 14.1 fix)
- `hasActiveSubscription()`: Verifica suscripción activa
- `suspend()`, `reactivate()`, `cancel()`: Gestión de estado
- `follow()`, `unfollow()`, `isFollowing()`: Red social empresas

#### CompanySettings - Configuración

**Archivo:** `app/Models/CompanySettings.php`

**Campos:**
- `company_id`
- `timezone`, `currency`, `language`
- `tax_rate`, `date_format`, `time_format`
- `invoice_prefix`, `quote_prefix`, `order_prefix`
- `email_notifications`, `sms_notifications`

#### Sistema de Suscripciones

##### Plan - Plan de Suscripción

**Archivo:** `app/Models/Plan.php`

**Planes Disponibles:**
- `free`: Plan gratuito (limitado)
- `basic`: Plan básico
- `professional`: Plan profesional
- `enterprise`: Plan empresarial (personalizable)

**Campos:**
- `name`, `slug`, `description`
- `price`, `currency`, `interval` (month/year)
- `trial_days`
- `features` (JSON), `limits` (JSON)
- `is_active`, `is_featured`, `sort_order`

**Límites por Plan:**
```json
{
    "max_users": 5,
    "max_documents": 100,
    "max_storage": 1024,
    "features": [
        "documents",
        "simple_items",
        "stock_management"
    ]
}
```

##### Subscription - Suscripción

**Archivo:** `app/Models/Subscription.php`

**Proveedor:** Laravel Cashier (Stripe)

**Campos:**
- `company_id`, `user_id`
- `name`, `stripe_id`, `stripe_status`, `stripe_price`
- `quantity`, `trial_ends_at`, `ends_at`

##### PlanLimitService - Verificación de Límites

**Archivo:** `app/Services/PlanLimitService.php`

**Métodos:**
- `canAddUser(Company $company)`: Verifica límite de usuarios
- `canCreateDocument(Company $company)`: Verifica límite de documentos
- `canAccessFeature(Company $company, $feature)`: Verifica acceso a feature
- `getRemainingLimit(Company $company, $limitType)`: Límite restante

**Uso:**
```php
use App\Services\PlanLimitService;

$service = app(PlanLimitService::class);

// Verificar antes de crear usuario
if (!$service->canAddUser($company)) {
    throw new Exception('Límite de usuarios alcanzado');
}

// Verificar feature
if (!$service->canAccessFeature($company, 'advanced_reports')) {
    throw new Exception('Feature no disponible en tu plan');
}
```

#### Servicio de Contexto

**TenantContext** (`app/Services/TenantContext.php`)

**Métodos:**
- `setTenant(Company $company)`: Establecer tenant
- `getTenant()`: Obtener tenant actual
- `clearTenant()`: Limpiar contexto
- `runInTenantContext(Company $company, Closure $callback)`: Ejecutar en contexto

#### Páginas de Configuración

**CompanyProfile**
- Archivo: `app/Filament/Pages/CompanyProfile.php`
- Propósito: Perfil público de empresa
- URL: `/admin/empresa/{slug}`

**CompanySettings**
- Archivo: `app/Filament/Pages/CompanySettings.php`
- Propósito: Configuración de empresa

**Billing**
- Archivo: `app/Filament/Pages/Billing.php`
- Propósito: Facturación y suscripciones

#### Cambios Recientes
- **Sprint 14.1**: Fix crítico en `getCurrentPlan()` - Redirección a /admin/billing resuelto

---

## 🧮 SERVICIOS DE CÁLCULO

### Arquitectura de Cálculo (Sprint 13)

LitoPro 3.0 usa un sistema de cálculo modular con **3 servicios principales**:

```
SimpleItemCalculatorService (Orquestador)
  ├── MountingCalculatorService (Montaje)
  ├── CuttingCalculatorService (Divisor de cortes)
  └── FinishingCalculatorService (Acabados)
```

### SimpleItemCalculatorService

**Archivo:** `app/Services/SimpleItemCalculatorService.php`

**Propósito:** Orquestador principal de cálculos para SimpleItem

#### Métodos Principales

##### 1. calculateFinalPricingNew() - Sistema NUEVO (Sprint 13)

**Flujo:**
```
1. calculateMountingWithCuts()
   ├── MountingCalculatorService: copias por pliego
   └── CuttingCalculatorService: divisor de cortes

2. calculatePrintingMillaresNew()
   └── Millares sobre IMPRESIONES (no pliegos)

3. calculateFinishingsCost()
   └── FinishingCalculatorService: acabados

4. calculateAdditionalCosts()
   └── Corte, montaje, diseño, transporte

5. Calcular total + margen → Precio final
```

**Retorna:** `PricingResult` (DTO)
```php
PricingResult {
    mountingOption: MountingOption,
    printingCalculation: PrintingCalculation,
    additionalCosts: AdditionalCosts,
    subtotal: float,
    profitMargin: float,
    finalPrice: float,
    unitPrice: float,
    costBreakdown: array
}
```

##### 2. calculateMountingWithCuts() - Montaje + Divisor

**Propósito:** Calcula montaje con divisor de cortes (NUEVO sistema)

**Ejemplo:**
```
Trabajo: 22×28 cm
Máquina: 50×35 cm
Pliego: 100×70 cm
Cantidad: 1000

PASO 1: Montaje (MountingCalculatorService)
  → 2 copias por pliego en máquina 50×35

PASO 2: Divisor (CuttingCalculatorService)
  → 4 cortes de 50×35 en pliego 100×70

PASO 3: Cálculo
  → Impresiones: 1000 ÷ 2 = 500
  → Pliegos: 500 ÷ 4 = 125
  → Millares: 500 ÷ 1000 = 0.5 → 1 millar
```

**Retorna:**
```php
[
    'mounting' => [...],
    'copies_per_mounting' => 2,
    'divisor' => 4,
    'divisor_layout' => ['horizontal_cuts' => 2, 'vertical_cuts' => 2],
    'impressions_needed' => 500,
    'sheets_needed' => 125,
    'total_impressions' => 500,
    'total_copies_produced' => 1000,
    'waste_copies' => 0,
    'paper_cost' => 62500.0
]
```

##### 3. calculatePureMounting() - Montaje Puro

**Propósito:** Solo montaje, sin divisor (cuántas copias por pliego)

**Usa:** `MountingCalculatorService` directamente

##### 4. calculateFinalPricing() - Sistema LEGACY

**Propósito:** Sistema anterior (sin divisor de cortes)

**Diferencia con NUEVO:**
```
❌ LEGACY: Pliegos = 1000 ÷ 9 = 112 pliegos
✅ NUEVO: Impresiones = 1000 ÷ 2 = 500 → Pliegos = 500 ÷ 4 = 125
```

### MountingCalculatorService

**Archivo:** `app/Services/MountingCalculatorService.php`

**Propósito:** Cálculo PURO de montaje (copias por pliego)

#### Características
- NO conoce papel ni divisor
- Solo calcula cuántas copias caben en tamaño de máquina
- Calcula en 3 orientaciones (horizontal, vertical, maximum)

#### Métodos

##### calculateMounting()

**Parámetros:**
- `$workWidth`, `$workHeight`: Tamaño del trabajo (cm)
- `$machineWidth`, `$machineHeight`: Tamaño máximo máquina (cm)
- `$marginPerSide`: Margen por lado (cm)

**Retorna:**
```php
[
    'horizontal' => [
        'copies_per_sheet' => 2,
        'layout' => '1 × 2',
        'horizontal_copies' => 1,
        'vertical_copies' => 2,
        'usable_width' => 48.0,
        'usable_height' => 33.0
    ],
    'vertical' => [
        'copies_per_sheet' => 2,
        'layout' => '2 × 1',
        // ...
    ],
    'maximum' => [
        'copies_per_sheet' => 2,
        // ... (la mejor opción)
    ]
]
```

##### calculateRequiredSheets()

**Parámetros:**
- `$totalCopies`: Total de copias a producir
- `$copiesPerSheet`: Copias por pliego

**Retorna:**
```php
[
    'sheets_needed' => 250,
    'total_copies_produced' => 500,
    'waste_copies' => 0
]
```

### CuttingCalculatorService

**Archivo:** `app/Services/CuttingCalculatorService.php`

**Propósito:** Cálculo de divisor de cortes (cuántos cortes de máquina en pliego)

#### Métodos

##### calculateCuts()

**Parámetros:**
- `$paperWidth`, `$paperHeight`: Tamaño del pliego (cm)
- `$cutWidth`, `$cutHeight`: Tamaño del corte de máquina (cm)
- `$desiredCuts`: Cortes deseados (opcional)
- `$orientation`: horizontal | vertical | both

**Retorna:**
```php
[
    'cutsPerSheet' => 4,
    'sheetsNeeded' => 125,
    'totalCutsProduced' => 500,
    'wastePercentage' => 2.5,
    'arrangeResult' => [
        'horizontal_cuts' => 2,
        'vertical_cuts' => 2,
        'total_cuts' => 4
    ]
]
```

##### arrangeMultipleCuts()

**Propósito:** Optimiza layout de cortes en papel

**Ejemplo:**
```
Pliego 100×70
Corte 50×35

Horizontal: 2 cortes (100÷50=2, 70÷35=2)
Vertical: 2 cortes (100÷35=2.8→2, 70÷50=1.4→1)

Mejor: Horizontal → 2×2 = 4 cortes
```

### FinishingCalculatorService

**Archivo:** `app/Services/FinishingCalculatorService.php`

**Propósito:** Cálculo de costos de acabados

#### Métodos por Tipo de Medición

##### calculateByMillar()

**Parámetros:** `$finishing`, `$quantity`

**Cálculo:**
```php
$millares = $quantity / 1000;
$cost = $millares * $finishing->cost_per_unit;
```

##### calculateByRange()

**Parámetros:** `$finishing`, `$quantity`

**Cálculo:**
```php
// Busca en FinishingRange según cantidad
$range = $finishing->ranges()
    ->where('min_quantity', '<=', $quantity)
    ->where('max_quantity', '>=', $quantity)
    ->first();

$cost = $range->price;
```

##### calculateBySize()

**Parámetros:** `$finishing`, `$width`, `$height`

**Cálculo:**
```php
$area_m2 = ($width * $height) / 10000; // cm² → m²
$cost = $area_m2 * $finishing->cost_per_unit;
```

##### calculateByUnit()

**Parámetros:** `$finishing`, $quantity`

**Cálculo:**
```php
$cost = $quantity * $finishing->cost_per_unit;
```

##### calculateFixed()

**Parámetros:** `$finishing`

**Cálculo:**
```php
$cost = $finishing->fixed_cost;
```

#### Uso desde SimpleItem

```php
use App\Services\FinishingCalculatorService;

$item = SimpleItem::first();
$item->load('finishings');

$finishingCalc = new FinishingCalculatorService();

foreach ($item->finishings as $finishing) {
    $params = [
        'quantity' => $item->quantity,
        'width' => $item->horizontal_size,
        'height' => $item->vertical_size
    ];

    $cost = $finishingCalc->calculateCost($finishing, $params);
}
```

### Comparación: Sistema Anterior vs Nuevo

#### Sistema Anterior (Legacy)
```
Trabajo 22×28 en pliego 100×70
Montaje: 9 copias (3×3) directamente en pliego
Pliegos: 1000 ÷ 9 = 112 pliegos
Millares: 112 ÷ 1000 = 0.112 → 1 millar
```

#### Sistema Nuevo (Sprint 13)
```
Trabajo 22×28 en máquina 50×35
Montaje: 2 copias
Divisor: 50×35 en pliego 100×70 → 4 cortes
Impresiones: 1000 ÷ 2 = 500
Pliegos: 500 ÷ 4 = 125 pliegos
Millares: 500 ÷ 1000 = 0.5 → 1 millar
```

**Ventajas del Nuevo:**
- ✅ Más preciso (millares sobre impresiones)
- ✅ Soporta cortes de máquina en pliego
- ✅ Mejor optimización de papel
- ✅ Cálculo separado de montaje y divisor

### Otros Servicios de Cálculo

#### DigitalItemCalculatorService
**Archivo:** `app/Services/DigitalItemCalculatorService.php`

**Métodos:**
- `calculateTotalPrice(DigitalItem $item, array $params)`
- `calculateByFixed()`, `calculateBySize()`, `calculateByUnit()`

#### TalonarioCalculatorService
**Archivo:** `app/Services/TalonarioCalculatorService.php`

**Métodos:**
- `calculateCost(TalonarioItem $item)`
- `calculateSheetCost(TalonarioSheet $sheet)`

#### MagazineCalculatorService
**Archivo:** `app/Services/MagazineCalculatorService.php`

**Métodos:**
- `calculateCost(MagazineItem $item)`
- `calculatePageCost(MagazinePage $page)`

---

## 🌐 RED SOCIAL EMPRESARIAL

### Funcionalidades Principales
- ✅ Posts y publicaciones entre empresas
- ✅ Sistema de seguimiento entre empresas
- ✅ Comentarios y respuestas
- ✅ Reacciones (like, love, haha, wow, sad, angry)
- ✅ Notificaciones en tiempo real
- ✅ Niveles de visibilidad (public, company, department, role)

### Modelos Principales

#### SocialPost - Publicación
**Archivo:** `app/Models/SocialPost.php`

**Campos:**
- `company_id`, `author_id`
- `title`, `content`, `image`
- `visibility`: public | company | department | role
- `likes_count`, `comments_count`, `shares_count`

**Relaciones:**
```
SocialPost
├── belongsTo: Company, Author (User)
├── hasMany: Reactions, Comments, Likes
└── morphMany: SocialNotification
```

**Scopes:**
- `published()`, `byVisibility()`, `recent()`

#### SocialPostComment - Comentario
**Archivo:** `app/Models/SocialPostComment.php`

**Campos:**
- `post_id`, `author_id`, `content`
- `parent_comment_id` (para respuestas)

**Relaciones:**
```
SocialPostComment
├── belongsTo: Post, Author, ParentComment
├── hasMany: Replies (SocialPostComment)
└── hasMany: Likes
```

#### SocialPostReaction - Reacción
**Archivo:** `app/Models/SocialPostReaction.php`

**Tipos:** like, love, haha, wow, sad, angry

**Campos:**
- `post_id`, `user_id`, `reaction_type`

#### CompanyFollower - Seguimiento entre Empresas
**Archivo:** `app/Models/CompanyFollower.php`

**Campos:**
- `follower_company_id` (quien sigue)
- `followed_company_id` (quien es seguido)
- `user_id` (usuario que creó el seguimiento)

**Relaciones:**
```
CompanyFollower
├── belongsTo: FollowerCompany (Company)
├── belongsTo: FollowedCompany (Company)
└── belongsTo: User
```

**Métodos en Company:**
```php
// Seguir empresa
$company->follow($otherCompany);

// Dejar de seguir
$company->unfollow($otherCompany);

// Verificar si sigue
$company->isFollowing($otherCompany); // boolean
```

### Políticas de Seguridad

**SocialPostPolicy** (`app/Policies/SocialPostPolicy.php`)

**Métodos:**
- `viewAny()`: Requiere `view-posts`
- `create()`: Requiere `create-posts`
- `update()`: Requiere `edit-posts` O ser autor
- `delete()`: Requiere `delete-posts` O ser autor

**Verificación en Widget:**
```php
// CreatePostWidget.php
public static function canView(): bool
{
    return auth()->user()->can('create', SocialPost::class);
}
```

### Widgets de Red Social (5)

1. **SocialFeedWidget**: Feed de posts
   - Archivo: `app/Filament/Widgets/SocialFeedWidget.php`
   - Muestra posts de empresas seguidas

2. **CreatePostWidget**: Crear post
   - Archivo: `app/Filament/Widgets/CreatePostWidget.php`
   - Verificación: `canView()` - Solo con permiso `create-posts`

3. **CompanyPostsWidget**: Posts de la empresa
   - Archivo: `app/Filament/Widgets/CompanyPostsWidget.php`

4. **SocialPostWidget**: Post individual
   - Archivo: `app/Filament/Widgets/SocialPostWidget.php`

5. **SuggestedCompaniesWidget**: Empresas sugeridas
   - Archivo: `app/Filament/Widgets/SuggestedCompaniesWidget.php`

### Páginas

**CompanyProfile** (`app/Filament/Pages/CompanyProfile.php`)
- URL: `/admin/empresa/{slug}`
- Muestra perfil público de empresa
- Posts, seguidores, productos

**Companies** (`app/Filament/Pages/Companies.php`)
- URL: `/admin/empresas`
- Listado de empresas (Super Admin)
- Búsqueda y filtros

### Flujo de Red Social

```
1. Usuario crea SocialPost
   ↓
2. Sistema crea SocialNotification automática
   ├── Notifica a seguidores de la empresa
   └── type: post_created
   ↓
3. Otros usuarios ven post en SocialFeedWidget
   ↓
4. Usuario da like/reacción
   ├── Incrementa likes_count en post
   ├── Crea SocialPostReaction
   └── Crea SocialNotification (type: post_liked)
   ↓
5. Usuario comenta
   ├── Crea SocialPostComment
   ├── Incrementa comments_count
   └── Crea SocialNotification (type: post_commented)
```

### Niveles de Visibilidad

| Visibility | Quién puede ver |
|------------|-----------------|
| `public` | Todas las empresas |
| `company` | Solo empresa del autor |
| `department` | Solo departamento específico |
| `role` | Solo rol específico |

### Cambios Recientes
- **Sprint 14.4**: Fix de verificación de permisos en CreatePostWidget

---

## 📈 HISTORIAL DE CAMBIOS POR SPRINT

### Sprint 15 (06-Nov-2025) - Documentación Sistema de Notificaciones

**Objetivo:** Documentar exhaustivamente el sistema de notificaciones

**Documentos Generados:**
- `NOTIFICATION_SYSTEM_ANALYSIS.md` (40 KB)
- `NOTIFICATION_SYSTEM_SUMMARY.md` (15 KB)
- `NOTIFICATION_FILE_REFERENCES.md` (11 KB)
- `README_NOTIFICATIONS.md`

**Hallazgos:**
- 4 tipos de notificaciones documentados
- 7 tablas multi-tenant
- 2 servicios principales
- 5 canales de comunicación

**Archivos Analizados:** 27 archivos, 2600+ líneas de código

---

### Sprint 14.4 (06-Nov-2025) - Fix Verificación de Permisos en Acciones

**Problema:** Usuario Salesperson sin permiso `create-posts` podía crear posts

**Causa Raíz:** CreatePostWidget NO verificaba permisos antes de permitir la acción

**Solución:**
1. Creada `SocialPostPolicy` con verificación completa
2. Widget protegido con `canView()` y verificación en `createPost()`

**Archivos Modificados:**
- `app/Policies/SocialPostPolicy.php` (CREADO)
- `app/Filament/Widgets/CreatePostWidget.php`

**Testing:**
- ✅ Salesperson sin create-posts: Widget NO aparece
- ✅ Manager con create-posts: Widget visible y funcional

---

### Sprint 14.3 (06-Nov-2025) - Fix Interfaz de Gestión de Roles

**Problema:** Formulario de roles solo mostraba 43 permisos de 56 existentes

**Permisos Faltantes:**
- Gestión de Empresas (4 permisos)
- Inventario (3 permisos)

**Solución:**
1. Agregadas secciones faltantes en `RoleForm.php`
2. Actualizado `EditRole.php` para cargar/guardar nuevas categorías

**Archivos Modificados:**
- `app/Filament/Resources/Roles/Schemas/RoleForm.php`
- `app/Filament/Resources/Roles/Pages/EditRole.php`

**Resultado:** Ahora muestra TODOS los 56 permisos del sistema

---

### Sprint 14.2 (06-Nov-2025) - Fix Crítico de Permisos por Rol

**Problema:** Salesperson tenía acceso a recursos de Admin (Papers, Machines, etc.)

**Causa Raíz:** Recursos críticos NO tenían `canViewAny()` configurado

**Solución:** Agregado `canViewAny()` a:
- `PaperResource`
- `PrintingMachineResource`
- `FinishingResource`
- `CollectionAccountResource`

**Restricción:** Solo `Super Admin`, `Company Admin`, `Manager`

**Archivos Modificados:**
- `app/Filament/Resources/Papers/PaperResource.php`
- `app/Filament/Resources/PrintingMachines/PrintingMachineResource.php`
- `app/Filament/Resources/Finishings/FinishingResource.php`
- `app/Filament/Resources/CollectionAccounts/CollectionAccountResource.php`

---

### Sprint 14.1 (06-Nov-2025) - UI de Acabados + Fix de Billing

**1. Interfaz de Acabados en SimpleItem**

**Cambios:**
- Agregada sección "🎨 Acabados Sugeridos" en `SimpleItemForm.php`
- Repeater con relación `finishings`
- Auto-población de parámetros según tipo
- Cálculo en tiempo real

**Archivo:** `app/Filament/Resources/SimpleItems/Schemas/SimpleItemForm.php`

**2. Ocultada Opción "Tiro y Retiro en Misma Plancha"**

**Cambio:** Removido Toggle `front_back_plate` de la interfaz

**3. Fix Crítico: Redirección a /admin/billing**

**Problema:** Usuarios quedaban atrapados en página de billing

**Causa Raíz:**
1. `getCurrentPlan()` retornaba `null` para plan "free"
2. Método buscaba por `name` en lugar de `slug`
3. Company tenía `status = 'incomplete'`

**Solución:**
- Corregido `getCurrentPlan()` para buscar por `slug`
- Removida exclusión de plan "free"
- Actualizado status de empresa a 'active'

**Archivo:** `app/Models/Company.php` (líneas 313-321)

---

### Sprint 14 (06-Nov-2025) - Sistema de Acabados para SimpleItem

**Objetivo:** Implementar sistema de acabados con parámetros dinámicos

**Características:**
- Sistema híbrido: SimpleItem (sugerencias) + DocumentItem (aplicados)
- Tabla pivot `simple_item_finishing` con parámetros dinámicos
- Auto-construcción de parámetros según tipo de medición
- Integración completa con SimpleItemCalculatorService

**Métodos Agregados en SimpleItem:**
- `addFinishing()`: Agregar acabado con parámetros
- `calculateFinishingsCost()`: Suma costos de acabados
- `getFinishingsBreakdown()`: Desglose detallado

**Ejemplo de Uso:**
```php
$item = SimpleItem::first();

// Agregar acabado con parámetros automáticos
$plastificado = Finishing::where('measurement_unit', 'millar')->first();
$item->addFinishing($plastificado);

// Agregar acabado con parámetros manuales
$barnizUV = Finishing::where('measurement_unit', 'tamaño')->first();
$item->addFinishing($barnizUV, ['width' => 20, 'height' => 13], isDefault: true);

// Obtener costo total
$totalCost = $item->calculateFinishingsCost();
```

**Parámetros Auto-construidos:**
- MILLAR/RANGO/UNIDAD → `['quantity' => $item->quantity]`
- TAMAÑO → `['width' => $item->horizontal_size, 'height' => $item->vertical_size]`

---

### Sprint 13 (05-Nov-2025) - Nuevo Sistema de Montaje con Divisor de Cortes

**Objetivo:** Implementar cálculo de millares sobre impresiones (no pliegos)

**Arquitectura:**
```
MountingCalculatorService (montaje puro)
  +
CuttingCalculatorService (divisor de cortes)
  =
SimpleItemCalculatorService (integración completa)
```

**Método Principal:** `calculateMountingWithCuts()`

**Flujo:**
1. Calcular montaje (copias en tamaño de máquina)
2. Calcular divisor (cortes de máquina en pliego)
3. Calcular impresiones (cantidad ÷ copias por montaje)
4. Calcular pliegos (impresiones ÷ divisor)
5. Calcular millares (impresiones ÷ 1000)

**Ejemplo:**
```
Trabajo 22×28 → Máquina 50×35 → Montaje: 2 copias
Divisor: 50×35 en pliego 100×70 → 4 cortes
Impresiones: 1000 ÷ 2 = 500
Pliegos: 500 ÷ 4 = 125
Millares: 500 ÷ 1000 = 0.5 → 1 millar
```

**Diferencia con Sistema Anterior:**
- ❌ Antes: Millares sobre pliegos
- ✅ Ahora: Millares sobre impresiones

---

## ✅ TAREAS PENDIENTES Y ROADMAP

### Prioridad Alta - Seguridad

#### 1. Completar Verificación de Permisos

**Recursos con Verificación Parcial (Policy sin canViewAny):**
- [ ] `DocumentResource`
- [ ] `ContactResource`
- [ ] `ProductResource`
- [ ] `SimpleItemResource`
- [ ] `PurchaseOrderResource`

**Acción:** Agregar método `canViewAny()` a cada recurso

**Ejemplo:**
```php
public static function canViewAny(): bool
{
    return auth()->user()->can('viewAny', Document::class);
}
```

#### 2. ProductionOrderResource Sin Protección

**Estado Actual:**
- ❌ Sin Policy
- ❌ Sin canViewAny()

**Acciones:**
1. Crear `ProductionOrderPolicy`
2. Agregar `canViewAny()` en `ProductionOrderResource`
3. Testing completo

---

### Prioridad Media - Testing

#### 1. Testing de Roles y Permisos

**Casos de Prueba:**
- [ ] Salesperson solo ve recursos permitidos
- [ ] Manager tiene acceso amplio
- [ ] Company Admin no puede ver otras empresas
- [ ] Super Admin tiene acceso total

#### 2. Testing de Aislamiento Multi-Tenant

**Casos de Prueba:**
- [ ] Empresa A no puede ver datos de Empresa B
- [ ] Super Admin puede ver todas las empresas
- [ ] Usuario sin empresa no puede acceder al sistema

#### 3. Testing de Cálculos

**Casos de Prueba:**
- [ ] Sistema nuevo de montaje con divisor
- [ ] Cálculo de acabados por cada tipo
- [ ] Pricing completo de SimpleItem
- [ ] Validaciones técnicas (tamaño excede máquina, etc.)

---

### Prioridad Media - Documentación

#### 1. Guía de Usuario Final

**Contenido:**
- Cómo crear cotizaciones
- Cómo gestionar inventario
- Cómo usar red social empresarial
- FAQ

#### 2. Guía de Desarrollo

**Contenido:**
- Cómo agregar nuevo tipo de item
- Cómo modificar servicios de cálculo
- Cómo agregar nuevo permiso
- Estándares de código

#### 3. Documentación Técnica

**Contenido:**
- Arquitectura multi-tenant detallada
- Sistema de cálculo paso a paso
- Guía de testing

---

### Prioridad Baja - Mejoras

#### 1. Optimización de Performance

**Áreas:**
- Carga eager de relaciones
- Caching de catálogos (Papers, Machines)
- Optimización de queries N+1

#### 2. UI/UX

**Áreas:**
- Mejora de widgets de dashboard
- Diseño responsive
- Accesibilidad

#### 3. Integraciones

**Áreas:**
- API REST completa
- Webhooks
- Integraciones con ERP externos

---

## 📊 CONTROL DE VERSIONES

### Versiones del Sistema

| Versión | Fecha | Descripción |
|---------|-------|-------------|
| 3.0.15 | 2025-11-07 | Documentación sistema de notificaciones |
| 3.0.14 | 2025-11-06 | Fix permisos + Sistema de acabados |
| 3.0.13 | 2025-11-05 | Nuevo sistema de montaje con divisor |
| 3.0.12 | - | Sistema de órdenes multi-paper |
| 3.0.11 | - | Red social empresarial |
| 3.0.0 | - | Lanzamiento inicial LitoPro 3.0 |

### Cómo Mantener Este Documento

#### Al Agregar un Nuevo Módulo

1. Actualizar sección correspondiente en "Módulos y Funcionalidades"
2. Agregar modelos, relaciones y métodos clave
3. Documentar recursos Filament asociados
4. Agregar a "Historial de Cambios"
5. Actualizar "Control de Versiones"

#### Al Modificar Funcionalidad Existente

1. Actualizar sección del módulo afectado
2. Marcar cambios con "Sprint XX"
3. Agregar a "Historial de Cambios por Sprint"
4. Actualizar "Control de Versiones"

#### Al Completar Tarea Pendiente

1. Marcar tarea como completada con ✅
2. Actualizar sección del módulo
3. Agregar a "Historial de Cambios"

### Responsabilidades

| Rol | Responsabilidad |
|-----|----------------|
| Desarrollador | Actualizar documento con cada cambio |
| Tech Lead | Revisar y aprobar cambios |
| Product Manager | Mantener sección de Roadmap |

---

## 📚 REFERENCIAS Y DOCUMENTACIÓN ADICIONAL

### Documentos Relacionados

| Documento | Propósito |
|-----------|-----------|
| `README_INVENTARIO.md` | Índice de navegación |
| `RESUMEN_EJECUTIVO_INVENTARIO.md` | Resumen ejecutivo rápido |
| `PROYECTO_LITOPRO_INVENTARIO_COMPLETO.md` | Inventario técnico completo |
| `NOTIFICATION_SYSTEM_SUMMARY.md` | Guía de notificaciones |
| `CLAUDE.md` | Instrucciones para Claude |

### Stack y Herramientas

| Herramienta | Versión | Documentación |
|-------------|---------|---------------|
| Laravel | 12.25.0 | https://laravel.com/docs/12.x |
| Filament | 4.0.3 | https://filamentphp.com/docs/4.x |
| Livewire | 3.6.4 | https://livewire.laravel.com/docs/3.x |
| Spatie Permission | - | https://spatie.be/docs/laravel-permission |
| Laravel Cashier | - | https://laravel.com/docs/12.x/billing |

### Comandos Útiles

```bash
# Desarrollo
php artisan serve --port=8000
php artisan tinker

# Testing
php artisan test
php artisan test --filter SimpleItemTest

# Base de Datos
php artisan migrate:fresh --seed
php artisan litopro:setup-demo --fresh

# Caché
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear

# Calidad de Código
vendor/bin/pint
composer analyse
```

---

**FIN DEL DOCUMENTO MAESTRO DE CONTROL DE CAMBIOS**

**Última Actualización:** 2025-11-07
**Versión del Documento:** 1.0
**Próxima Revisión:** Después de Sprint 16

---

## 📝 REGISTRO DE CAMBIOS DE ESTE DOCUMENTO

| Fecha | Versión | Cambios |
|-------|---------|---------|
| 2025-11-07 | 1.0 | Creación inicial del documento maestro |
