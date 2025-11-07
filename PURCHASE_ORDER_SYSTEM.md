# Sistema de Órdenes de Pedido (Purchase Orders) - LitoPro 3.0

## Visión General

El sistema de órdenes de pedido (Purchase Orders) es un módulo que permite crear solicitudes de compra de materiales (papeles, productos, etc.) a proveedores desde las cotizaciones del sistema.

**Flujo General:**
```
Cotización (Document) con Items
    ↓
Seleccionar Items → Crear Órdenes de Pedido (agrupa por proveedor)
    ↓
PurchaseOrder creado
    ↓
Notificación enviada (Proveedor + Usuarios internos)
```

---

## 1. MODELOS

### 1.1 PurchaseOrder (Modelo Principal)

**Ubicación**: `/home/dasiva/Descargas/litopro825/app/Models/PurchaseOrder.php`

**Tabla**: `purchase_orders`

**Campos Principales**:
```php
$fillable = [
    'company_id',                    // Empresa que crea la orden (tenant)
    'supplier_company_id',          // Empresa proveedor
    'order_number',                 // Número generado automático (OP-YYYY-NNNN)
    'status',                       // OrderStatus enum
    'order_date',                   // Fecha de creación
    'expected_delivery_date',       // Fecha entrega estimada
    'actual_delivery_date',         // Fecha entrega real
    'total_amount',                 // Total calculado
    'notes',                        // Notas adicionales
    'created_by',                   // User ID que creó
    'approved_by',                  // User ID que aprobó
    'approved_at',                  // Timestamp de aprobación
];
```

**Relaciones**:
```php
company()                          // Empresa que crea (BelongsTo)
supplierCompany()                  // Empresa proveedor (BelongsTo)
createdBy()                        // Usuario creador (BelongsTo)
approvedBy()                       // Usuario aprobador (BelongsTo)
documentItems()                    // Items de cotización (BelongsToMany)
statusHistories()                  // Historial de cambios (HasMany)
purchaseOrderItems()               // Items de la orden directamente (HasMany)
documents()                        // Cotizaciones relacionadas
```

**Ciclo de Vida Model Hooks** (línea 46-136):

#### ✅ Hook `creating` (línea 49-61)
Se ejecuta ANTES de crear:
- Asigna `company_id` del usuario autenticado (multi-tenant)
- Genera número único de orden (formato: `OP-2025-0001`)
- Asigna `created_by` del usuario actual

#### ✅ Hook `created` (línea 63-80)
Se ejecuta DESPUÉS de crear:
1. **Crear registro de historial inicial**:
   ```php
   $order->statusHistories()->create([
       'from_status' => null,
       'to_status' => $order->status,
       'user_id' => auth()->id(),
   ]);
   ```

2. **Notificar a proveedor si status = 'sent'**:
   ```php
   if ($order->status === OrderStatus::SENT && $order->supplierCompany && $order->supplierCompany->email) {
       Notification::route('mail', $order->supplierCompany->email)
           ->notify(new PurchaseOrderCreated($order->id));
   }
   ```

3. **Notificar a usuarios de empresa creadora**:
   ```php
   $companyUsers = User::forTenant($order->company_id)->get();
   Notification::send($companyUsers, new PurchaseOrderCreated($order->id));
   ```

#### ⚠️ Hook `updating` (línea 82-135)
Se ejecuta ANTES de actualizar:
- Detecta si el campo `status` cambió (línea 84)
- Dentro de `static::updated()` (se ejecuta DESPUÉS):
  - Crea registro de historial (línea 91-95)
  - Notifica a usuarios de empresa creadora (línea 98-103)
  - Si cambia a 'sent': Notifica a usuarios del proveedor (línea 106-118)
  - Si cambia a 'confirmed' o 'received': Notifica al cliente por email (línea 121-132)

---

### 1.2 PurchaseOrderItem (Pivot Entity)

**Ubicación**: `/home/dasiva/Descargas/litopro825/app/Models/PurchaseOrderItem.php`

**Tabla**: `document_item_purchase_order` (pivot)

**Campo table**: `protected $table = 'document_item_purchase_order';`

**Campos Principales**:
```php
$fillable = [
    'document_item_id',             // Referencia a DocumentItem
    'purchase_order_id',            // Referencia a PurchaseOrder
    'paper_id',                     // Papel específico (si aplica)
    'paper_description',            // Descripción del papel
    'quantity_ordered',             // Cantidad ordenada
    'sheets_quantity',              // Cantidad de pliegos
    'cut_width',                    // Ancho de corte
    'cut_height',                   // Alto de corte
    'unit_price',                   // Precio unitario
    'total_price',                  // Precio total
    'status',                       // Estado del item
    'notes',                        // Notas específicas
];
```

**Relaciones**:
```php
documentItem()         // DocumentItem (BelongsTo)
purchaseOrder()        // PurchaseOrder (BelongsTo)
paper()               // Paper (BelongsTo)
```

**Métodos Importantes**:
- `getPaperNameAttribute()` (línea 54-111): Obtiene nombre del papel con prioridades
  1. Si hay `paper_description` → usa esa
  2. Si hay relación `paper_id` → usa nombre del papel
  3. Si hay `documentItem.itemable` cargado → extrae información:
     - SimpleItem: Papel + tamaño
     - Product: Nombre del producto
     - TalonarioItem: Descripción del talonario
     - MagazineItem: Descripción de revista

- `getCutSizeAttribute()` (línea 116-123): Formatea tamaño de corte

---

## 2. EVENTOS Y NOTIFICACIONES

### 2.1 Evento: PurchaseOrderStatusChanged

**Ubicación**: `/home/dasiva/Descargas/litopro825/app/Events/PurchaseOrderStatusChanged.php`

**Propósito**: Se crea cuando cambia el estado de una orden de pedido

**Estructura**:
```php
class PurchaseOrderStatusChanged
{
    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public OrderStatus $oldStatus,
        public OrderStatus $newStatus
    ) {}
}
```

**Cómo se Dispara**:
- **NO se dispara explícitamente** en el modelo
- El evento está definido pero **no se usa actualmente**
- Las notificaciones se envían directamente en los hooks del modelo

**Status**: ⚠️ EVENTO DEFINIDO PERO NO UTILIZADO
- Hay listener vacío: `NotifyPurchaseOrderStatusChange` (línea 9-25)
- Las notificaciones se manejan directamente en PurchaseOrder hooks

---

### 2.2 Notificaciones

#### A) PurchaseOrderCreated

**Ubicación**: `/home/dasiva/Descargas/litopro825/app/Notifications/PurchaseOrderCreated.php`

**Se envía cuando**:
- Se crea una nueva orden de pedido (PurchaseOrder::created hook)
- Se cambia estado a 'sent' (línea 106-118 en PurchaseOrder.php)

**Receptores**:
1. Proveedor (por email) - si tiene email configurado
2. Usuarios de la empresa que crea la orden (notificación en app + email)
3. Usuarios del proveedor (si la orden se envía)

**Métodos**:
```php
public function via(object $notifiable): array {
    return ['mail', 'database'];  // Email + Notificación en app
}

public function toMail(object $notifiable): MailMessage {
    // Cuerpo del email con PDF adjunto
    // Plantilla: emails.purchase-order.created
    // Adjunto: PDF de la orden
}

public function toArray(object $notifiable): array {
    // Notificación en app (base de datos)
    // Campos: purchase_order_id, order_number, supplier_company, total_amount, message
}
```

---

#### B) PurchaseOrderStatusChanged

**Ubicación**: `/home/dasiva/Descargas/litopro825/app/Notifications/PurchaseOrderStatusChanged.php`

**Se envía cuando**:
- El estado de una orden cambia (PurchaseOrder::updating hook)

**Receptores**:
1. Usuarios de la empresa creadora (siempre)
2. Empresa cliente por email (si cambia a 'confirmed' o 'received')

**Métodos**:
```php
public function via(object $notifiable): array {
    return ['mail', 'database'];  // Email + Notificación en app
}

public function toMail(object $notifiable): MailMessage {
    // Plantilla: emails.purchase-order.status-changed
    // Incluye: orden, estado anterior, estado nuevo
}

public function toArray(object $notifiable): array {
    // Notificación en app
    // Campos: purchase_order_id, order_number, old_status, new_status
}
```

---

## 3. FLUJO DE CREACIÓN DE ÓRDENES

### 3.1 Punto de Entrada: DocumentsTable

**Ubicación**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/Documents/Tables/DocumentsTable.php`

**Acción**: `create_purchase_orders` (línea 245-529)

**Visibilidad**: Línea 249
```php
->visible(fn ($record) => $record->canCreateOrders())
```

**Pasos del Proceso**:

#### Paso 1: Seleccionar Items (Formulario - línea 250-324)
```
Sección: "Seleccionar Items para Orden de Pedido"
├── CheckboxList: selected_items
│   ├── Opciones: Items disponibles de la cotización
│   ├── Descripción detallada según tipo:
│   │   ├── SimpleItem: Papel + tamaño
│   │   ├── MagazineItem: Revista + pliegos
│   │   └── Product: Producto + cantidad
│   └── Sub-descripción: Cantidad + Proveedor + Costo estimado
└── Textarea: notes (notas adicionales)
```

#### Paso 2: Procesar Items (línea 326-401)
1. **Obtener items seleccionados** con relaciones (línea 328-345)
2. **Agrupar por proveedor y tipo** (línea 347-386)
   - Determina tipo: 'papel' o 'producto'
   - Obtiene supplier_id según tipo de item:
     - SimpleItem → `paper.company_id`
     - MagazineItem → `getMainPaperSupplier()`
     - TalonarioItem → `getMainPaperSupplier()`
     - Product → `company_id`
   - Clave: `{tipo}_{supplierId}`

#### Paso 3: Crear Órdenes (línea 388-530)
Para cada grupo (proveedor + tipo):

**A) Crear PurchaseOrder** (línea 393-400)
```php
$order = PurchaseOrder::create([
    'company_id' => auth()->user()->company_id,
    'supplier_company_id' => $supplierId,
    'order_date' => now(),
    'expected_delivery_date' => now()->addDays(7),
    'status' => 'draft',
    'notes' => $data['notes'] ?? null,
]);
```

↓ **Dispara PurchaseOrder::created hook**

**B) Agregar Items según tipo** (línea 403-529)

**Para MagazineItem** (línea 404-441):
- Obtiene papeles usados: `magazine->getPapersUsed()`
- **Crea UNA FILA POR CADA TIPO DE PAPEL**
- Para cada papel:
  ```php
  $order->documentItems()->attach($item->id, [
      'paper_id' => $paperId,
      'paper_description' => "{$paper->name} - Revista: {$magazine->description}",
      'quantity_ordered' => $item->quantity,
      'sheets_quantity' => $sheets,
      'cut_width' => $cutWidth,
      'cut_height' => $cutHeight,
      'unit_price' => $paper->cost_per_sheet,
      'total_price' => $sheets * $unitPrice,
      'status' => 'pending',
  ]);
  ```
- Actualiza estado del item: `$item->updateOrderStatus()` (línea 444)

**Para TalonarioItem** (línea 446-487):
- Obtiene papeles usados: `talonario->getPapersUsed()`
- **Crea UNA FILA POR CADA TIPO DE PAPEL**
- Idem a MagazineItem

**Para SimpleItem, Product, etc.** (línea 489-529):
- **Crea UNA SOLA FILA POR ITEM**
- Extrae información:
  - SimpleItem: Paper + mounting_quantity + tamaño
  - Product: sale_price + quantity
  - Otros: unit_price + quantity
- Attach a la orden con pivot data
- Actualiza estado del item: `$item->updateOrderStatus()`

---

## 4. SISTEMA DE NOTIFICACIONES EN DETALLE

### 4.1 Flujo de Notificaciones al Crear Orden

```
PurchaseOrder::create() con status = 'draft'
    ↓
PurchaseOrder::created hook ejecuta:
    ├─→ 1. Crear OrderStatusHistory
    │      └─ from: null → to: 'draft'
    │
    ├─→ 2. SI status === 'sent'
    │      ├─ Enviar email a proveedor
    │      │  └─ PurchaseOrderCreated notification
    │      └─ Notificar usuarios del proveedor
    │
    └─→ 3. SIEMPRE notificar usuarios de empresa creadora
           └─ PurchaseOrderCreated notification
              └─ Via: ['mail', 'database']
```

**Código de Creación** (PurchaseOrder.php línea 63-80):
```php
static::created(function (PurchaseOrder $order) {
    // 1. Crear historial
    $order->statusHistories()->create([
        'from_status' => null,
        'to_status' => $order->status,
        'user_id' => auth()->id(),
    ]);

    // 2. Si enviada a proveedor, notificarlo
    if ($order->status === OrderStatus::SENT && $order->supplierCompany && $order->supplierCompany->email) {
        Notification::route('mail', $order->supplierCompany->email)
            ->notify(new PurchaseOrderCreated($order->id));
    }

    // 3. Notificar usuarios internos
    $companyUsers = User::forTenant($order->company_id)->get();
    Notification::send($companyUsers, new PurchaseOrderCreated($order->id));
});
```

### 4.2 Flujo de Notificaciones al Cambiar Estado

```
PurchaseOrder->update(['status' => 'sent'])
    ↓
PurchaseOrder::updating hook detecta cambio en 'status'
    ↓
PurchaseOrder::updated hook ejecuta:
    ├─→ 1. Crear OrderStatusHistory
    │      └─ from: 'draft' → to: 'sent'
    │
    ├─→ 2. Notificar usuarios de empresa creadora
    │      └─ PurchaseOrderStatusChanged notification
    │
    ├─→ 3. SI newStatus === 'sent'
    │      ├─ Notificar usuarios del proveedor
    │      │  └─ PurchaseOrderCreated notification
    │      └─ Email a proveedor (si tiene email)
    │
    └─→ 4. SI newStatus EN ['confirmed', 'received']
           └─ Email a empresa cliente
              └─ PurchaseOrderStatusChanged notification
```

**Código de Cambio de Estado** (PurchaseOrder.php línea 82-135):
```php
static::updating(function (PurchaseOrder $order) {
    if ($order->isDirty('status')) {
        $oldStatus = $order->getOriginal('status');
        $newStatus = $order->status;

        static::updated(function (PurchaseOrder $updatedOrder) use ($oldStatus, $newStatus) {
            // Crear historial
            $updatedOrder->statusHistories()->create([...]);

            // Notificar usuarios internos
            Notification::send($companyUsers, new PurchaseOrderStatusChanged(...));

            // Si cambia a 'sent', notificar proveedor
            if ($newStatus === OrderStatus::SENT && $updatedOrder->supplierCompany) {
                $supplierUsers = User::where('company_id', $updatedOrder->supplier_company_id)->get();
                if ($supplierUsers->isNotEmpty()) {
                    Notification::send($supplierUsers, new PurchaseOrderCreated($updatedOrder->id));
                }
                
                // Email adicional
                if ($updatedOrder->supplierCompany->email) {
                    Notification::route('mail', $updatedOrder->supplierCompany->email)
                        ->notify(new PurchaseOrderCreated($updatedOrder->id));
                }
            }

            // Si confirma o recibe, email al cliente
            if (in_array($newStatus, [OrderStatus::CONFIRMED, OrderStatus::RECEIVED])) {
                $clientCompany = $updatedOrder->company;
                if ($clientCompany && $clientCompany->email) {
                    Notification::route('mail', $clientCompany->email)
                        ->notify(new PurchaseOrderStatusChanged(...));
                }
            }
        });
    }
});
```

---

## 5. ESTADO ACTUAL DE EVENTOS

### ✅ Eventos Definidos
- `PurchaseOrderStatusChanged` (línea 1-25)

### ⚠️ Listeners Vacíos (No Implementados)
- `NotifyPurchaseOrderStatusChange` (línea 1-26)
  - Tiene método `handle()` vacío
  - **El evento NO se dispara en el código**

### ❌ Listeners Faltantes
- No hay listener para `DocumentCreated`
- Las notificaciones se manejan **directamente en los hooks del modelo**

### 📝 Estrategia Actual
```
❌ NO USAR: Event Dispatching Pattern
✅ USA: Model Hooks (Eloquent Lifecycle)

PurchaseOrder::created, updated hooks
    → Envían notificaciones directamente
    → Crean registros de historial
```

---

## 6. INTERFAZ FILAMENT

### 6.1 Recurso: PurchaseOrderResource

**Ubicación**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/PurchaseOrderResource.php`

**Ubicación de Creación**:
- No tiene página CreateRecord propia
- Se crea desde DocumentResource (DocumentsTable action)

**Páginas**:
```php
'index'  => ListPurchaseOrders::route('/')
'create' => CreatePurchaseOrder::route('/create')  // Minimal
'view'   => ViewPurchaseOrder::route('/{record}')
'edit'   => EditPurchaseOrder::route('/{record}/edit')
```

**Query Builder** (línea 33-51):
```php
// Mostrar órdenes creadas POR la empresa O RECIBIDAS como proveedor
->where(function ($query) use ($companyId) {
    $query->where('purchase_orders.company_id', $companyId)
        ->orWhere('purchase_orders.supplier_company_id', $companyId);
})
```

### 6.2 Acciones en EditPurchaseOrder

**Ubicación**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Pages/EditPurchaseOrder.php`

**Acción: send_email** (línea 17-50)
- Label: "Enviar por Email"
- Formulario: Email del proveedor (pre-cargado)
- Acción: Genera PDF y envía por email
- Usa: `PurchaseOrderPdfService`

### 6.3 Relaciones en PurchaseOrderResource

- `PurchaseOrderItemsRelationManager` (línea 66)
  - Muestra los items de la orden
  - Permite editar/eliminar items

---

## 7. CAMPOS DE CONFIGURACIÓN

### 7.1 Enum: OrderStatus

**Ubicación**: `/home/dasiva/Descargas/litopro825/app/Enums/OrderStatus.php`

Estados disponibles:
- `DRAFT` - Borrador
- `SENT` - Enviada a proveedor
- `CONFIRMED` - Confirmada por proveedor
- `RECEIVED` - Recibida
- `PARTIALLY_RECEIVED` - Parcialmente recibida
- `CANCELLED` - Cancelada

---

## 8. TABLA DE HISTORIAL

### OrderStatusHistory

**Ubicación**: `/home/dasiva/Descargas/litopro825/app/Models/OrderStatusHistory.php`

**Campos**:
- `purchase_order_id` - Referencia a orden
- `from_status` - Estado anterior
- `to_status` - Estado nuevo
- `user_id` - Usuario que cambió
- `notes` - Notas del cambio
- `created_at` - Timestamp

---

## 9. FLUJO VISUAL DE NOTIFICACIONES

```
═══════════════════════════════════════════════════════════════
                    CREAR ORDEN DE PEDIDO
═══════════════════════════════════════════════════════════════

1. Usuario en Cotización → Acción "Crear Órdenes de Pedido"
                            ↓
2. Filament Action       → Formulario (seleccionar items + notas)
                            ↓
3. Action Handler        → $order = PurchaseOrder::create(...)
                            ↓
4. Model Hook created    → Enviar notificaciones
   ├─ PurchaseOrderCreated → Usuarios de empresa creadora
   └─ Si status='sent'    → Email a proveedor
                            ↓
5. Notificaciones        → Via ['mail', 'database']
   ├─ Email              → Adjunta PDF de orden
   └─ Base de datos      → Stored notification


═══════════════════════════════════════════════════════════════
                    CAMBIAR ESTADO DE ORDEN
═══════════════════════════════════════════════════════════════

1. Usuario en EditPurchaseOrder → Cambiar status en formulario
                                   ↓
2. Model Hook updating          → Detecta cambio de status
                                   ↓
3. Model Hook updated           → Enviar notificaciones
   ├─ PurchaseOrderStatusChanged → Usuarios de empresa creadora
   ├─ Si newStatus='sent'        → Notificar proveedor
   └─ Si newStatus='confirmed'   → Email a empresa cliente
      o 'received'
                                   ↓
4. Notificaciones               → Via ['mail', 'database']
```

---

## 10. ARCHIVOS IMPLICADOS

### Modelos
- `/home/dasiva/Descargas/litopro825/app/Models/PurchaseOrder.php` (269 líneas)
- `/home/dasiva/Descargas/litopro825/app/Models/PurchaseOrderItem.php` (125 líneas)
- `/home/dasiva/Descargas/litopro825/app/Models/OrderStatusHistory.php`

### Eventos & Listeners
- `/home/dasiva/Descargas/litopro825/app/Events/PurchaseOrderStatusChanged.php` (25 líneas)
- `/home/dasiva/Descargas/litopro825/app/Listeners/NotifyPurchaseOrderStatusChange.php` (26 líneas - vacío)

### Notificaciones
- `/home/dasiva/Descargas/litopro825/app/Notifications/PurchaseOrderCreated.php` (59 líneas)
- `/home/dasiva/Descargas/litopro825/app/Notifications/PurchaseOrderStatusChanged.php` (73 líneas)

### Filament Resources
- `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/PurchaseOrderResource.php` (80 líneas)
- `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Pages/CreatePurchaseOrder.php`
- `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Pages/EditPurchaseOrder.php` (55 líneas)
- `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Pages/ListPurchaseOrders.php`
- `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php`
- `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderForm.php`
- `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Tables/PurchaseOrdersTable.php`
- `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/RelationManagers/PurchaseOrderItemsRelationManager.php`

### Documentos (donde se crean órdenes)
- `/home/dasiva/Descargas/litopro825/app/Filament/Resources/Documents/Tables/DocumentsTable.php` (línea 245-529)
- `/home/dasiva/Descargas/litopro825/app/Filament/Resources/Documents/DocumentResource.php`

### Servicios
- `/home/dasiva/Descargas/litopro825/app/Services/PurchaseOrderPdfService.php` (genera PDFs)

---

## 11. MAPEO LÍNEA POR LÍNEA

### PurchaseOrder.php - Hooks de Notificación

| Línea | Función | Descripción |
|-------|---------|-------------|
| 46-136 | booted() | Ciclo de vida del modelo |
| 49-61 | creating | Asigna company_id, order_number, created_by |
| 63-80 | created | **Envía notificaciones al crear** |
| 72-75 | created | Email a proveedor si status='sent' |
| 78-79 | created | Notifica usuarios internos |
| 82-135 | updating | Detecta cambios de status |
| 89-133 | updated (anidado) | **Envía notificaciones al cambiar status** |
| 91-95 | updated | Crear historial de cambio |
| 98-103 | updated | Notificar usuarios internos |
| 106-118 | updated | Si status='sent', notificar proveedor |
| 121-132 | updated | Si status='confirmed'/'received', email cliente |

### DocumentsTable.php - Creación de Órdenes

| Línea | Función | Descripción |
|-------|---------|-------------|
| 245-529 | create_purchase_orders action | Acción para crear órdenes |
| 249 | visible() | Solo muestra si `canCreateOrders()` |
| 250-324 | form() | Formulario de selección de items |
| 256-279 | opciones | Lista de items disponibles |
| 326-529 | action handler | Lógica de creación |
| 328-345 | obtener items | Carga items y relaciones |
| 347-386 | agrupar items | Agrupa por proveedor y tipo |
| 393-400 | crear orden | `PurchaseOrder::create()` |
| 404-441 | MagazineItem | Crea múltiples filas por papel |
| 446-487 | TalonarioItem | Crea múltiples filas por papel |
| 490-529 | Otros items | Crea una fila por item |

### Notificaciones

#### PurchaseOrderCreated.php
| Línea | Método | Descripción |
|-------|--------|-------------|
| 25-28 | via() | ['mail', 'database'] |
| 30-44 | toMail() | Email con PDF adjunto |
| 46-57 | toArray() | Notificación en app |

#### PurchaseOrderStatusChanged.php
| Línea | Método | Descripción |
|-------|--------|-------------|
| 37-40 | via() | ['mail', 'database'] |
| 42-57 | toMail() | Email de cambio de estado |
| 59-72 | toArray() | Notificación en app |

---

## 12. DATOS DE EJEMPLO

### Crear Orden Completa

```php
// 1. Desde acción en Filament (DocumentsTable)
$selectedItems = [1, 2, 3];  // DocumentItem IDs
$notes = "Orden urgente";

// 2. Se crea automáticamente
$order = PurchaseOrder::create([
    'company_id' => 1,
    'supplier_company_id' => 5,
    'order_date' => now(),
    'expected_delivery_date' => now()->addDays(7),
    'status' => 'draft',
    'notes' => 'Orden urgente',
]);
// ↓ Ejecuta PurchaseOrder::created hook

// 3. Notificaciones enviadas
// - PurchaseOrderCreated → usuarios internos (mail + database)

// 4. Cambiar a enviada
$order->status = 'sent';
$order->save();
// ↓ Ejecuta PurchaseOrder::updated hook

// 5. Más notificaciones
// - PurchaseOrderStatusChanged → usuarios internos
// - PurchaseOrderCreated → proveedor (email + app)
// - PurchaseOrderCreated → email a supplier@company.com
```

---

## 13. NOTA IMPORTANTE: Event Pattern No Implementado

**Situación Actual**:
- El evento `PurchaseOrderStatusChanged` está definido ✅
- El listener `NotifyPurchaseOrderStatusChange` existe pero está vacío ❌
- **Las notificaciones se envían directamente en los hooks del modelo** ✅

**Por qué funciona así**:
- Más simple y directo (menos complejidad)
- El listener podría usarse en el futuro para procesamiento asíncrono
- El evento no se dispara con `dispatch()` en ningún lado

**Si se quisiera refactorizar** a event pattern:
```php
// En lugar de notificar directamente en hook:
static::updated(function (PurchaseOrder $updatedOrder) {
    PurchaseOrderStatusChanged::dispatch($updatedOrder, $oldStatus, $newStatus);
});

// En NotifyPurchaseOrderStatusChange::handle():
public function handle(PurchaseOrderStatusChanged $event): void {
    // Lógica de notificación aquí
    Notification::send(...);
}
```

---

## 14. CONCLUSIÓN

El sistema de órdenes de pedido en LitoPro:

1. **Usa Model Hooks** para capturar eventos de creación y actualización
2. **Envía notificaciones directamente** al proveedor y usuarios internos
3. **Multi-tenant**: Aisla órdenes por `company_id`
4. **Complejo**: Maneja diferentes tipos de items (SimpleItem, MagazineItem, Product, etc.)
5. **Auditable**: Crea historial de cambios en `OrderStatusHistory`
6. **Flexible**: Agrupa órdenes automáticamente por proveedor

Las notificaciones se envían SIEMPRE via `['mail', 'database']`, permitiendo:
- Email a proveedores y clientes
- Notificaciones persistidas en base de datos
- Historial completo de cambios

