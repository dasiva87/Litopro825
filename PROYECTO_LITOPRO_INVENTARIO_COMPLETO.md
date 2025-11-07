# LitoPro 3.0 - Inventario Completo del Proyecto

**Generado:** 2025-11-07  
**Versión del Sistema:** Laravel 12.25.0 + Filament 4.0.3 + PHP 8.3.21

---

## 📊 ESTADÍSTICAS GENERALES

| Categoría | Cantidad |
|-----------|----------|
| **Modelos (Models)** | 62 |
| **Recursos Filament (Resources)** | 19 |
| **Servicios de Negocio (Services)** | 19 |
| **Widgets de Dashboard** | 29 |
| **Páginas Filament Personalizadas** | 11 |
| **Políticas de Autorización (Policies)** | 10 |
| **Migraciones de BD** | 125 |
| **Líneas Totales de Código (Models)** | ~10,776 |

---

## 📦 MODELOS DE BASE DE DATOS (62)

### 1. Modelos Core del Sistema

#### **Company** (Empresa Multi-Tenant)
- **Archivo:** `app/Models/Company.php`
- **Traits:** `HasFactory`, `SoftDeletes`
- **Campos Principales:**
  - `name`, `slug`, `email`, `phone`, `address`
  - `city_id`, `state_id`, `country_id`
  - `tax_id`, `logo`, `website`, `bio`
  - `subscription_plan`, `subscription_expires_at`
  - `max_users`, `is_active`, `status`
  - `company_type` (Litografía/Papelería)
- **Relaciones:**
  - `HasMany`: users, contacts, papers, printingMachines, products, documents, invoices, usageMetrics, activityLogs
  - `HasOne`: settings
  - `BelongsTo`: country, state, city
  - `HasMany (followers)`: companyFollowers (as followed)
  - `HasMany (following)`: companyFollowers (as follower)
  - `HasMany`: supplierRequests, receivedSupplierRequests, supplierRelationships, clientRelationships
- **Scopes:**
  - `active()`, `byPlan()`, `byStatus()`, `suspended()`, `cancelled()`, `onTrial()`, `pending()`
  - `litografias()`, `papelerias()`, `byType()`
- **Métodos Clave:**
  - `getCurrentPlan()`: Obtiene el plan actual de suscripción
  - `hasActiveSubscription()`: Verifica si tiene suscripción activa
  - `suspend()`, `reactivate()`, `cancel()`: Gestión de estado
  - `follow()`, `unfollow()`, `isFollowing()`: Red social empresas

#### **User** (Usuario del Sistema)
- **Archivo:** `app/Models/User.php`
- **Traits:** `BelongsToTenant`, `Billable`, `HasApiTokens`, `HasFactory`, `HasRoles`, `Impersonate`, `Notifiable`, `SoftDeletes`
- **Campos Principales:**
  - `company_id`, `name`, `email`, `password`
  - `document_type`, `document_number`, `phone`, `mobile`
  - `position`, `address`, `city_id`, `state_id`, `country_id`
  - `avatar`, `is_active`, `last_login_at`, `preferences`
- **Relaciones:**
  - `BelongsTo`: company (multi-tenant), country, state, city
  - `morphMany`: notifications (DatabaseNotification personalizado)
- **Roles (Spatie Permission):**
  - Super Admin, Company Admin, Manager, Salesperson, Operator, Customer, Employee, Client
- **Métodos Clave:**
  - `isAdmin()`: Verifica si es Super Admin o Company Admin
  - `canImpersonate()`: Permite impersonar otros usuarios
  - `canBeImpersonated()`: Permite ser impersonado

---

### 2. Modelos de Documentos y Cotizaciones

#### **Document** (Cotización/Orden/Factura)
- **Archivo:** `app/Models/Document.php`
- **Traits:** `BelongsToTenant`, `HasFactory`, `SoftDeletes`
- **Campos Principales:**
  - `company_id`, `user_id`, `contact_id`, `document_type_id`
  - `document_number`, `reference`, `date`, `due_date`
  - `status`, `subtotal`, `discount_amount`, `discount_percentage`
  - `tax_amount`, `tax_percentage`, `total`
  - `notes`, `internal_notes`, `valid_until`
  - `version`, `parent_document_id`
- **Relaciones:**
  - `BelongsTo`: company, user, contact, documentType, parentDocument
  - `HasMany`: items (DocumentItem), childVersions, purchaseOrders
- **Estados Posibles:**
  - `draft`, `sent`, `approved`, `rejected`, `in_production`, `completed`, `cancelled`
- **Scopes:**
  - `byStatus()`, `byType()`, `quotes()`, `orders()`, `invoices()`, `active()`, `expiringSoon()`
- **Métodos Clave:**
  - `calculateTotals()`: Calcula subtotal, descuento, impuestos y total
  - `generateDocumentNumber()`: Genera número único (COT-2025-001)
  - `markAsSent()`, `markAsApproved()`, `markAsRejected()`: Transiciones de estado
  - `createNewVersion()`: Crea nueva versión del documento
  - `hasAvailableItemsForOrder()`: Verifica si tiene items disponibles para órdenes

#### **DocumentItem** (Item Polimórfico de Documento)
- **Archivo:** `app/Models/DocumentItem.php`
- **Traits:** `HasFactory`, `SoftDeletes`, `BelongsToTenant`
- **Campos Principales:**
  - `document_id`, `company_id`
  - `itemable_type`, `itemable_id` (Relación polimórfica)
  - `printing_machine_id`, `paper_id`
  - `description`, `quantity`
  - `width`, `height`, `pages`, `colors_front`, `colors_back`
  - `paper_cut_width`, `paper_cut_height`, `orientation`
  - `cuts_per_sheet`, `sheets_needed`, `unit_copies`
  - `paper_cost`, `printing_cost`, `cutting_cost`, `design_cost`, `transport_cost`, `other_costs`
  - `unit_price`, `total_price`, `profit_margin`
  - `item_type`, `item_config`, `is_template`, `template_name`
  - `order_status` (available, in_cart, ordered, received)
- **Relaciones:**
  - `BelongsTo`: document, printingMachine, paper
  - `MorphTo`: itemable (SimpleItem, Product, DigitalItem, TalonarioItem, MagazineItem, CustomItem)
  - `HasMany`: finishings (DocumentItemFinishing)
  - `BelongsToMany`: purchaseOrders, collectionAccounts, productionOrders (con pivots)
- **Tipos de Item (itemable_type):**
  - `App\Models\SimpleItem`: Trabajos de impresión sencillos
  - `App\Models\Product`: Productos del catálogo
  - `App\Models\DigitalItem`: Servicios de impresión digital
  - `App\Models\TalonarioItem`: Talonarios personalizados
  - `App\Models\MagazineItem`: Revistas con múltiples páginas
  - `App\Models\CustomItem`: Items personalizados sin cálculo automático
- **Métodos Clave:**
  - `calculateTotals()`: Calcula precios según tipo de item
  - `calculateCuttingOptimization()`: Optimiza cortes en papel
  - `updateOrderStatus()`: Actualiza estado según órdenes de compra
  - `generateDescription()`: Genera descripción automática
  - `saveAsTemplate()`: Guarda como plantilla reutilizable

#### **DocumentType** (Tipo de Documento)
- **Archivo:** `app/Models/DocumentType.php`
- **Constantes:**
  - `QUOTE = 'quote'`
  - `ORDER = 'order'`
  - `INVOICE = 'invoice'`
  - `PAPER = 'paper'`
  - `PURCHASE = 'purchase'`
  - `DELIVERY = 'delivery'`

---

### 3. Modelos de Items Específicos (Polimórficos)

#### **SimpleItem** (Item de Impresión Sencillo)
- **Archivo:** `app/Models/SimpleItem.php`
- **Traits:** `BelongsToTenant`, `HasFactory`, `SoftDeletes`
- **Campos Principales:**
  - `company_id`, `description`, `base_description`, `quantity`
  - `sobrante_papel`, `horizontal_size`, `vertical_size`
  - `mounting_quantity`, `custom_paper_width`, `custom_paper_height`
  - `mounting_type` (automatic/custom), `custom_mounting_data`
  - `paper_cuts_h`, `paper_cuts_v`
  - `ink_front_count`, `ink_back_count`, `front_back_plate`
  - `design_value`, `transport_value`, `rifle_value`, `cutting_cost`, `mounting_cost`
  - `profit_percentage`
  - `paper_id`, `printing_machine_id`
  - `paper_cost`, `printing_cost`, `total_cost`, `final_price`
- **Relaciones:**
  - `MorphMany`: documentItems
  - `BelongsTo`: paper, printingMachine
  - `BelongsToMany`: finishings (simple_item_finishing pivot)
- **Métodos Clave:**
  - `calculateAll()`: Calcula costos completos (usa SimpleItemCalculatorService)
  - `getMountingWithCuts()`: Obtiene cálculo de montaje con divisor de cortes
  - `getPureMounting()`: Obtiene montaje puro (cuántas copias por pliego)
  - `getBestMounting()`: Obtiene mejor opción de montaje
  - `addFinishing()`: Agrega acabado con cálculo automático
  - `calculateFinishingsCost()`: Suma costos de todos los acabados
  - `generateAutoDescription()`: Genera descripción concatenada automática

#### **Product** (Producto del Catálogo)
- **Archivo:** `app/Models/Product.php`
- **Traits:** `BelongsToTenant`, `HasFactory`, `SoftDeletes`
- **Campos Principales:**
  - `company_id`, `name`, `description`, `sku`
  - `category`, `unit`, `cost_price`, `sale_price`
  - `stock`, `min_stock`, `is_active`
- **Relaciones:**
  - `BelongsTo`: company, supplier
  - `MorphMany`: documentItems
- **Métodos:**
  - `calculateTotalPrice($quantity)`: Calcula precio total por cantidad

#### **DigitalItem** (Servicio de Impresión Digital)
- **Archivo:** `app/Models/DigitalItem.php`
- **Campos Principales:**
  - `company_id`, `description`, `pricing_type` (fixed/size/unit)
  - `unit_value`, `width`, `height`, `material`, `finish`
- **Relaciones:**
  - `BelongsTo`: company, supplier
  - `MorphMany`: documentItems
  - `BelongsToMany`: finishings
- **Métodos:**
  - `calculateTotalPrice($params)`: Calcula precio según tipo de medición
  - `calculateFinishingsCost()`: Suma acabados aplicados

#### **TalonarioItem** (Talonario Numerado)
- **Archivo:** `app/Models/TalonarioItem.php`
- **Campos Principales:**
  - `company_id`, `description`, `quantity`
  - `numeracion_inicial`, `numeracion_final`, `copias_por_talonario`
  - `horizontal_size`, `vertical_size`, `papel_carbon`
- **Relaciones:**
  - `MorphMany`: documentItems
  - `HasMany`: sheets (TalonarioSheet)
  - `BelongsToMany`: finishings

#### **MagazineItem** (Revista con Múltiples Páginas)
- **Archivo:** `app/Models/MagazineItem.php`
- **Campos Principales:**
  - `company_id`, `description`, `quantity`, `total_pages`
  - `tipo_encuadernacion`, `cubierta_diferente`
  - `papel_interior_id`, `papel_cubierta_id`
- **Relaciones:**
  - `MorphMany`: documentItems
  - `HasMany`: pages (MagazinePage)
  - `BelongsToMany`: finishings

#### **CustomItem** (Item Personalizado sin Cálculo Automático)
- **Archivo:** `app/Models/CustomItem.php`
- **Campos Principales:**
  - `company_id`, `description`, `quantity`
  - `unit_price`, `total_price`, `notes`
- **Relaciones:**
  - `MorphMany`: documentItems

---

### 4. Modelos de Catálogo e Inventario

#### **Paper** (Papel)
- **Archivo:** `app/Models/Paper.php`
- **Traits:** `BelongsToTenant`, `HasFactory`, `SoftDeletes`, `StockManagement`
- **Campos Principales:**
  - `company_id`, `name`, `type`, `weight`, `width`, `height`
  - `cost_per_sheet`, `stock`, `min_stock`, `max_stock`
  - `is_active`, `supplier_id`
- **Relaciones:**
  - `BelongsTo`: company, supplier
  - `HasMany`: documentItems
  - `MorphMany`: stockMovements (desde StockManagement trait)
- **Métodos (StockManagement trait):**
  - `addStock($quantity, $reason)`: Agrega stock con registro
  - `removeStock($quantity, $reason)`: Remueve stock con registro
  - `isLowStock()`: Verifica si está bajo stock mínimo
  - `isCriticalStock()`: Verifica si está en nivel crítico

#### **PrintingMachine** (Máquina de Impresión)
- **Archivo:** `app/Models/PrintingMachine.php`
- **Traits:** `BelongsToTenant`, `HasFactory`, `SoftDeletes`
- **Campos Principales:**
  - `company_id`, `name`, `model`, `brand`
  - `max_width`, `max_height`, `max_colors`
  - `cost_per_impression`, `setup_cost`, `costo_ctp`
  - `is_active`, `supplier_id`
- **Relaciones:**
  - `BelongsTo`: company, supplier
  - `HasMany`: documentItems
- **Métodos:**
  - `calculateCostForQuantity($impressions)`: Calcula costo por millar

#### **Finishing** (Acabado)
- **Archivo:** `app/Models/Finishing.php`
- **Enums:**
  - `FinishingMeasurementUnit`: MILLAR, RANGO, TAMAÑO, UNIDAD, FIJO, CUSTOM
  - `FinishingType`: LAMINADO, BARNIZ, CORTE, DOBLEZ, ENCUADERNACION, etc.
- **Campos Principales:**
  - `name`, `measurement_unit`, `finishing_type`
  - `fixed_cost`, `cost_per_unit`
  - `is_active`, `supplier_id`
- **Relaciones:**
  - `BelongsTo`: supplier
  - `HasMany`: ranges (FinishingRange para precios por rango)
  - `BelongsToMany`: digitalItems, simpleItems
- **Métodos:**
  - Cálculo de costo manejado por `FinishingCalculatorService`

#### **Contact** (Cliente/Proveedor)
- **Archivo:** `app/Models/Contact.php`
- **Traits:** `BelongsToTenant`, `HasFactory`, `SoftDeletes`
- **Campos Principales:**
  - `company_id`, `type` (customer/supplier/both)
  - `name`, `email`, `phone`, `mobile`, `tax_id`
  - `address`, `city_id`, `state_id`, `country_id`
  - `is_active`, `notes`
- **Relaciones:**
  - `BelongsTo`: company, country, state, city
  - `HasMany`: documents

---

### 5. Modelos de Órdenes y Producción

#### **PurchaseOrder** (Orden de Compra a Proveedor)
- **Archivo:** `app/Models/PurchaseOrder.php`
- **Traits:** `BelongsToTenant`, `HasFactory`, `SoftDeletes`
- **Campos Principales:**
  - `company_id`, `order_number`, `supplier_company_id`
  - `status` (draft, sent, confirmed, in_production, completed, cancelled)
  - `order_date`, `expected_delivery_date`, `actual_delivery_date`
  - `subtotal`, `tax_amount`, `total`, `notes`
  - `created_by`, `approved_by`, `approved_at`
- **Relaciones:**
  - `BelongsTo`: company, supplierCompany, createdBy, approvedBy
  - `BelongsToMany`: documentItems (con pivot document_item_purchase_order)
  - `HasMany`: statusHistories, purchaseOrderItems
- **Arquitectura Multi-Paper:**
  - Usa tabla pivot `purchase_order_items` para permitir múltiples rows por DocumentItem
  - Soporta revistas con varios tipos de papel en una sola orden
- **Métodos:**
  - `generateOrderNumber()`: PO-2025-001
  - `calculateTotals()`: Suma items de la orden
  - `markAsConfirmed()`, `markAsCompleted()`: Transiciones de estado

#### **PurchaseOrderItem** (Item de Orden de Compra - Pivot como Entity)
- **Archivo:** `app/Models/PurchaseOrderItem.php`
- **Campos Principales:**
  - `purchase_order_id`, `document_item_id`, `paper_id`
  - `quantity_ordered`, `unit_price`, `total_price`
  - `status`, `notes`, `paper_description`
- **Relaciones:**
  - `BelongsTo`: purchaseOrder, documentItem, paper
- **Métodos:**
  - `getPaperNameAttribute()`: Obtiene nombre del papel con carga dinámica

#### **ProductionOrder** (Orden de Producción Interna)
- **Archivo:** `app/Models/ProductionOrder.php`
- **Traits:** `BelongsToTenant`, `HasFactory`, `SoftDeletes`
- **Campos Principales:**
  - `company_id`, `order_number`, `supplier_id`
  - `status` (pending, in_progress, paused, completed, cancelled)
  - `priority` (low, normal, high, urgent)
  - `expected_start_date`, `actual_start_date`, `expected_completion_date`, `actual_completion_date`
  - `operator_id`, `quality_checked_by`, `quality_status`
  - `total_impressions`, `total_sheets`, `notes`
- **Relaciones:**
  - `BelongsTo`: company, supplier, operator, qualityCheckedBy
  - `BelongsToMany`: documentItems (con pivot document_item_production_order)
- **Métodos:**
  - `generateOrderNumber()`: PRO-2025-001
  - `calculateTotals()`: Suma impresiones y pliegos

#### **CollectionAccount** (Cuenta de Cobro)
- **Archivo:** `app/Models/CollectionAccount.php`
- **Traits:** `BelongsToTenant`, `HasFactory`, `SoftDeletes`
- **Campos Principales:**
  - `company_id`, `account_number`, `client_company_id`
  - `status` (draft, sent, confirmed, in_production, completed, invoiced, cancelled)
  - `account_date`, `due_date`, `subtotal`, `tax_amount`, `total`
  - `created_by`, `approved_by`, `approved_at`
- **Relaciones:**
  - `BelongsTo`: company, clientCompany, createdBy, approvedBy
  - `BelongsToMany`: documentItems (con pivot document_item_collection_account)
  - `HasMany`: statusHistories
- **Métodos:**
  - `generateAccountNumber()`: CC-2025-001
  - `calculateTotals()`: Suma items de la cuenta

---

### 6. Modelos de Red Social Empresarial

#### **SocialPost** (Publicación en Red Social)
- **Archivo:** `app/Models/SocialPost.php`
- **Traits:** `BelongsToTenant`, `HasFactory`, `SoftDeletes`
- **Campos Principales:**
  - `company_id`, `author_id`, `title`, `content`, `image`
  - `visibility` (public, company, department, role)
  - `likes_count`, `comments_count`, `shares_count`
- **Relaciones:**
  - `BelongsTo`: company, author (User)
  - `HasMany`: reactions, comments, likes
- **Scopes:**
  - `published()`, `byVisibility()`, `recent()`

#### **SocialPostComment** (Comentario en Post)
- **Archivo:** `app/Models/SocialPostComment.php`
- **Campos Principales:**
  - `company_id`, `post_id`, `author_id`, `content`
  - `parent_comment_id` (para respuestas)
- **Relaciones:**
  - `BelongsTo`: company, post, author, parentComment
  - `HasMany`: replies, likes

#### **SocialPostReaction** (Reacción en Post)
- **Archivo:** `app/Models/SocialPostReaction.php`
- **Campos Principales:**
  - `company_id`, `post_id`, `user_id`, `reaction_type`
- **Tipos:** like, love, haha, wow, sad, angry

#### **CompanyFollower** (Seguimiento entre Empresas)
- **Archivo:** `app/Models/CompanyFollower.php`
- **Campos Principales:**
  - `follower_company_id`, `followed_company_id`, `user_id`
- **Relaciones:**
  - `BelongsTo`: followerCompany, followedCompany, user

#### **SocialNotification** (Notificación de Red Social)
- **Archivo:** `app/Models/SocialNotification.php`
- **Campos Principales:**
  - `company_id`, `user_id`, `sender_id`, `type`, `title`, `message`
  - `data`, `read_at`
- **Tipos:** post_created, post_liked, post_commented, company_followed

---

### 7. Modelos de Sistema de Notificaciones Avanzado

#### **NotificationChannel** (Canal de Notificaciones)
- **Archivo:** `app/Models/NotificationChannel.php`
- **Campos Principales:**
  - `name`, `type` (email, database, SMS, push, custom)
  - `config` (JSON con configuración específica)
  - `is_active`, `priority`
- **Relaciones:**
  - `BelongsTo`: creator
  - `HasMany`: notificationLogs, recentLogs

#### **NotificationRule** (Regla de Envío de Notificaciones)
- **Archivo:** `app/Models/NotificationRule.php`
- **Campos Principales:**
  - `name`, `event_type`, `conditions` (JSON)
  - `channels` (array de canales a usar)
  - `recipients`, `is_active`, `priority`

#### **NotificationLog** (Log de Notificaciones Enviadas)
- **Archivo:** `app/Models/NotificationLog.php`
- **Campos Principales:**
  - `notification_channel_id`, `recipient_id`, `event_type`
  - `status`, `sent_at`, `delivered_at`, `failed_at`
  - `error_message`, `metadata`

---

### 8. Modelos de Inventario y Stock

#### **StockMovement** (Movimiento de Inventario)
- **Archivo:** `app/Models/StockMovement.php`
- **Traits:** `BelongsToTenant`
- **Campos Principales:**
  - `company_id`, `user_id`, `stockable_type`, `stockable_id`
  - `type` (purchase, sale, adjustment, transfer, damage, return)
  - `quantity`, `unit_cost`, `total_cost`
  - `reference`, `notes`, `movement_date`
- **Relaciones:**
  - `BelongsTo`: company, user
  - `MorphTo`: stockable (Paper, Product, etc.)

#### **StockAlert** (Alerta de Stock Crítico)
- **Archivo:** `app/Models/StockAlert.php`
- **Traits:** `BelongsToTenant`
- **Campos Principales:**
  - `company_id`, `stockable_type`, `stockable_id`
  - `alert_type` (low_stock, out_of_stock, expiring_soon)
  - `alert_level` (info, warning, critical)
  - `current_stock`, `min_stock`, `threshold`
  - `status` (active, acknowledged, resolved)
  - `acknowledged_by`, `acknowledged_at`, `resolved_by`, `resolved_at`
- **Relaciones:**
  - `BelongsTo`: company, acknowledgedBy, resolvedBy
  - `MorphTo`: stockable

---

### 9. Modelos de Suscripción y Facturación

#### **Plan** (Plan de Suscripción)
- **Archivo:** `app/Models/Plan.php`
- **Campos Principales:**
  - `name`, `slug`, `description`, `price`, `currency`
  - `interval` (month/year), `trial_days`
  - `features` (JSON), `limits` (JSON)
  - `is_active`, `is_featured`, `sort_order`
- **Constantes:**
  - `FREE = 'free'`, `BASIC = 'basic'`, `PROFESSIONAL = 'professional'`, `ENTERPRISE = 'enterprise'`

#### **Subscription** (Suscripción de Empresa)
- **Archivo:** `app/Models/Subscription.php`
- **Campos Principales:**
  - `company_id`, `user_id`, `name`, `stripe_id`
  - `stripe_status`, `stripe_price`, `quantity`
  - `trial_ends_at`, `ends_at`
- **Relaciones:**
  - `BelongsTo`: company, user

#### **Invoice** (Factura de Suscripción)
- **Archivo:** `app/Models/Invoice.php`
- **Campos Principales:**
  - `company_id`, `subscription_id`, `invoice_number`
  - `amount`, `status`, `payment_method`
  - `paid_at`, `due_date`
- **Relaciones:**
  - `BelongsTo`: company, subscription

#### **UsageMetric** (Métricas de Uso)
- **Archivo:** `app/Models/UsageMetric.php`
- **Campos Principales:**
  - `company_id`, `metric_type`, `metric_value`
  - `period_start`, `period_end`, `metadata`
- **Relaciones:**
  - `BelongsTo`: company

---

### 10. Modelos de Configuración y Sistema

#### **CompanySettings** (Configuración de Empresa)
- **Archivo:** `app/Models/CompanySettings.php`
- **Campos Principales:**
  - `company_id`, `timezone`, `currency`, `language`
  - `tax_rate`, `date_format`, `time_format`
  - `invoice_prefix`, `quote_prefix`, `order_prefix`
  - `email_notifications`, `sms_notifications`
- **Relaciones:**
  - `BelongsTo`: company

#### **ActivityLog** (Log de Actividades)
- **Archivo:** `app/Models/ActivityLog.php`
- **Campos Principales:**
  - `company_id`, `user_id`, `subject_type`, `subject_id`
  - `event`, `description`, `properties` (JSON)
  - `ip_address`, `user_agent`
- **Relaciones:**
  - `BelongsTo`: company, user
  - `MorphTo`: subject

#### **DashboardWidget** (Widget de Dashboard Personalizado)
- **Archivo:** `app/Models/DashboardWidget.php`
- **Campos Principales:**
  - `company_id`, `user_id`, `widget_type`
  - `configuration` (JSON), `position`, `is_visible`
- **Relaciones:**
  - `BelongsTo`: company, user

---

### 11. Modelos Geográficos (Soporte)

#### **Country** (País)
- **Archivo:** `app/Models/Country.php`
- **Relaciones:** `HasMany`: states, companies, users

#### **State** (Departamento/Estado)
- **Archivo:** `app/Models/State.php`
- **Relaciones:** `BelongsTo`: country | `HasMany`: cities, companies, users

#### **City** (Ciudad)
- **Archivo:** `app/Models/City.php`
- **Relaciones:** `BelongsTo`: state, country | `HasMany`: companies, users

---

### 12. Modelos de Relaciones Empresariales

#### **SupplierRequest** (Solicitud de Proveedor)
- **Archivo:** `app/Models/SupplierRequest.php`
- **Campos Principales:**
  - `requester_company_id`, `supplier_company_id`
  - `status` (pending, approved, rejected)
  - `message`, `response_message`
- **Relaciones:**
  - `BelongsTo`: requesterCompany, supplierCompany

#### **SupplierRelationship** (Relación Cliente-Proveedor Aprobada)
- **Archivo:** `app/Models/SupplierRelationship.php`
- **Campos Principales:**
  - `client_company_id`, `supplier_company_id`
  - `relationship_type`, `is_active`, `approved_by_user_id`
- **Relaciones:**
  - `BelongsTo`: clientCompany, supplierCompany, approvedByUser

---

### 13. Modelos Adicionales (Marketplace y Otros)

- **MarketplaceOffer**: Ofertas de papel en marketplace
- **PaperOrder**: Órdenes de papel
- **PaperOrderItem**: Items de orden de papel
- **Deadline**: Plazos de entrega
- **CompanyConnection**: Conexiones entre empresas
- **SocialComment**: Comentarios en posts
- **SocialLike**: Likes en posts
- **SocialConnection**: Conexiones sociales
- **MagazinePage**: Páginas de revistas
- **TalonarioSheet**: Hojas de talonarios
- **FinishingRange**: Rangos de precios de acabados
- **AutomatedReport**: Reportes automáticos
- **ReportExecution**: Ejecución de reportes
- **PlanExperiment**: Experimentos A/B de planes
- **EnterprisePlan**: Planes empresariales personalizados
- **ApiIntegration**: Integraciones API
- **DatabaseNotification**: Notificaciones Laravel personalizadas

---

## 🔧 SERVICIOS DE NEGOCIO (19)

### 1. Servicios de Cálculo de Precios

#### **SimpleItemCalculatorService**
- **Archivo:** `app/Services/SimpleItemCalculatorService.php`
- **Propósito:** Cálculo completo de costos para SimpleItem
- **Métodos Principales:**
  - `calculateFinalPricing(SimpleItem $item)`: Cálculo completo legacy
  - `calculateFinalPricingNew(SimpleItem $item)`: Cálculo con NUEVO sistema montaje+cortes
  - `calculateMountingWithCuts(SimpleItem $item)`: Sistema NUEVO de montaje con divisor
  - `calculatePureMounting(SimpleItem $item)`: Montaje puro (cuántas copias por pliego)
  - `calculateMountingOptions(SimpleItem $item)`: Opciones de montaje disponibles
  - `calculatePrintingMillares(SimpleItem $item)`: Cálculo de millares para impresión
  - `calculatePrintingMillaresNew(SimpleItem $item, array $mountingWithCuts)`: Millares con NUEVO sistema
  - `validateTechnicalViability(SimpleItem $item)`: Valida viabilidad técnica
- **Integración:**
  - Usa `MountingCalculatorService` para cálculos de montaje
  - Usa `CuttingCalculatorService` para cálculos de cortes
  - Usa `FinishingCalculatorService` para acabados
- **DTOs Retornados:**
  - `PricingResult`: Resultado completo de pricing
  - `MountingOption`: Opción de montaje (horizontal/vertical/maximum)
  - `PrintingCalculation`: Cálculo de impresión (millares, costos)
  - `AdditionalCosts`: Costos adicionales (corte, montaje, diseño, etc.)

#### **MountingCalculatorService**
- **Archivo:** `app/Services/MountingCalculatorService.php`
- **Propósito:** Cálculo PURO de montaje (cuántas copias caben en una máquina)
- **Métodos:**
  - `calculateMounting($workWidth, $workHeight, $machineWidth, $machineHeight, $marginPerSide)`: Cálculo en 3 orientaciones (horizontal, vertical, maximum)
  - `calculateRequiredSheets($totalCopies, $copiesPerSheet)`: Pliegos necesarios
- **Notas:**
  - NO conoce papel ni divisor de cortes
  - Solo calcula cuántas copias caben en el tamaño de máquina
  - Retorna: horizontal, vertical, maximum (mejor opción), sheets_info, efficiency

#### **CuttingCalculatorService**
- **Archivo:** `app/Services/CuttingCalculatorService.php`
- **Propósito:** Cálculo de cortes de máquina en pliego
- **Métodos:**
  - `calculateCuts($paperWidth, $paperHeight, $cutWidth, $cutHeight, $desiredCuts, $orientation)`: Optimización de cortes
  - `arrangeMultipleCuts($paperWidth, $paperHeight, $cutWidth, $cutHeight)`: Arreglo de cortes en papel
- **Retorna:**
  - `cutsPerSheet`: Cortes por pliego
  - `sheetsNeeded`: Pliegos necesarios
  - `totalCutsProduced`: Total de cortes producidos
  - `wastePercentage`: Porcentaje de desperdicio
  - `arrangeResult`: Layout de cortes (horizontal_cuts × vertical_cuts)

#### **FinishingCalculatorService**
- **Archivo:** `app/Services/FinishingCalculatorService.php`
- **Propósito:** Cálculo de costos de acabados
- **Métodos:**
  - `calculateCost(Finishing $finishing, array $params)`: Cálculo según tipo de medición
  - `calculateByMillar(Finishing $finishing, int $quantity)`: Por millar
  - `calculateByRange(Finishing $finishing, int $quantity)`: Por rango
  - `calculateBySize(Finishing $finishing, float $width, float $height)`: Por tamaño
  - `calculateByUnit(Finishing $finishing, int $quantity)`: Por unidad
  - `calculateFixed(Finishing $finishing)`: Costo fijo
- **Parámetros esperados por tipo:**
  - MILLAR/RANGO/UNIDAD: `['quantity' => int]`
  - TAMAÑO: `['width' => float, 'height' => float]`
  - FIJO: `[]`

#### **DigitalItemCalculatorService**
- **Archivo:** `app/Services/DigitalItemCalculatorService.php`
- **Propósito:** Cálculo de precios para servicios digitales
- **Métodos:**
  - `calculateTotalPrice(DigitalItem $item, array $params)`: Precio total según tipo
  - `calculateByFixed(DigitalItem $item, int $quantity)`: Precio fijo
  - `calculateBySize(DigitalItem $item, float $width, float $height)`: Por tamaño (m²)
  - `calculateByUnit(DigitalItem $item, int $quantity)`: Por unidad

#### **TalonarioCalculatorService**
- **Archivo:** `app/Services/TalonarioCalculatorService.php`
- **Propósito:** Cálculo de costos para talonarios numerados
- **Métodos:**
  - `calculateCost(TalonarioItem $item)`: Cálculo completo
  - `calculateSheetCost(TalonarioSheet $sheet)`: Costo por hoja

#### **MagazineCalculatorService**
- **Archivo:** `app/Services/MagazineCalculatorService.php`
- **Propósito:** Cálculo de costos para revistas
- **Métodos:**
  - `calculateCost(MagazineItem $item)`: Cálculo completo
  - `calculatePageCost(MagazinePage $page)`: Costo por página

---

### 2. Servicios de Inventario y Stock

#### **StockMovementService**
- **Archivo:** `app/Services/StockMovementService.php`
- **Propósito:** Gestión de movimientos de inventario
- **Métodos:**
  - `recordMovement($stockable, $type, $quantity, $reason)`: Registrar movimiento
  - `purchase($stockable, $quantity, $unitCost, $reference)`: Compra
  - `sale($stockable, $quantity, $unitCost, $reference)`: Venta
  - `adjustment($stockable, $quantity, $reason)`: Ajuste
  - `transfer($stockable, $quantity, $destination, $reason)`: Transferencia
  - `getMovementHistory($stockable)`: Historial de movimientos

#### **StockAlertService**
- **Archivo:** `app/Services/StockAlertService.php`
- **Propósito:** Gestión de alertas de stock
- **Métodos:**
  - `checkStock($stockable)`: Verificar nivel de stock
  - `createAlert($stockable, $alertType, $alertLevel)`: Crear alerta
  - `acknowledgeAlert($alert, $user)`: Reconocer alerta
  - `resolveAlert($alert, $user)`: Resolver alerta
  - `getActiveAlerts($company)`: Alertas activas

#### **StockNotificationService**
- **Archivo:** `app/Services/StockNotificationService.php`
- **Propósito:** Notificaciones de stock crítico
- **Métodos:**
  - `notifyLowStock($stockable)`: Notificar stock bajo
  - `notifyOutOfStock($stockable)`: Notificar sin stock
  - `notifyExpiringSoon($stockable)`: Notificar próximo vencimiento
  - `sendAlertNotifications($alert)`: Enviar notificaciones de alerta

#### **StockPredictionService**
- **Archivo:** `app/Services/StockPredictionService.php`
- **Propósito:** Predicción de necesidades de stock
- **Métodos:**
  - `predictNextMonth($stockable)`: Predicción próximo mes
  - `getConsumptionRate($stockable)`: Tasa de consumo
  - `estimateReorderPoint($stockable)`: Punto de reorden

#### **StockReportService**
- **Archivo:** `app/Services/StockReportService.php`
- **Propósito:** Reportes de inventario
- **Métodos:**
  - `getStockSummary($company)`: Resumen de stock
  - `getLowStockItems($company)`: Items con stock bajo
  - `getValuation($company)`: Valoración de inventario
  - `getMovementReport($company, $startDate, $endDate)`: Reporte de movimientos

---

### 3. Servicios de Notificaciones

#### **NotificationService**
- **Archivo:** `app/Services/NotificationService.php`
- **Propósito:** Sistema avanzado de notificaciones multi-canal
- **Métodos:**
  - `send($type, $userId, $data, $priority)`: Enviar notificación
  - `sendToChannel($channel, $notification)`: Enviar por canal específico
  - `sendEmail($notification)`: Enviar email
  - `sendSMS($notification)`: Enviar SMS
  - `sendPush($notification)`: Enviar push notification
  - `logNotification($notification, $status)`: Registrar log

---

### 4. Servicios de Producción y Órdenes

#### **ProductionCalculatorService**
- **Archivo:** `app/Services/ProductionCalculatorService.php`
- **Propósito:** Cálculo de producción
- **Métodos:**
  - `calculateProductionTime($order)`: Tiempo de producción
  - `calculateMaterialNeeds($order)`: Necesidades de material
  - `estimateCompletionDate($order)`: Fecha estimada de finalización

#### **ProductionOrderGroupingService**
- **Archivo:** `app/Services/ProductionOrderGroupingService.php`
- **Propósito:** Agrupación de órdenes de producción
- **Métodos:**
  - `groupByPaper($orders)`: Agrupar por papel
  - `groupByMachine($orders)`: Agrupar por máquina
  - `optimizeSequence($orders)`: Optimizar secuencia de producción

#### **PurchaseOrderPdfService**
- **Archivo:** `app/Services/PurchaseOrderPdfService.php`
- **Propósito:** Generación de PDF de órdenes de compra
- **Métodos:**
  - `generatePdf(PurchaseOrder $order)`: Generar PDF
  - `generateQuotePdf(Document $quote)`: Generar PDF de cotización

---

### 5. Servicios de Suscripción y Límites

#### **PlanLimitService**
- **Archivo:** `app/Services/PlanLimitService.php`
- **Propósito:** Verificación de límites de plan
- **Métodos:**
  - `canAddUser(Company $company)`: Verifica si puede agregar usuario
  - `canCreateDocument(Company $company)`: Verifica límite de documentos
  - `canAccessFeature(Company $company, $feature)`: Verifica acceso a feature
  - `getRemainingLimit(Company $company, $limitType)`: Límite restante

#### **CustomSubscriptionBuilder**
- **Archivo:** `app/Services/CustomSubscriptionBuilder.php`
- **Propósito:** Constructor de suscripciones personalizadas
- **Métodos:**
  - `buildSubscription(Company $company, Plan $plan)`: Construir suscripción
  - `applyTrial(Subscription $subscription, $days)`: Aplicar período de prueba
  - `addCoupon(Subscription $subscription, $coupon)`: Aplicar cupón

---

### 6. Servicios de Contexto Multi-Tenant

#### **TenantContext**
- **Archivo:** `app/Services/TenantContext.php`
- **Propósito:** Gestión del contexto de tenant actual
- **Métodos:**
  - `setTenant(Company $company)`: Establecer tenant
  - `getTenant()`: Obtener tenant actual
  - `clearTenant()`: Limpiar contexto
  - `runInTenantContext(Company $company, Closure $callback)`: Ejecutar en contexto

---

## 🎨 WIDGETS DE DASHBOARD (29)

### 1. Widgets de Stock e Inventario

1. **SimpleStockKpisWidget**: KPIs básicos de stock
2. **StockKpisWidget**: KPIs avanzados de stock
3. **StockMovementsKpisWidget**: KPIs de movimientos
4. **StockAlertsWidget**: Alertas de stock crítico
5. **AdvancedStockAlertsWidget**: Alertas avanzadas con análisis
6. **StockTrendsChartWidget**: Gráfico de tendencias de stock
7. **StockLevelTrackingWidget**: Seguimiento de niveles
8. **StockMovementsTableWidget**: Tabla de movimientos
9. **StockPredictionsWidget**: Predicciones de stock
10. **RecentMovementsWidget**: Movimientos recientes

### 2. Widgets de Documentos y Órdenes

11. **ActiveDocumentsWidget**: Documentos activos
12. **RecentOrdersWidget**: Órdenes recientes
13. **PurchaseOrdersOverviewWidget**: Resumen de órdenes de compra
14. **PurchaseOrderNotificationsWidget**: Notificaciones de órdenes
15. **ReceivedOrdersWidget**: Órdenes recibidas
16. **PendingOrdersStatsWidget**: Estadísticas de órdenes pendientes
17. **DeliveryAlertsWidget**: Alertas de entrega
18. **DeadlinesWidget**: Plazos de entrega

### 3. Widgets de Red Social

19. **SocialFeedWidget**: Feed de posts sociales
20. **CreatePostWidget**: Crear nuevo post
21. **CompanyPostsWidget**: Posts de la empresa
22. **SocialPostWidget**: Widget de post individual
23. **SuggestedCompaniesWidget**: Empresas sugeridas para seguir

### 4. Widgets de Calculadoras

24. **PaperCalculatorWidget**: Calculadora de papel
25. **CalculadoraCorteWidget**: Calculadora de cortes con SVG

### 5. Widgets de Sistema

26. **DashboardStatsWidget**: Estadísticas generales
27. **QuickActionsWidget**: Acciones rápidas
28. **OnboardingWidget**: Onboarding de nuevos usuarios
29. **MrrWidget**: Monthly Recurring Revenue (solo Super Admin)

---

## 📄 PÁGINAS FILAMENT PERSONALIZADAS (11)

### 1. Páginas de Autenticación
1. **Register** (`app/Filament/Pages/Auth/Register.php`): Registro de usuarios
2. **RequestPasswordReset** (`app/Filament/Pages/Auth/PasswordReset/RequestPasswordReset.php`)
3. **ResetPassword** (`app/Filament/Pages/Auth/PasswordReset/ResetPassword.php`)

### 2. Páginas de Dashboard
4. **Dashboard** (`app/Filament/Pages/Dashboard.php`): Dashboard principal
5. **Home** (`app/Filament/Pages/Home.php`): Página de inicio

### 3. Páginas de Empresa
6. **CompanyProfile** (`app/Filament/Pages/CompanyProfile.php`): Perfil de empresa
7. **CompanySettings** (`app/Filament/Pages/CompanySettings.php`): Configuración de empresa
8. **Companies** (`app/Filament/Pages/Companies.php`): Listado de empresas (Super Admin)

### 4. Páginas de Inventario
9. **StockManagement** (`app/Filament/Pages/StockManagement.php`): Gestión de stock
10. **StockMovements** (`app/Filament/Pages/StockMovements.php`): Movimientos de stock

### 5. Páginas de Facturación
11. **Billing** (`app/Filament/Pages/Billing.php`): Facturación y suscripciones

---

## 🛡️ POLÍTICAS DE AUTORIZACIÓN (10)

### 1. Políticas de Recursos Core

1. **UserPolicy** (`app/Policies/UserPolicy.php`)
   - Métodos: `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`
   - Restricción: Solo Super Admin y Company Admin

2. **RolePolicy** (`app/Policies/RolePolicy.php`)
   - Métodos: `viewAny`, `view`, `create`, `update`, `delete`
   - Restricción: Solo Super Admin y Company Admin

3. **CompanyPolicy** (`app/Policies/CompanyPolicy.php`)
   - Métodos: `viewAny`, `view`, `create`, `update`, `delete`
   - Restricción: Super Admin para todas, Company Admin solo su empresa

### 2. Políticas de Documentos

4. **DocumentPolicy** (`app/Policies/DocumentPolicy.php`)
   - Métodos: `viewAny`, `view`, `create`, `update`, `delete`
   - Verificación: Permisos específicos + company_id

5. **ContactPolicy** (`app/Policies/ContactPolicy.php`)
   - Métodos: `viewAny`, `view`, `create`, `update`, `delete`
   - Verificación: Permisos específicos + company_id

### 3. Políticas de Productos e Items

6. **ProductPolicy** (`app/Policies/ProductPolicy.php`)
   - Métodos: `viewAny`, `view`, `create`, `update`, `delete`
   - Verificación: Permisos específicos + company_id

7. **SimpleItemPolicy** (`app/Policies/SimpleItemPolicy.php`)
   - Métodos: `viewAny`, `view`, `create`, `update`, `delete`
   - Verificación: Permisos específicos + company_id

### 4. Políticas de Órdenes

8. **PurchaseOrderPolicy** (`app/Policies/PurchaseOrderPolicy.php`)
   - Métodos: `viewAny`, `view`, `create`, `update`, `delete`, `approve`
   - Verificación: Permisos específicos + company_id

### 5. Políticas de Proveedores

9. **SupplierRequestPolicy** (`app/Policies/SupplierRequestPolicy.php`)
   - Métodos: `viewAny`, `view`, `create`, `update`, `delete`, `approve`, `reject`
   - Verificación: Puede ser requester o supplier

### 6. Políticas de Red Social

10. **SocialPostPolicy** (`app/Policies/SocialPostPolicy.php`)
    - Métodos: `viewAny`, `create`, `update`, `delete`
    - Verificación: 
      - `viewAny`: Requiere `view-posts`
      - `create`: Requiere `create-posts`
      - `update`: Requiere `edit-posts` O ser autor
      - `delete`: Requiere `delete-posts` O ser autor

---

## 🔄 RECURSOS FILAMENT (19)

### 1. Recursos de Usuarios y Roles

1. **UserResource**
   - Modelo: User
   - Páginas: List, Create, Edit
   - Verificación: `canViewAny()` - Solo Admin/Manager
   - Form: UserForm
   - Table: UsersTable

2. **RoleResource**
   - Modelo: Role (Spatie)
   - Páginas: List, Create, Edit
   - Verificación: `canViewAny()` - Solo Admin
   - Form: RoleForm (con categorías de permisos)
   - Table: RolesTable

### 2. Recursos de Contactos

3. **ContactResource**
   - Modelo: Contact
   - Páginas: List, Create, Edit
   - RelationManager: SuppliersRelationManager
   - Policy: ContactPolicy
   - Form: ContactForm
   - Table: ContactsTable

### 3. Recursos de Documentos

4. **DocumentResource**
   - Modelo: Document
   - Páginas: List, Edit, View
   - Policy: DocumentPolicy
   - Forms: ProductDocumentForm, CustomItemDocumentForm, DocumentItemFormFactory
   - RelationManagers: Múltiples handlers (ProductHandler, SimpleItemHandler, etc.)
   - Arquitectura: Factory pattern para items polimórficos

### 4. Recursos de Catálogo

5. **PaperResource**
   - Modelo: Paper
   - Páginas: List, Create, Edit
   - Verificación: `canViewAny()` - Solo Admin/Manager
   - Form: PaperForm
   - Table: PapersTable

6. **PrintingMachineResource**
   - Modelo: PrintingMachine
   - Páginas: List, Create, Edit
   - Verificación: `canViewAny()` - Solo Admin/Manager
   - Form: PrintingMachineForm
   - Table: PrintingMachinesTable

7. **FinishingResource**
   - Modelo: Finishing
   - Páginas: List, Create, Edit
   - Verificación: `canViewAny()` - Solo Admin/Manager
   - Sin form dedicado (inline)

### 5. Recursos de Productos e Items

8. **ProductResource**
   - Modelo: Product
   - Páginas: List, Create, Edit
   - Policy: ProductPolicy
   - Form: ProductForm
   - Table: ProductsTable

9. **SimpleItemResource**
   - Modelo: SimpleItem
   - Páginas: List, Create, Edit
   - Policy: SimpleItemPolicy
   - Form: SimpleItemForm (con sección de acabados)
   - Table: SimpleItemsTable

10. **DigitalItemResource**
    - Modelo: DigitalItem
    - Páginas: List, Create, Edit
    - Form: DigitalItemForm
    - Table: DigitalItemsTable

11. **TalonarioItemResource**
    - Modelo: TalonarioItem
    - Páginas: List, Create, Edit
    - RelationManager: TalonarioSheetsRelationManager
    - Form: TalonarioItemForm
    - Table: TalonarioItemsTable

12. **MagazineItemResource**
    - Modelo: MagazineItem
    - Páginas: List, Create, Edit
    - Form: MagazineItemForm
    - Table: MagazineItemsTable

### 6. Recursos de Órdenes

13. **PurchaseOrderResource**
    - Modelo: PurchaseOrder
    - Páginas: List, Create, Edit, View
    - Policy: PurchaseOrderPolicy
    - Form: PurchaseOrderForm
    - Table: PurchaseOrdersTable
    - Arquitectura: Multi-paper support con PurchaseOrderItem

14. **ProductionOrderResource**
    - Modelo: ProductionOrder
    - Páginas: List, Create, Edit
    - Sin verificación: ❌ PENDIENTE agregar canViewAny()

15. **CollectionAccountResource**
    - Modelo: CollectionAccount
    - Páginas: List, Create, Edit
    - Verificación: `canViewAny()` - Solo Admin/Manager

### 7. Recursos de Proveedores

16. **SupplierRequestResource**
    - Modelo: SupplierRequest
    - Páginas: List, Create, Edit
    - Policy: SupplierRequestPolicy
    - Form: SupplierRequestForm
    - Table: SupplierRequestsTable

17. **SupplierRelationshipResource**
    - Modelo: SupplierRelationship
    - Páginas: List, Create, Edit
    - Form: SupplierRelationshipForm

### 8. Recursos de Suscripción

18. **PlanResource**
    - Modelo: Plan
    - Páginas: List, Create, Edit
    - Form: PlanForm
    - Table: PlansTable

19. **SubscriptionResource**
    - Modelo: Subscription
    - Páginas: List, Create, Edit
    - Form: SubscriptionForm
    - Table: SubscriptionsTable

---

## 🗄️ ESTRUCTURA DE BASE DE DATOS

### Tablas Principales (125 Migraciones)

#### 1. Core del Sistema
- `users` - Usuarios del sistema (multi-tenant)
- `companies` - Empresas (tenant principal)
- `company_settings` - Configuración por empresa
- `permission_tables` - Spatie Permission (roles, permissions, model_has_roles, etc.)
- `countries`, `states`, `cities` - Geolocalización

#### 2. Documentos y Cotizaciones
- `documents` - Cotizaciones/Órdenes/Facturas
- `document_types` - Tipos de documento
- `document_items` - Items polimórficos de documento
- `document_item_finishings` - Acabados aplicados a items

#### 3. Items Específicos (Polimórficos)
- `simple_items` - Items de impresión sencillos
- `products` - Productos del catálogo
- `digital_items` - Servicios digitales
- `talonario_items` - Talonarios
- `talonario_sheets` - Hojas de talonario
- `magazine_items` - Revistas
- `magazine_pages` - Páginas de revista
- `custom_items` - Items personalizados

#### 4. Catálogo
- `papers` - Papeles
- `printing_machines` - Máquinas de impresión
- `finishings` - Acabados
- `finishing_ranges` - Rangos de precios de acabados
- `contacts` - Clientes y proveedores

#### 5. Órdenes
- `purchase_orders` - Órdenes de compra
- `purchase_order_items` - Items de orden de compra (pivot como entity)
- `document_item_purchase_order` - Pivot DocumentItem-PurchaseOrder
- `production_orders` - Órdenes de producción
- `document_item_production_order` - Pivot DocumentItem-ProductionOrder
- `collection_accounts` - Cuentas de cobro
- `document_item_collection_account` - Pivot DocumentItem-CollectionAccount
- `order_status_histories` - Historial de estados
- `collection_account_status_histories` - Historial de estados de cuentas

#### 6. Inventario
- `stock_movements` - Movimientos de stock (polimórfico)
- `stock_alerts` - Alertas de stock crítico (polimórfico)
- `stock_notifications` - Notificaciones de stock

#### 7. Red Social
- `social_posts` - Publicaciones
- `social_post_comments` - Comentarios
- `social_post_reactions` - Reacciones
- `social_notifications` - Notificaciones sociales
- `company_followers` - Seguimiento entre empresas
- `social_comments`, `social_likes`, `social_connections` - Sistema social legacy

#### 8. Notificaciones Avanzadas
- `notification_channels` - Canales de notificaciones
- `notification_rules` - Reglas de envío
- `notification_logs` - Logs de notificaciones
- `notifications` - Notificaciones Laravel estándar

#### 9. Suscripciones y Facturación
- `plans` - Planes de suscripción
- `subscriptions` - Suscripciones (Cashier)
- `subscription_items` - Items de suscripción (Cashier)
- `invoices` - Facturas de suscripción
- `usage_metrics` - Métricas de uso
- `plan_experiments` - Experimentos A/B
- `enterprise_plans` - Planes empresariales

#### 10. Proveedores
- `supplier_requests` - Solicitudes de proveedor
- `supplier_relationships` - Relaciones aprobadas

#### 11. Sistema
- `activity_logs` - Logs de actividad
- `dashboard_widgets` - Widgets de dashboard
- `automated_reports` - Reportes automáticos
- `report_executions` - Ejecuciones de reportes
- `api_integrations` - Integraciones API
- `deadlines` - Plazos de entrega (polimórfico)
- `jobs`, `cache`, `sessions` - Laravel estándar

#### 12. Marketplace (Legacy)
- `marketplace_offers` - Ofertas de marketplace
- `paper_orders` - Órdenes de papel
- `paper_order_items` - Items de orden de papel
- `company_connections` - Conexiones entre empresas

#### 13. Tablas Pivot
- `simple_item_finishing` - SimpleItem ↔ Finishing
- `digital_item_finishing` - DigitalItem ↔ Finishing
- `magazine_item_finishings` - MagazineItem ↔ Finishing
- `talonario_finishings` - TalonarioItem ↔ Finishing
- `document_item_purchase_order` - DocumentItem ↔ PurchaseOrder
- `document_item_collection_account` - DocumentItem ↔ CollectionAccount
- `document_item_production_order` - DocumentItem ↔ ProductionOrder

---

## 🔗 MAPA DE RELACIONES PRINCIPALES

### Arquitectura Multi-Tenant

```
Company (Tenant Root)
├── users (HasMany)
├── settings (HasOne)
├── contacts (HasMany)
├── papers (HasMany)
├── printingMachines (HasMany)
├── products (HasMany)
├── documents (HasMany)
│   └── items (HasMany - DocumentItem)
│       └── itemable (MorphTo)
│           ├── SimpleItem
│           ├── Product
│           ├── DigitalItem
│           ├── TalonarioItem
│           ├── MagazineItem
│           └── CustomItem
├── purchaseOrders (HasMany)
├── productionOrders (HasMany)
├── collectionAccounts (HasMany)
├── invoices (HasMany)
├── usageMetrics (HasMany)
├── activityLogs (HasMany)
├── supplierRequests (HasMany)
└── followers (HasMany - CompanyFollower)
```

### Relaciones de DocumentItem (Polimórfico)

```
DocumentItem
├── document (BelongsTo)
├── itemable (MorphTo) - 6 tipos
│   ├── SimpleItem
│   ├── Product
│   ├── DigitalItem
│   ├── TalonarioItem
│   ├── MagazineItem
│   └── CustomItem
├── printingMachine (BelongsTo)
├── paper (BelongsTo)
├── finishings (HasMany)
├── purchaseOrders (BelongsToMany - pivot)
├── collectionAccounts (BelongsToMany - pivot)
└── productionOrders (BelongsToMany - pivot)
```

### Relaciones de SimpleItem (Item Principal)

```
SimpleItem
├── company (BelongsTo - multi-tenant)
├── paper (BelongsTo)
├── printingMachine (BelongsTo)
├── documentItems (MorphMany)
└── finishings (BelongsToMany - pivot simple_item_finishing)
    └── pivot: quantity, width, height, calculated_cost, is_default
```

### Relaciones de PurchaseOrder (Arquitectura Multi-Paper)

```
PurchaseOrder
├── company (BelongsTo)
├── supplierCompany (BelongsTo)
├── createdBy (BelongsTo - User)
├── approvedBy (BelongsTo - User)
├── documentItems (BelongsToMany - pivot)
│   └── pivot: quantity_ordered, unit_price, total_price, status
├── purchaseOrderItems (HasMany - PurchaseOrderItem)
│   ├── documentItem (BelongsTo)
│   ├── purchaseOrder (BelongsTo)
│   └── paper (BelongsTo)
└── statusHistories (HasMany)
```

### Relaciones de Stock (Polimórfico)

```
Paper/Product (stockable)
├── stockMovements (MorphMany)
│   ├── company (BelongsTo)
│   ├── user (BelongsTo)
│   └── stockable (MorphTo)
└── stockAlerts (MorphMany)
    ├── company (BelongsTo)
    ├── stockable (MorphTo)
    ├── acknowledgedBy (BelongsTo - User)
    └── resolvedBy (BelongsTo - User)
```

---

## 🎯 SISTEMA DE PERMISOS (Spatie Permission)

### Roles del Sistema (8)

1. **Super Admin**: Acceso total al sistema
2. **Company Admin**: Administrador de empresa
3. **Manager**: Gerente con acceso amplio
4. **Salesperson**: Vendedor con permisos limitados
5. **Operator**: Operador de producción
6. **Customer**: Cliente externo
7. **Employee**: Empleado general
8. **Client**: Cliente (legacy)

### Permisos por Categoría (56 Totales)

#### Gestión de Usuarios (4)
- view-users, create-users, edit-users, delete-users

#### Gestión de Contactos (4)
- view-contacts, create-contacts, edit-contacts, delete-contacts

#### Cotizaciones (6)
- view-documents, create-documents, edit-documents, delete-documents
- approve-documents, reject-documents

#### Documentos (5)
- view-documents, create-documents, edit-documents, delete-documents
- send-documents

#### Órdenes de Producción (5)
- view-production-orders, create-production-orders, edit-production-orders
- delete-production-orders, manage-production

#### Órdenes de Papel (4)
- view-paper-orders, create-paper-orders, edit-paper-orders, delete-paper-orders

#### Productos (4)
- view-products, create-products, edit-products, delete-products

#### Equipos (4)
- view-machines, create-machines, edit-machines, delete-machines

#### Empresas (4) - Solo Super Admin
- view-companies, create-companies, edit-companies, delete-companies

#### Inventario (3)
- manage-inventory, manage-paper-catalog, manage-printing-machines

#### Sistema (6)
- access-admin-panel, manage-settings, view-reports, export-data
- import-data, manage-roles

#### Reportes (2)
- view-reports, export-reports

#### Red Social (5)
- view-posts, create-posts, edit-posts, delete-posts, manage-social

---

## 📊 ESTADO DE VERIFICACIÓN DE PERMISOS

### ✅ Recursos con Verificación Completa

| Recurso | Policy | canViewAny() | Estado |
|---------|--------|--------------|--------|
| Users | ✅ UserPolicy | ✅ Solo Admin | ✅ Completo |
| Roles | ✅ RolePolicy | ✅ Solo Admin | ✅ Completo |
| Papers | - | ✅ Solo Admin/Manager | ✅ Completo |
| PrintingMachines | - | ✅ Solo Admin/Manager | ✅ Completo |
| Finishings | - | ✅ Solo Admin/Manager | ✅ Completo |
| CollectionAccounts | - | ✅ Solo Admin/Manager | ✅ Completo |
| SocialPosts (Widget) | ✅ SocialPostPolicy | ✅ canView() en widget | ✅ Completo |

### ⚠️ Recursos con Verificación Parcial

| Recurso | Policy | canViewAny() | Estado |
|---------|--------|--------------|--------|
| Documents | ✅ DocumentPolicy | ❌ Falta | ⚠️ Parcial |
| Contacts | ✅ ContactPolicy | ❌ Falta | ⚠️ Parcial |
| Products | ✅ ProductPolicy | ❌ Falta | ⚠️ Parcial |
| SimpleItems | ✅ SimpleItemPolicy | ❌ Falta | ⚠️ Parcial |
| PurchaseOrders | ✅ PurchaseOrderPolicy | ❌ Falta | ⚠️ Parcial |

### ❌ Recursos sin Verificación

| Recurso | Policy | canViewAny() | Estado |
|---------|--------|--------------|--------|
| ProductionOrders | ❌ Sin Policy | ❌ Sin verificación | ❌ Sin protección |

---

## 🔍 CARACTERÍSTICAS TÉCNICAS CLAVE

### 1. Multi-Tenancy
- **Trait:** `BelongsToTenant`
- **Scope:** `TenantScope` (automático)
- **Aislamiento:** Por `company_id`
- **Modelos afectados:** ~90% de los modelos

### 2. Sistema de Cálculo de Precios

#### Nuevo Sistema de Montaje con Divisor (Sprint 13)
```
Trabajo 22×28 → Máquina 50×35 → Montaje: 2 copias
Divisor: 50×35 en pliego 100×70 → 4 cortes
Impresiones: 1000 ÷ 2 = 500
Pliegos: 500 ÷ 4 = 125
Millares: 500 ÷ 1000 = 0.5 → 1 millar
```

**Servicios involucrados:**
1. `MountingCalculatorService`: Montaje puro (copias por pliego)
2. `CuttingCalculatorService`: Divisor de cortes (pliego en máquina)
3. `SimpleItemCalculatorService`: Integración completa

#### Sistema de Acabados (Sprint 14)
- **Tabla pivot:** `simple_item_finishing`
- **Parámetros dinámicos:** quantity, width, height
- **Métodos:** `addFinishing()`, `calculateFinishingsCost()`, `getFinishingsBreakdown()`
- **Auto-construcción de parámetros** según tipo de medición

### 3. Sistema de Notificaciones (4 Tipos)

1. **Notificaciones Sociales** (SocialNotification)
   - Posts, comentarios, likes, seguimientos
   - Tabla: `social_notifications`

2. **Alertas de Inventario** (StockAlert)
   - Stock bajo, sin stock, próximo vencimiento
   - Tabla: `stock_alerts`
   - Servicio: `StockNotificationService`

3. **Sistema Avanzado** (NotificationChannel + Rule + Log)
   - Multi-canal: email, database, SMS, push, custom
   - Tablas: `notification_channels`, `notification_rules`, `notification_logs`
   - Servicio: `NotificationService`

4. **Laravel Notifications** (Notifications)
   - Sistema estándar de Laravel
   - Tabla: `notifications`
   - Modelo personalizado: `DatabaseNotification`

### 4. Arquitectura Polimórfica

#### DocumentItem (itemable_type)
- `App\Models\SimpleItem`
- `App\Models\Product`
- `App\Models\DigitalItem`
- `App\Models\TalonarioItem`
- `App\Models\MagazineItem`
- `App\Models\CustomItem`

#### StockMovement (stockable_type)
- `App\Models\Paper`
- `App\Models\Product`

#### StockAlert (stockable_type)
- `App\Models\Paper`
- `App\Models\Product`

#### Deadline (deadlinable_type)
- `App\Models\Document`
- `App\Models\PurchaseOrder`

### 5. Sistema de Órdenes Multi-Paper

**Arquitectura:**
- `PurchaseOrder` → `BelongsToMany` → `DocumentItem` (pivot)
- `PurchaseOrderItem` → Entity independiente para multi-paper
- Soporta revistas con varios papeles en una orden

**Flujo:**
1. Usuario selecciona DocumentItems para orden
2. Sistema crea PurchaseOrder
3. Por cada papel único en cada item:
   - Crea PurchaseOrderItem con `paper_id` específico
   - Permite múltiples rows por DocumentItem (revistas)

### 6. Sistema de Suscripciones

**Proveedores:**
- Laravel Cashier (Stripe) - Implementado
- PayU - Parcialmente implementado

**Planes:**
- `free` - Plan gratuito
- `basic` - Plan básico
- `professional` - Plan profesional
- `enterprise` - Plan empresarial (personalizable)

**Límites por Plan:**
- `max_users` - Usuarios máximos
- `max_documents` - Documentos por mes
- `features` - Features disponibles

---

## 📈 PRÓXIMAS TAREAS PRIORITARIAS

### 1. Completar Verificación de Permisos
- Agregar `canViewAny()` a:
  - Documents
  - Contacts
  - Products
  - SimpleItems
  - PurchaseOrders
- Crear `ProductionOrderPolicy`
- Agregar `canViewAny()` a ProductionOrderResource

### 2. Testing de Roles y Permisos
- Verificar que Salesperson solo vea sus recursos permitidos
- Verificar aislamiento multi-tenant
- Testing de políticas en widgets

### 3. Documentación Técnica
- Guía de uso del nuevo sistema de montaje
- Documentación de servicios de cálculo
- Guía de desarrollo de nuevos tipos de items

---

## 📚 REFERENCIAS DE CÓDIGO

### Traits Importantes

#### BelongsToTenant
```php
// app/Models/Concerns/BelongsToTenant.php
trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope(new TenantScope);
        
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

#### StockManagement
```php
// app/Models/Concerns/StockManagement.php
trait StockManagement
{
    public function addStock($quantity, $reason)
    public function removeStock($quantity, $reason)
    public function isLowStock(): bool
    public function isCriticalStock(): bool
    public function stockMovements(): MorphMany
}
```

### DTOs de Cálculo

```php
// SimpleItemCalculatorService retorna:
PricingResult {
    mountingOption: MountingOption
    printingCalculation: PrintingCalculation
    additionalCosts: AdditionalCosts
    subtotal: float
    profitMargin: float
    finalPrice: float
    unitPrice: float
    costBreakdown: array
}
```

---

## 🔧 CONFIGURACIÓN DEL PROYECTO

### Stack Tecnológico
- **Backend:** Laravel 12.25.0
- **PHP:** 8.3.21
- **Admin Panel:** Filament 4.0.3
- **Frontend:** Livewire 3.6.4 + TailwindCSS 4.1.12
- **Base de Datos:** MySQL
- **Autenticación:** Spatie Permission
- **Suscripciones:** Laravel Cashier (Stripe)

### Comandos Principales
```bash
php artisan test                    # Testing completo
php artisan pint && composer analyse    # Lint + análisis
php artisan migrate && php artisan db:seed  # Setup BD
php artisan litopro:setup-demo --fresh     # Demo completo
php artisan serve --port=8000      # Servidor de desarrollo
```

---

**Fin del Inventario Completo**

Este documento es un mapa completo del proyecto LitoPro 3.0 y debe actualizarse con cada cambio significativo en la arquitectura.
