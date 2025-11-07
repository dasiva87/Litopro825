# LitoPro 3.0 - Sitemap Completo del Sistema

**Fecha de Generación**: 2025-11-07  
**Versión**: 3.0  
**Stack**: Laravel 12.25.0 + PHP 8.3.21 + Filament 4.0.3 + MySQL

---

## Tabla de Contenidos

1. [Panel de Administración Filament](#1-panel-de-administración-filament)
2. [Recursos Filament (CRUD)](#2-recursos-filament-crud)
3. [Páginas Personalizadas](#3-páginas-personalizadas)
4. [Widgets del Dashboard](#4-widgets-del-dashboard)
5. [Rutas Web Públicas y Protegidas](#5-rutas-web-públicas-y-protegidas)
6. [API Endpoints](#6-api-endpoints)
7. [Modelos y Relaciones](#7-modelos-y-relaciones)
8. [Sistema de Permisos y Roles](#8-sistema-de-permisos-y-roles)
9. [Flujos de la Aplicación](#9-flujos-de-la-aplicación)

---

## 1. Panel de Administración Filament

### URL Base
- **Panel**: `http://localhost:8000/admin`
- **Autenticación**: Sistema multi-tenant por `company_id`

### Grupos de Navegación

| Grupo | Sort | Recursos |
|-------|------|----------|
| **Documentos** | 1 | Contactos, Proveedores, Cotizaciones, Órdenes de Pedido, Órdenes de Producción, Cuentas de Cobro |
| **Items** | 2 | Items Sencillos, Items Revista, Items Talonario, Items Digital |
| **Inventario** | 3 | Productos, Papeles, Gestión de Stock, Movimientos |
| **Configuración** | 4 | Acabados, Máquinas de Impresión |
| **Sistema** | 5 | Usuarios, Roles, Planes, Suscripciones |

---

## 2. Recursos Filament (CRUD)

### 2.1 Grupo: Documentos

#### 📋 Contactos (ContactResource)
- **URL Base**: `/admin/contacts`
- **Modelo**: `App\Models\Contact`
- **Icono**: `heroicon-o-users`
- **Permisos**: 
  - Verificación: `Policy` (parcial, falta `canViewAny()`)
  - Roles permitidos: Todos los autenticados
- **Páginas**:
  - List: `/admin/contacts`
  - Create: `/admin/contacts/create`
  - Edit: `/admin/contacts/{record}/edit`
- **Características**:
  - Multi-tenant por `company_id`
  - Soft deletes habilitado
  - Gestión de clientes y proveedores

#### 🚚 Proveedores (SupplierRelationshipResource)
- **URL Base**: `/admin/supplier-relationships`
- **Modelo**: `App\Models\SupplierRelationship`
- **Icono**: `heroicon-o-truck`
- **Permisos**: 
  - Solo visible para litografías y papelerías
  - Solo litografías pueden editar/eliminar
  - Crear: Deshabilitado (solo desde solicitudes)
- **Páginas**:
  - List: `/admin/supplier-relationships`
  - Edit: `/admin/supplier-relationships/{record}/edit`
- **Características**:
  - Vista dual: Litografías ven proveedores, papelerías ven clientes
  - Requiere aprobación

#### 📄 Cotizaciones (DocumentResource)
- **URL Base**: `/admin/documents`
- **Modelo**: `App\Models\Document`
- **Icono**: `heroicon-o-document-text`
- **Permisos**: 
  - Verificación: `Policy` (parcial, falta `canViewAny()`)
  - Roles permitidos: Todos los autenticados
- **Páginas**:
  - List: `/admin/documents`
  - Create: `/admin/documents/create`
  - Create Quotation: `/admin/documents/create-quotation`
  - View: `/admin/documents/{record}`
  - Edit: `/admin/documents/{record}/edit`
- **RelationManager**: `DocumentItemsRelationManager`
- **Características**:
  - Multi-tenant por `company_id`
  - Soft deletes habilitado
  - Generación de PDF
  - Items relacionados vía polimórfica (SimpleItem, MagazineItem, etc.)

#### 🛒 Órdenes de Pedido (PurchaseOrderResource)
- **URL Base**: `/admin/purchase-orders`
- **Modelo**: `App\Models\PurchaseOrder`
- **Icono**: `heroicon-o-shopping-cart`
- **Permisos**: 
  - Verificación: `Policy` (parcial, falta `canViewAny()`)
  - Roles permitidos: Todos los autenticados
- **Páginas**:
  - List: `/admin/purchase-orders`
  - Create: `/admin/purchase-orders/create`
  - View: `/admin/purchase-orders/{record}`
  - Edit: `/admin/purchase-orders/{record}/edit`
- **RelationManager**: `PurchaseOrderItemsRelationManager`
- **Características**:
  - Vista dual: Muestra órdenes creadas O recibidas como proveedor
  - Multi-paper support (revistas con varios papeles)
  - Generación de PDF
  - FLUJO 1: Desde Purchase Order → Buscar Cotizaciones → Seleccionar Items
  - FLUJO 2: Desde Document Item → Seleccionar Órdenes Abiertas

#### ⚙️ Órdenes de Producción (ProductionOrderResource)
- **URL Base**: `/admin/production-orders`
- **Modelo**: `App\Models\ProductionOrder`
- **Icono**: `heroicon-o-cog-6-tooth`
- **Permisos**: 
  - ❌ Sin verificación de permisos (TAREA PENDIENTE)
  - Roles permitidos: Todos los autenticados
- **Páginas**:
  - List: `/admin/production-orders`
  - Create: `/admin/production-orders/create`
  - View: `/admin/production-orders/{record}`
  - Edit: `/admin/production-orders/{record}/edit`
- **RelationManager**: `ProductionOrderItemsRelationManager`
- **Características**:
  - Asignación de operadores
  - Seguimiento de estado de producción
  - Relación con supplier (proveedor)

#### 💰 Cuentas de Cobro (CollectionAccountResource)
- **URL Base**: `/admin/collection-accounts`
- **Modelo**: `App\Models\CollectionAccount`
- **Icono**: `heroicon-o-banknotes`
- **Permisos**: 
  - Verificación: `canViewAny()` ✅
  - Roles permitidos: Super Admin, Company Admin, Manager
- **Páginas**:
  - List: `/admin/collection-accounts`
  - Create: `/admin/collection-accounts/create`
  - View: `/admin/collection-accounts/{record}`
  - Edit: `/admin/collection-accounts/{record}/edit`
- **RelationManager**: `CollectionAccountItemsRelationManager`
- **Características**:
  - Vista dual: Creadas por empresa O recibidas como cliente
  - Generación de PDF
  - Historial de estados

---

### 2.2 Grupo: Items

#### 📦 Items Sencillos (SimpleItemResource)
- **URL Base**: `/admin/simple-items`
- **Modelo**: `App\Models\SimpleItem`
- **Icono**: `heroicon-o-rectangle-stack`
- **Permisos**: 
  - Verificación: `Policy` (parcial, falta `canViewAny()`)
  - Roles permitidos: Solo litografías (trait `CompanyTypeResource`)
- **Páginas**:
  - List: `/admin/simple-items`
  - Create: `/admin/simple-items/create`
  - Edit: `/admin/simple-items/{record}/edit`
- **Características**:
  - Sistema de acabados sugeridos (tabla pivot `simple_item_finishing`)
  - Cálculo de montaje con divisor (Sprint 13)
  - Pricing completo con acabados (Sprint 14)
  - Calculadora de cortes con validación SVG

#### 📖 Items Revista (MagazineItemResource)
- **URL Base**: `/admin/magazine-items`
- **Modelo**: `App\Models\MagazineItem`
- **Icono**: `heroicon-o-book-open`
- **Permisos**: 
  - Verificación: Solo litografías
  - Roles permitidos: Según trait `CompanyTypeResource`
- **Páginas**:
  - List: `/admin/magazine-items`
  - Create: `/admin/magazine-items/create`
  - Edit: `/admin/magazine-items/{record}/edit`
- **Características**:
  - Páginas múltiples (relación `MagazinePage`)
  - Papel diferente por página (caratula, interior, contracaratula)

#### 📝 Items Talonario (TalonarioItemResource)
- **URL Base**: `/admin/talonario-items`
- **Modelo**: `App\Models\TalonarioItem`
- **Icono**: `heroicon-o-document-duplicate`
- **Permisos**: 
  - Verificación: Solo litografías
  - Roles permitidos: Según trait `CompanyTypeResource`
- **Páginas**:
  - List: `/admin/talonario-items`
  - Create: `/admin/talonario-items/create`
  - Edit: `/admin/talonario-items/{record}/edit`
- **Características**:
  - Hojas múltiples (relación `TalonarioSheet`)
  - Numeración personalizada

#### 💻 Items Digital (DigitalItemResource)
- **URL Base**: `/admin/digital-items`
- **Modelo**: `App\Models\DigitalItem`
- **Icono**: `heroicon-o-computer-desktop`
- **Permisos**: 
  - Verificación: Solo litografías
  - Roles permitidos: Según trait `CompanyTypeResource`
- **Páginas**:
  - List: `/admin/digital-items`
  - Create: `/admin/digital-items/create`
  - Edit: `/admin/digital-items/{record}/edit`
- **Características**:
  - Productos digitales sin costos de impresión

---

### 2.3 Grupo: Inventario

#### 📦 Productos (ProductResource)
- **URL Base**: `/admin/products`
- **Modelo**: `App\Models\Product`
- **Icono**: `heroicon-o-cube`
- **Permisos**: 
  - Verificación: `Policy` (parcial, falta `canViewAny()`)
  - Roles permitidos: Todos los autenticados
- **Páginas**:
  - List: `/admin/products`
  - Create: `/admin/products/create`
  - Edit: `/admin/products/{record}/edit`
- **Características**:
  - Litografías: Ven sus productos + productos de proveedores aprobados
  - Papelerías: Solo ven sus propios productos
  - Solo se pueden editar/eliminar productos propios
  - Gestión de stock

#### 📄 Papeles (PaperResource)
- **URL Base**: `/admin/papers`
- **Modelo**: `App\Models\Paper`
- **Icono**: `heroicon-o-document`
- **Permisos**: 
  - Verificación: `canViewAny()` ✅
  - Roles permitidos: Super Admin, Company Admin, Manager
- **Páginas**:
  - List: `/admin/papers`
  - Create: `/admin/papers/create`
  - Edit: `/admin/papers/{record}/edit`
- **Características**:
  - Litografías: Ven sus papeles + papeles de proveedores aprobados
  - Papelerías: Solo ven sus propios papeles
  - Solo se pueden editar/eliminar papeles propios
  - Gestión de stock

---

### 2.4 Grupo: Configuración

#### 🎨 Acabados (FinishingResource)
- **URL Base**: `/admin/finishings`
- **Modelo**: `App\Models\Finishing`
- **Icono**: `heroicon-o-rectangle-stack`
- **Permisos**: 
  - Verificación: `canViewAny()` ✅
  - Roles permitidos: Super Admin, Company Admin, Manager
- **Páginas**:
  - List: `/admin/finishings`
  - Create: `/admin/finishings/create`
  - Edit: `/admin/finishings/{record}/edit`
- **Características**:
  - Tipos: MILLAR, RANGO, UNIDAD, TAMAÑO
  - Rangos de precios (relación `FinishingRange`)
  - Cálculo dinámico según parámetros

#### 🖨️ Máquinas de Impresión (PrintingMachineResource)
- **URL Base**: `/admin/printing-machines`
- **Modelo**: `App\Models\PrintingMachine`
- **Icono**: `heroicon-o-printer`
- **Permisos**: 
  - Verificación: `canViewAny()` ✅
  - Roles permitidos: Super Admin, Company Admin, Manager
- **Páginas**:
  - List: `/admin/printing-machines`
  - Create: `/admin/printing-machines/create`
  - Edit: `/admin/printing-machines/{record}/edit`
- **Características**:
  - Dimensiones máximas (width, height)
  - Cálculo de montaje (MountingCalculatorService)

---

### 2.5 Grupo: Sistema

#### 👤 Usuarios (UserResource)
- **URL Base**: `/admin/users`
- **Modelo**: `App\Models\User`
- **Icono**: `heroicon-o-rectangle-stack`
- **Permisos**: 
  - Verificación: `Policy` + `canViewAny()` ✅
  - Roles permitidos: Super Admin, Company Admin
- **Páginas**:
  - List: `/admin/users`
  - Create: `/admin/users/create`
  - Edit: `/admin/users/{record}/edit`
- **Características**:
  - Oculto del menú principal (acceso vía dropdown avatar)
  - Multi-tenant estricto por `company_id`
  - Soft deletes habilitado

#### 🛡️ Roles (RoleResource)
- **URL Base**: `/admin/roles`
- **Modelo**: `Spatie\Permission\Models\Role`
- **Icono**: `heroicon-o-shield-check`
- **Permisos**: 
  - Verificación: `Policy` + `canViewAny()` ✅
  - Roles permitidos: Super Admin, Company Admin
- **Páginas**:
  - List: `/admin/roles`
  - Create: `/admin/roles/create`
  - Edit: `/admin/roles/{record}/edit`
- **Características**:
  - Oculto del menú principal
  - Company Admin NO puede ver/editar rol "Super Admin"
  - 56 permisos en 13 categorías (Sprint 14.3)

#### 💳 Planes (PlanResource)
- **URL Base**: `/admin/plans`
- **Modelo**: `App\Models\Plan`
- **Icono**: `heroicon-o-credit-card`
- **Permisos**: 
  - Verificación: Solo Super Admin
- **Páginas**:
  - List: `/admin/plans`
  - Create: `/admin/plans/create`
  - Edit: `/admin/plans/{record}/edit`
- **Características**:
  - Planes de suscripción (Free, Pro, Enterprise)
  - Integración con PayU

#### 📊 Suscripciones (SubscriptionResource)
- **URL Base**: `/admin/subscriptions`
- **Modelo**: `App\Models\Subscription`
- **Icono**: `heroicon-o-document-chart-bar`
- **Permisos**: 
  - Verificación: Solo Super Admin
- **Páginas**:
  - List: `/admin/subscriptions`
  - Create: `/admin/subscriptions/create`
  - Edit: `/admin/subscriptions/{record}/edit`
- **Características**:
  - Historial de suscripciones por empresa

---

## 3. Páginas Personalizadas

### 3.1 Dashboard Principal

#### 🏠 Dashboard (Dashboard)
- **URL**: `/admin` (slug vacío)
- **Vista**: `filament.pages.dashboard`
- **Icono**: `heroicon-o-squares-2x2`
- **Permisos**: Todos los autenticados
- **Widgets Activos**:
  1. `OnboardingWidget` - Bienvenida y setup inicial
  2. `PurchaseOrderNotificationsWidget` - Alertas de órdenes
  3. `PurchaseOrdersOverviewWidget` - Resumen de órdenes
  4. `DashboardStatsWidget` - Estadísticas generales
  5. `AdvancedStockAlertsWidget` - Alertas de stock avanzadas
  6. `ActiveDocumentsWidget` - Cotizaciones activas
  7. `StockAlertsWidget` - Alertas de stock
  8. `DeadlinesWidget` - Fechas límite
- **Características**:
  - Primera página que ve el usuario al autenticarse
  - Personalización según rol

### 3.2 Autenticación

#### 🔐 Login
- **URL**: `/admin/login`
- **Vista**: Filament built-in

#### 📝 Registro
- **URL**: `/admin/register`
- **Vista**: `app/Filament/Pages/Auth/Register.php`
- **Características**:
  - Rate limiting (10 intentos por minuto)
  - Creación automática de empresa

#### 🔑 Recuperar Contraseña
- **URL**: `/admin/password/reset`
- **Páginas**:
  - Request: `app/Filament/Pages/Auth/PasswordReset/RequestPasswordReset.php`
  - Reset: `app/Filament/Pages/Auth/PasswordReset/ResetPassword.php`

### 3.3 Gestión de Empresa

#### ⚙️ Configuración de Empresa (CompanySettings)
- **URL**: `/admin/company-settings`
- **Vista**: `filament.pages.company-settings`
- **Icono**: `heroicon-o-cog-6-tooth`
- **Permisos**: Todos los autenticados
- **Oculto del menú**: `shouldRegisterNavigation = false`
- **Características**:
  - Información básica (nombre, email, teléfono, dirección)
  - Perfil social (bio, avatar, banner)
  - Redes sociales (Facebook, Instagram, Twitter, LinkedIn)
  - Configuración de privacidad (perfil público, permitir seguidores)

#### 🏢 Perfil de Empresa (CompanyProfile)
- **URL**: `/admin/empresa/{slug}`
- **Vista**: `filament.pages.company-profile`
- **Permisos**: Acceso público según configuración
- **Características**:
  - Vista pública del perfil de empresa
  - Muestra posts de la red social
  - Sistema de seguidores
  - Información de contacto (si está habilitado)

#### 🏢 Directorio de Empresas (Companies)
- **URL**: `/admin/companies`
- **Vista**: `filament.pages.companies`
- **Permisos**: Todos los autenticados
- **Características**:
  - Directorio de empresas registradas
  - Búsqueda y filtrado
  - Envío de solicitudes de proveedor

### 3.4 Inventario

#### 📊 Gestión de Stock (StockManagement)
- **URL**: `/admin/stock-management`
- **Vista**: `filament.pages.stock-management`
- **Icono**: `heroicon-o-chart-bar-square`
- **Permisos**: Todos los autenticados
- **Grupo**: Inventario (Sort: 3)
- **Características**:
  - KPIs de stock (total items, low stock, out of stock, alertas críticas)
  - Gráfica de tendencias (últimos 30 días)
  - Predicciones de reorden (30 días)
  - Movimientos recientes
  - Alertas críticas
  - Notificaciones en tiempo real
  - Generación de reportes (JSON, CSV, HTML)
  - Polling cada 30 segundos

#### 📦 Movimientos de Stock (StockMovements)
- **URL**: `/admin/stock-movements`
- **Vista**: `filament.pages.stock-movements`
- **Permisos**: Todos los autenticados
- **Grupo**: Inventario (Sort: 4)
- **Características**:
  - Listado de movimientos de entrada/salida
  - Filtros por tipo, fecha, producto

### 3.5 Facturación

#### 💳 Facturación (Billing)
- **URL**: `/admin/billing`
- **Vista**: `filament.pages.billing`
- **Icono**: `heroicon-o-credit-card`
- **Permisos**: Todos los autenticados
- **Oculto del menú**: `shouldRegisterNavigation = false`
- **Características**:
  - Vista de plan actual
  - Cambio de plan (Free, Pro, Enterprise)
  - Integración con PayU
  - Historial de suscripciones
  - Cancelar suscripción

---

## 4. Widgets del Dashboard

### 4.1 Widgets de Onboarding

| Widget | Propósito |
|--------|-----------|
| **OnboardingWidget** | Guía de configuración inicial para nuevas empresas |

### 4.2 Widgets de Órdenes

| Widget | Propósito |
|--------|-----------|
| **PurchaseOrderNotificationsWidget** | Alertas de nuevas órdenes recibidas |
| **PurchaseOrdersOverviewWidget** | Resumen de órdenes abiertas, completadas, pendientes |
| **ReceivedOrdersWidget** | Órdenes recibidas como proveedor |
| **PendingOrdersStatsWidget** | Estadísticas de órdenes pendientes |
| **RecentOrdersWidget** | Últimas órdenes creadas |

### 4.3 Widgets de Stock

| Widget | Propósito |
|--------|-----------|
| **StockAlertsWidget** | Alertas básicas de stock bajo |
| **AdvancedStockAlertsWidget** | Alertas avanzadas con severidad |
| **StockKpisWidget** | KPIs de inventario |
| **StockMovementsKpisWidget** | KPIs de movimientos |
| **SimpleStockKpisWidget** | KPIs simplificados |
| **StockLevelTrackingWidget** | Seguimiento de niveles de stock |
| **StockTrendsChartWidget** | Gráfica de tendencias |
| **StockPredictionsWidget** | Predicciones de reorden |
| **RecentMovementsWidget** | Últimos movimientos de stock |
| **StockMovementsTableWidget** | Tabla de movimientos |

### 4.4 Widgets de Documentos

| Widget | Propósito |
|--------|-----------|
| **ActiveDocumentsWidget** | Cotizaciones activas/abiertas |
| **DeadlinesWidget** | Fechas límite de documentos |
| **DeliveryAlertsWidget** | Alertas de entregas próximas |

### 4.5 Widgets de Red Social

| Widget | Propósito |
|--------|-----------|
| **SocialFeedWidget** | Feed de posts de empresas seguidas |
| **CreatePostWidget** | Crear nuevo post (verificado con Policy Sprint 14.4) |
| **CompanyPostsWidget** | Posts de la empresa actual |
| **SocialPostWidget** | Widget individual de post |
| **SuggestedCompaniesWidget** | Sugerencias de empresas para seguir |

### 4.6 Widgets de Cálculo

| Widget | Propósito |
|--------|-----------|
| **PaperCalculatorWidget** | Calculadora de papeles |
| **CalculadoraCorteWidget** | Calculadora de cortes con SVG (validación de límites Sprint 13) |

### 4.7 Widgets de Negocio

| Widget | Propósito |
|--------|-----------|
| **DashboardStatsWidget** | Estadísticas generales del dashboard |
| **MrrWidget** | Monthly Recurring Revenue (Super Admin) |

---

## 5. Rutas Web Públicas y Protegidas

### 5.1 Rutas Públicas

```
GET  /                           → Welcome page
GET  /register                   → Redirige a Filament register
GET  /pricing                    → Página de precios públicos
```

### 5.2 Rutas de Autenticación (Guest)

```
GET  /admin/login                → Login
POST /admin/login                → Procesar login
GET  /admin/register             → Registro
POST /admin/register             → Procesar registro
GET  /admin/password/reset       → Solicitar reset
POST /admin/password/reset       → Procesar reset
```

### 5.3 Rutas Protegidas (Auth)

#### Perfil de Empresa

```
GET  /complete-profile           → Completar perfil empresa (primer login)
POST /complete-profile           → Guardar perfil empresa
GET  /complete-profile/skip      → Saltar perfil empresa
POST /complete-profile/states    → AJAX: Obtener estados por país
POST /complete-profile/cities    → AJAX: Obtener ciudades por estado
```

#### PDFs de Documentos

```
GET  /documents/{document}/pdf              → Ver PDF de cotización
GET  /documents/{document}/download         → Descargar PDF de cotización
GET  /collection-accounts/{id}/pdf          → Ver PDF de cuenta de cobro
GET  /collection-accounts/{id}/download     → Descargar PDF de cuenta de cobro
```

#### PDFs de Órdenes de Pedido

```
GET  /purchase-orders/{id}/pdf              → Ver PDF de orden
GET  /purchase-orders/{id}/download         → Descargar PDF de orden
POST /purchase-orders/{id}/email            → Enviar PDF por email
```

#### Flujos de Purchase Order

```
# FLUJO 1: Desde Purchase Order → Buscar Cotizaciones → Seleccionar Items
GET  /purchase-orders/search-documents      → Buscar cotizaciones
GET  /purchase-orders/document-items/{id}   → Items de una cotización
POST /purchase-orders/{id}/add-items        → Agregar items a orden

# FLUJO 2: Desde Document Item → Seleccionar Órdenes Abiertas
GET  /document-items/open-orders            → Órdenes abiertas
POST /document-items/{id}/add-to-orders     → Agregar item a órdenes
```

#### Impersonación (Super Admin)

```
POST /super-admin/impersonate/{user}        → Impersonar usuario
POST /super-admin/leave-impersonation       → Dejar impersonación
```

#### Suscripciones (PayU)

```
GET  /subscription/pricing                  → Ver planes
POST /subscription/subscribe/{plan}         → Suscribirse a plan
GET  /subscription/success                  → Página de éxito
GET  /subscription/manage                   → Gestionar suscripción
POST /subscription/change-plan/{plan}       → Cambiar de plan
POST /subscription/cancel                   → Cancelar suscripción
POST /subscription/resume                   → Reanudar suscripción
GET  /subscription/invoice/{invoice}        → Descargar factura
GET  /subscription/billing-portal           → Portal de facturación PayU
```

#### Debug (Solo Non-Production)

```
GET  /debug/tenant-context                  → Debug de contexto de tenant
```

---

## 6. API Endpoints

### 6.1 User Info

```
GET  /api/user                              → Información del usuario (Sanctum)
```

### 6.2 Sistema de Seguidores

```
POST /api/companies/{company}/follow        → Toggle seguir/dejar de seguir
GET  /api/companies/{company}/follow-status → Estado de seguimiento
GET  /api/companies/suggestions             → Sugerencias de empresas
```

### 6.3 Red Social

```
GET  /api/social/feed                       → Feed de posts
POST /api/social/posts                      → Crear post
POST /api/social/posts/{post}/like          → Toggle like en post
POST /api/social/posts/{post}/comments      → Agregar comentario
GET  /api/social/posts/{post}/comments      → Obtener comentarios
```

**Nota**: Todas las rutas de API requieren autenticación `auth:web`

---

## 7. Modelos y Relaciones

### 7.1 Modelos Core

#### User
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `hasMany(Document)` → documents (creados)
  - `hasMany(PurchaseOrder)` → purchaseOrders (creados)
  - `hasMany(ProductionOrder)` → productionOrders (asignados como operator)
  - `hasMany(SocialPost)` → posts
- **Traits**: `BelongsToTenant`, `HasRoles`, `Notifiable`, `SoftDeletes`, `Impersonate`
- **Permisos**: Spatie Permission (roles y permisos)

#### Company
- **Relaciones**:
  - `hasMany(User)` → users
  - `hasMany(Contact)` → contacts
  - `hasMany(Document)` → documents
  - `hasMany(Product)` → products
  - `hasMany(Paper)` → papers
  - `hasMany(PrintingMachine)` → printingMachines
  - `hasMany(Finishing)` → finishings
  - `hasMany(PurchaseOrder)` → purchaseOrders (creadas)
  - `hasMany(PurchaseOrder)` → supplierOrders (recibidas como proveedor)
  - `hasMany(SupplierRelationship)` → clientRelationships (como cliente)
  - `hasMany(SupplierRelationship)` → supplierRelationships (como proveedor)
  - `hasMany(SocialPost)` → posts
  - `belongsToMany(Company)` → followers
  - `belongsToMany(Company)` → following
  - `belongsTo(City)` → city
  - `belongsTo(State)` → state
  - `belongsTo(Country)` → country
  - `hasOne(CompanySettings)` → settings
- **Enums**: `CompanyType` (LITOGRAFIA, PAPELERIA)
- **Métodos**:
  - `isLitografia()`, `isPapeleria()`
  - `getCurrentPlan()` (fix Sprint 14.2)
  - `hasActiveSubscription()`

### 7.2 Modelos de Documentos

#### Document (Cotización)
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `belongsTo(User)` → createdBy
  - `belongsTo(Contact)` → contact
  - `hasMany(DocumentItem)` → items
  - `belongsTo(DocumentType)` → documentType
- **Traits**: `BelongsToTenant`, `SoftDeletes`

#### DocumentItem
- **Relaciones**:
  - `belongsTo(Document)` → document
  - `morphTo()` → itemable (SimpleItem, MagazineItem, TalonarioItem, DigitalItem)
  - `belongsTo(Paper)` → paper (opcional, para items simples)
  - `belongsToMany(Finishing)` → finishings (pivot con parámetros)
- **Características**:
  - Polimórfico: Un item puede ser de cualquier tipo
  - Aplicación de acabados en documentos

#### PurchaseOrder
- **Relaciones**:
  - `belongsTo(Company)` → company (quien crea la orden)
  - `belongsTo(Company)` → supplierCompany (quien la recibe)
  - `hasMany(PurchaseOrderItem)` → purchaseOrderItems
  - `belongsTo(User)` → createdBy
- **Características**:
  - Multi-paper support: Pivot `PurchaseOrderItem` como entity
  - Vista dual: Órdenes creadas vs recibidas

#### ProductionOrder
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `belongsTo(Company)` → supplier
  - `belongsTo(User)` → operator
  - `hasMany(DocumentItem)` → documentItems
- **Características**:
  - Asignación de operador de producción
  - Seguimiento de estado

#### CollectionAccount
- **Relaciones**:
  - `belongsTo(Company)` → company (quien crea la cuenta)
  - `belongsTo(Company)` → clientCompany (quien paga)
  - `belongsTo(User)` → createdBy
  - `hasMany(CollectionAccountItem)` → items
  - `hasMany(CollectionAccountStatusHistory)` → statusHistory
- **Características**:
  - Vista dual: Creadas vs recibidas
  - Historial de estados completo

### 7.3 Modelos de Items

#### SimpleItem
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `belongsTo(Paper)` → paper
  - `belongsTo(PrintingMachine)` → printingMachine
  - `belongsToMany(Finishing)` → finishings (pivot: `simple_item_finishing`)
  - `morphMany(DocumentItem)` → documentItems
- **Métodos de Cálculo**:
  - `calculateMountingWithCuts()` → Montaje + divisor (Sprint 13)
  - `calculateFinishingsCost()` → Costo de acabados (Sprint 14)
  - `calculateAll()` → Pricing completo con acabados
  - `addFinishing($finishing, $params, $isDefault)` → Agregar acabado
  - `getFinishingsBreakdown()` → Desglose detallado
- **Características**:
  - Sistema híbrido de acabados: Sugerencias en SimpleItem, aplicados en DocumentItem
  - Cálculo de montaje con divisor de cortes

#### MagazineItem
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `hasMany(MagazinePage)` → pages
  - `morphMany(DocumentItem)` → documentItems
- **Características**:
  - Páginas con papel diferente (caratula, interior, contracaratula)

#### TalonarioItem
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `hasMany(TalonarioSheet)` → sheets
  - `morphMany(DocumentItem)` → documentItems
- **Características**:
  - Hojas con numeración personalizada

#### DigitalItem
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `morphMany(DocumentItem)` → documentItems
- **Características**:
  - Sin costos de impresión

### 7.4 Modelos de Inventario

#### Product
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `hasMany(StockMovement)` → stockMovements (polimórfico)
  - `hasMany(StockAlert)` → stockAlerts (polimórfico)
- **Scopes**:
  - `lowStock()` → Stock bajo (< min_stock)
  - `outOfStock()` → Sin stock (= 0)
- **Traits**: `BelongsToTenant`, `SoftDeletes`

#### Paper
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `hasMany(SimpleItem)` → simpleItems
  - `hasMany(MagazinePage)` → magazinePages
  - `hasMany(StockMovement)` → stockMovements (polimórfico)
  - `hasMany(StockAlert)` → stockAlerts (polimórfico)
- **Scopes**:
  - `lowStock()` → Stock bajo
  - `outOfStock()` → Sin stock
- **Traits**: `BelongsToTenant`, `SoftDeletes`

#### StockMovement
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `belongsTo(User)` → user
  - `morphTo()` → stockable (Product, Paper)
- **Tipos**: `in` (entrada), `out` (salida)
- **Características**:
  - Auditoría completa de movimientos
  - Razones de movimiento

#### StockAlert
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `morphTo()` → stockable (Product, Paper)
- **Niveles**: `critical`, `warning`, `info`
- **Características**:
  - Alertas automáticas según `min_stock`
  - Estados: `active`, `resolved`, `dismissed`

### 7.5 Modelos de Configuración

#### PrintingMachine
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `hasMany(SimpleItem)` → simpleItems
- **Características**:
  - Dimensiones máximas (width, height)
  - Usado en cálculos de montaje

#### Finishing
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `hasMany(FinishingRange)` → ranges
  - `belongsToMany(SimpleItem)` → simpleItems (pivot: `simple_item_finishing`)
  - `belongsToMany(DocumentItem)` → documentItems (pivot: `document_item_finishing`)
- **Tipos de Medida**: `MILLAR`, `RANGO`, `UNIDAD`, `TAMAÑO`
- **Características**:
  - Precios por rangos
  - Parámetros dinámicos según tipo

#### FinishingRange
- **Relaciones**:
  - `belongsTo(Finishing)` → finishing
- **Características**:
  - Rango de cantidad (min-max)
  - Precio por rango

### 7.6 Modelos de Red Social

#### SocialPost
- **Relaciones**:
  - `belongsTo(Company)` → company
  - `belongsTo(User)` → author
  - `hasMany(SocialPostComment)` → comments
  - `hasMany(SocialPostReaction)` → reactions
- **Scopes**:
  - `forFeed($companyId)` → Posts del feed (empresa + seguidas)
  - `public()` → Posts públicos
- **Características**:
  - Visibilidad: `public`, `followers`, `company`
  - Notificaciones automáticas (tabla `social_notifications`)

#### SocialPostComment
- **Relaciones**:
  - `belongsTo(SocialPost)` → post
  - `belongsTo(User)` → user

#### SocialPostReaction
- **Relaciones**:
  - `belongsTo(SocialPost)` → post
  - `belongsTo(User)` → user
- **Tipos**: `like`, `love`, `celebrate`, etc.

#### CompanyFollower
- **Relaciones**:
  - `belongsTo(Company)` → follower (quien sigue)
  - `belongsTo(Company)` → following (quien es seguido)

### 7.7 Modelos de Notificaciones (Sistema Completo - Sprint 15)

#### Sistema 1: Notificaciones Sociales
- **SocialNotification**: Notificaciones de red social interna
  - Campos: company_id, user_id, post_id, type, content, visibility
  - Aislamiento multi-tenant automático

#### Sistema 2: Alertas de Inventario
- **StockAlert**: Alertas de stock crítico (27 campos)
- **StockMovement**: Movimientos de stock (21 campos)
- **StockNotificationService**: Servicio para alertas automáticas
  - Métodos: 8 métodos documentados (290 líneas)

#### Sistema 3: Sistema Avanzado de Notificaciones
- **NotificationChannel**: Canales configurables (34 campos)
  - Tipos: email, database, SMS, push, custom
- **NotificationRule**: Reglas de envío (49 campos)
  - Filtrado por rol, severidad, deduplicación
- **NotificationLog**: Auditoría completa (40 campos)
- **NotificationService**: Servicio principal
  - Métodos: 7 métodos documentados (219 líneas)

#### Sistema 4: Laravel Notifications (Base)
- **DatabaseNotification**: Sistema base de Laravel
- Uso: `$user->notify(new CustomNotification())`

**Documentación Completa**: Ver `NOTIFICATION_SYSTEM_SUMMARY.md`

### 7.8 Modelos de Proveedores

#### SupplierRelationship
- **Relaciones**:
  - `belongsTo(Company)` → supplierCompany (papelería)
  - `belongsTo(Company)` → clientCompany (litografía)
- **Características**:
  - Requiere aprobación (`approved_at`)
  - Estados: `pending`, `approved`, `rejected`
  - Campo `is_active` para activar/desactivar

#### SupplierRequest
- **Relaciones**:
  - `belongsTo(Company)` → clientCompany (quien solicita)
  - `belongsTo(Company)` → supplierCompany (quien recibe)
- **Características**:
  - Solicitudes de relación de proveedor
  - Estados: `pending`, `approved`, `rejected`

---

## 8. Sistema de Permisos y Roles

### 8.1 Arquitectura de Seguridad (3 Capas - Sprint 14.4)

```
┌─────────────────────────────────────────┐
│  Capa 1: Interfaz (Resource/Widget)    │
│  - canViewAny(), canCreate()           │
│  - canView() en widgets                 │
└───────────────┬─────────────────────────┘
                │ $user->can('create', Model)
┌───────────────▼─────────────────────────┐
│  Capa 2: Policy (Lógica de Negocio)    │
│  - viewAny(), create(), update()        │
│  - Verificación de company_id           │
└───────────────┬─────────────────────────┘
                │ $user->hasPermissionTo('perm')
┌───────────────▼─────────────────────────┐
│  Capa 3: Spatie Permission (Base BD)   │
│  - Tabla: role_has_permissions          │
│  - Tabla: model_has_permissions         │
└─────────────────────────────────────────┘
```

### 8.2 Roles del Sistema (8 roles)

| Rol | Descripción | Permisos |
|-----|-------------|----------|
| **Super Admin** | Administrador global del SaaS | Todos los permisos (incluye gestión de empresas) |
| **Company Admin** | Administrador de empresa | Todos excepto gestión de empresas |
| **Manager** | Gerente de operaciones | Gestión de documentos, inventario, producción |
| **Salesperson** | Vendedor | Contactos, cotizaciones, órdenes (sin acceso a configuración) |
| **Operator** | Operador de producción | Solo órdenes de producción asignadas |
| **Employee** | Empleado general | Permisos básicos |
| **Customer** | Cliente externo | Solo visualización de sus documentos |
| **Client** | Cliente (alias de Customer) | Solo visualización de sus documentos |

### 8.3 Permisos por Categoría (56 permisos - Sprint 14.3)

#### Gestión de Usuarios (4)
- `view-users`, `create-users`, `edit-users`, `delete-users`

#### Gestión de Contactos (4)
- `view-contacts`, `create-contacts`, `edit-contacts`, `delete-contacts`

#### Cotizaciones (6)
- `view-quotations`, `create-quotations`, `edit-quotations`, `delete-quotations`
- `approve-quotations`, `send-quotations`

#### Documentos (5)
- `view-documents`, `create-documents`, `edit-documents`, `delete-documents`
- `print-documents`

#### Órdenes de Producción (5)
- `view-production-orders`, `create-production-orders`, `edit-production-orders`
- `delete-production-orders`, `assign-production-orders`

#### Órdenes de Papel (4)
- `view-paper-orders`, `create-paper-orders`, `edit-paper-orders`
- `delete-paper-orders`

#### Productos (4)
- `view-products`, `create-products`, `edit-products`, `delete-products`

#### Equipos (4)
- `view-equipment`, `create-equipment`, `edit-equipment`, `delete-equipment`

#### Empresas (4) - Solo Super Admin
- `view-companies`, `create-companies`, `edit-companies`, `delete-companies`

#### Inventario (3)
- `manage-inventory`, `manage-paper-catalog`, `manage-printing-machines`

#### Sistema (6)
- `access-admin-panel`, `manage-roles`, `manage-permissions`
- `view-logs`, `impersonate-users`, `manage-settings`

#### Reportes (2)
- `view-reports`, `export-reports`

#### Red Social (5)
- `view-posts`, `create-posts`, `edit-posts`, `delete-posts`, `moderate-posts`

### 8.4 Estado de Verificación por Recurso (Sprint 14.1-14.4)

| Recurso | canViewAny() | Policy | Estado |
|---------|--------------|--------|--------|
| Users | ✅ | ✅ | Completo (3 capas) |
| Roles | ✅ | ✅ | Completo (3 capas) |
| Papers | ✅ | ❌ | Parcial |
| PrintingMachines | ✅ | ❌ | Parcial |
| Finishings | ✅ | ❌ | Parcial |
| CollectionAccounts | ✅ | ❌ | Parcial |
| Posts (Widget) | ✅ | ✅ | Completo (3 capas, Sprint 14.4) |
| Documents | ❌ | ✅ | Parcial |
| Contacts | ❌ | ✅ | Parcial |
| Products | ❌ | ✅ | Parcial |
| SimpleItems | ❌ | ✅ | Parcial |
| PurchaseOrders | ❌ | ✅ | Parcial |
| ProductionOrders | ❌ | ❌ | Sin verificación |

**Tarea Pendiente**: Agregar `canViewAny()` a recursos con verificación parcial.

### 8.5 Ejemplos de Uso

#### Verificar Permiso en Código

```php
// Método 1: Spatie Permission (Base)
if ($user->hasPermissionTo('create-posts')) {
    // Permitir acción
}

// Método 2: Policy (Recomendado)
if ($user->can('create', SocialPost::class)) {
    // Permitir acción
}

// Método 3: En Filament Resource
public static function canViewAny(): bool {
    return auth()->user()->can('viewAny', Model::class);
}

// Método 4: En Widget (Sprint 14.4)
public function canView(): bool {
    return auth()->user()->can('create', SocialPost::class);
}
```

---

## 9. Flujos de la Aplicación

### 9.1 Flujo de Registro y Onboarding

```
1. Usuario visita /register
2. Completa formulario (nombre, email, contraseña, empresa)
3. Sistema crea:
   - Empresa (Company)
   - Usuario (User) con rol "Company Admin"
   - Configuraciones (CompanySettings)
4. Redirige a /complete-profile
5. Usuario completa perfil de empresa (opcional):
   - Información geográfica (país, estado, ciudad)
   - Tipo de empresa (Litografía/Papelería)
   - Logo, descripción
6. Redirige a Dashboard
7. Muestra OnboardingWidget con pasos iniciales
```

### 9.2 Flujo de Cotización (Document)

```
1. Usuario crea Document desde /admin/documents/create
2. Selecciona contacto (cliente)
3. Agrega items:
   - Opción A: Crear SimpleItem inline
   - Opción B: Seleccionar SimpleItem existente
   - Opción C: Crear MagazineItem, TalonarioItem, DigitalItem
4. Sistema calcula pricing automáticamente:
   - Montaje (MountingCalculatorService)
   - Divisor de cortes (CuttingCalculatorService) - Sprint 13
   - Acabados (FinishingCalculatorService) - Sprint 14
   - Impresión (PrintingCalculatorService)
5. Usuario puede:
   - Ver PDF de cotización
   - Enviar por email
   - Convertir a Purchase Order
   - Convertir a Production Order
```

### 9.3 Flujo de Purchase Order

#### FLUJO 1: Desde Purchase Order → Buscar Cotizaciones

```
1. Usuario crea PurchaseOrder desde /admin/purchase-orders/create
2. Selecciona proveedor (supplierCompany)
3. Hace clic en "Buscar Cotizaciones"
4. Sistema muestra modal con cotizaciones disponibles
5. Usuario selecciona cotización
6. Sistema muestra items de la cotización
7. Usuario selecciona items a agregar
8. Sistema crea PurchaseOrderItem por cada item:
   - Copia información del DocumentItem
   - Si es SimpleItem con papel, crea row para papel
   - Si es MagazineItem, crea row por cada página (multi-paper support)
9. Genera PDF de orden
10. Envía email al proveedor (opcional)
```

#### FLUJO 2: Desde Document Item → Agregar a Órdenes

```
1. Usuario está viendo Document
2. En RelationManager de items, selecciona item
3. Hace clic en "Agregar a Orden de Pedido"
4. Sistema muestra órdenes abiertas del proveedor
5. Usuario selecciona una o más órdenes
6. Sistema agrega item a las órdenes seleccionadas
```

### 9.4 Flujo de Production Order

```
1. Usuario crea ProductionOrder desde Document o Purchase Order
2. Selecciona:
   - Proveedor (si aplica)
   - Operador (User con rol Operator)
   - Items a producir (DocumentItem)
3. Asigna fechas:
   - Fecha de inicio
   - Fecha de entrega estimada
4. Operador recibe notificación
5. Operador actualiza estado:
   - pending → in_progress → completed
6. Sistema actualiza automáticamente:
   - Stock de materiales (si está habilitado)
   - Estado del Document original
```

### 9.5 Flujo de Collection Account (Cuenta de Cobro)

```
1. Usuario crea CollectionAccount
2. Selecciona:
   - Cliente (clientCompany)
   - Items/servicios a cobrar
3. Sistema calcula total
4. Genera PDF
5. Envía a cliente
6. Cliente paga y actualiza estado:
   - pending → paid
7. Sistema registra en historial de estados (CollectionAccountStatusHistory)
```

### 9.6 Flujo de Gestión de Stock

```
1. Administrador accede a /admin/stock-management
2. Dashboard muestra:
   - KPIs (total items, low stock, out of stock, alertas)
   - Gráfica de tendencias (30 días)
   - Predicciones de reorden (StockPredictionService)
   - Movimientos recientes
   - Alertas críticas
3. Sistema evalúa automáticamente alertas:
   - Si stock < min_stock → Crea StockAlert (warning)
   - Si stock = 0 → Crea StockAlert (critical)
   - Envía notificación (email, database, SMS según configuración)
4. Usuario puede:
   - Registrar entrada de stock (StockMovement tipo "in")
   - Registrar salida de stock (StockMovement tipo "out")
   - Generar reportes (JSON, CSV, HTML)
   - Ver predicciones de cuándo se agotará stock
```

### 9.7 Flujo de Relación Proveedor (Supplier Relationship)

```
# Para Litografía (Cliente):
1. Litografía accede a /admin/companies
2. Busca papelería
3. Envía SupplierRequest
4. Papelería recibe notificación

# Para Papelería (Proveedor):
5. Papelería accede a /admin/supplier-relationships
6. Ve solicitud pendiente
7. Aprueba o rechaza solicitud
8. Si aprueba:
   - Se crea SupplierRelationship (is_active=true)
   - Litografía puede ver productos/papeles de papelería
   - Litografía puede crear PurchaseOrder a papelería

# Gestión de Relación:
9. Litografía puede desactivar relación (is_active=false)
10. Papelería puede desactivar relación
11. Si se desactiva:
    - Litografía deja de ver productos/papeles de papelería
    - Órdenes existentes siguen activas
```

### 9.8 Flujo de Red Social (Sistema de Posts)

```
1. Usuario crea SocialPost desde CreatePostWidget (verificado con Policy - Sprint 14.4)
2. Selecciona visibilidad:
   - public: Todos pueden ver
   - followers: Solo seguidores
   - company: Solo empresa
3. Sistema crea notificaciones (SocialNotification) para:
   - Seguidores (si visibility=followers)
   - Empresa (si visibility=company)
4. Post aparece en:
   - SocialFeedWidget (empresas seguidas)
   - CompanyPostsWidget (perfil de empresa)
5. Usuarios pueden:
   - Like/Reaccionar (SocialPostReaction)
   - Comentar (SocialPostComment)
   - Compartir (futuro)
6. Sistema genera notificaciones en tiempo real
```

### 9.9 Flujo de Seguimiento de Empresas

```
1. Usuario accede a /admin/companies
2. Ve listado de empresas registradas
3. Hace clic en "Seguir" en perfil de empresa
4. Sistema crea CompanyFollower
5. Sistema actualiza contadores:
   - followers_count de empresa seguida
   - following_count de empresa que sigue
6. Usuario empieza a ver posts de empresa seguida en feed
7. Usuario puede dejar de seguir en cualquier momento
```

### 9.10 Flujo de Suscripción (PayU)

```
1. Usuario accede a /admin/billing
2. Ve plan actual (free por defecto)
3. Selecciona nuevo plan (Pro, Enterprise)
4. Sistema genera signature PayU
5. Redirige a página de pago PayU con parámetros:
   - merchantId, accountId
   - referenceCode (único)
   - amount, currency (COP)
   - buyerEmail, buyerFullName
   - responseUrl, confirmationUrl
6. Usuario completa pago en PayU
7. PayU redirige a responseUrl con resultado
8. PayU envía confirmación a confirmationUrl (webhook)
9. Sistema actualiza:
   - Company.subscription_plan
   - Company.subscription_expires_at
   - Company.is_active = true
10. Usuario puede usar funcionalidades del plan
```

---

## Anexo A: Servicios y Calculadoras

### Servicios de Cálculo

| Servicio | Propósito | Métodos Principales |
|----------|-----------|---------------------|
| **SimpleItemCalculatorService** | Cálculo de pricing completo | `calculateMountingWithCuts()`, `calculateFinalPricingNew()` |
| **MountingCalculatorService** | Cálculo de montaje puro | `calculateMounting()`, `calculateRequiredSheets()` |
| **CuttingCalculatorService** | Cálculo de divisor de cortes | `calculateCutting()`, `calculateOptimalLayout()` |
| **FinishingCalculatorService** | Cálculo de acabados | `calculateCost($finishing, $params)` |
| **PrintingCalculatorService** | Cálculo de impresión | `calculateMillares()`, `calculateInkCost()` |

### Servicios de Stock

| Servicio | Propósito | Métodos Principales |
|----------|-----------|---------------------|
| **StockAlertService** | Gestión de alertas | `evaluateAllAlerts()`, `getAlertsSummary()` |
| **StockPredictionService** | Predicción de stock | `getReorderAlerts()`, `predictDepletionDate()` |
| **StockReportService** | Generación de reportes | `generateInventoryReport()`, `exportReport()` |
| **StockNotificationService** | Notificaciones de stock | `sendStockAlert()`, `sendDepletionPrediction()` |

### Servicios de Notificaciones (Sprint 15)

| Servicio | Propósito | Métodos Principales |
|----------|-----------|---------------------|
| **NotificationService** | Servicio principal (219 líneas) | `send()`, `broadcast()`, `queue()` |
| **StockNotificationService** | Alertas de inventario (290 líneas) | `checkLowStock()`, `sendAlert()` |

**Documentación Completa**: Ver `NOTIFICATION_SYSTEM_ANALYSIS.md`

### Servicios de Pago

| Servicio | Propósito |
|----------|-----------|
| **PayUService** | Integración con PayU (Colombia) |

---

## Anexo B: Traits y Concerns

### Traits de Modelos

| Trait | Propósito |
|-------|-----------|
| **BelongsToTenant** | Scope global por `company_id` (multi-tenant) |
| **CompanyTypeResource** | Restricción a solo litografías |
| **SoftDeletes** | Borrado lógico (Laravel) |
| **HasRoles** | Sistema de roles (Spatie Permission) |
| **Notifiable** | Envío de notificaciones (Laravel) |
| **Impersonate** | Impersonación de usuarios (Super Admin) |

---

## Anexo C: Tablas de Base de Datos (Multi-Tenant)

### Tablas Multi-Tenant (con `company_id`)

```
users, contacts, documents, document_items
purchase_orders, purchase_order_items
production_orders
collection_accounts
simple_items, magazine_items, talonario_items, digital_items
products, papers
printing_machines, finishings
stock_movements, stock_alerts
social_posts, social_post_comments, social_post_reactions
supplier_relationships, supplier_requests
```

### Tablas Globales (sin `company_id`)

```
companies, plans, subscriptions
countries, states, cities
document_types
roles, permissions, model_has_roles, model_has_permissions, role_has_permissions
```

### Tablas de Notificaciones (Sprint 15)

```
social_notifications (11 campos)
stock_alerts (27 campos)
stock_movements (21 campos)
notification_channels (34 campos)
notification_rules (49 campos)
notification_logs (40 campos)
notifications (Laravel)
```

**Total**: 7 tablas de notificaciones documentadas con DDL completo en `NOTIFICATION_SYSTEM_ANALYSIS.md`

---

## Anexo D: Enums Principales

```php
// app/Enums/NavigationGroup.php
enum NavigationGroup: string {
    case Documentos = 'documentos';
    case Items = 'items';
    case Inventario = 'inventario';
    case Configuracion = 'configuracion';
    case Sistema = 'sistema';
}

// app/Enums/CompanyType.php
enum CompanyType: string {
    case LITOGRAFIA = 'litografia';
    case PAPELERIA = 'papeleria';
}
```

---

## Conclusión

Este sitemap documenta la arquitectura completa de LitoPro 3.0, incluyendo:

- **19 Recursos CRUD** completos
- **11 Páginas personalizadas**
- **29 Widgets** de dashboard
- **40+ Rutas web** (públicas y protegidas)
- **9 Endpoints API**
- **67 Modelos** con relaciones
- **8 Roles y 56 Permisos** en 13 categorías
- **10 Flujos principales** de negocio
- **4 Sistemas de notificaciones** (Sprint 15)

**Estado del Proyecto**: Sprint 15 completado (Sistema de Notificaciones Documentado).

**Próxima Tarea Prioritaria**: Completar verificación de permisos (`canViewAny()`) en recursos con verificación parcial.

---

**Generado**: 2025-11-07  
**Autor**: Claude Code (Assistant)  
**Versión LitoPro**: 3.0
