# Quick Reference: Sistema de Órdenes de Pedido

## Resumen Ejecutivo (2 minutos)

### ¿Qué es?
Sistema que permite crear **órdenes de compra** a proveedores desde las **cotizaciones**.

### ¿Cómo funciona?
1. **Usuario abre cotización** → Selecciona "Crear Órdenes de Pedido"
2. **Selecciona items** a ordenar (agrupa automáticamente por proveedor)
3. **Sistema crea PurchaseOrder** → Se disparan notificaciones automáticas
4. **Usuario puede cambiar estado** → Se envían más notificaciones

### Archivos Clave (10 archivos)

| Archivo | Líneas | Función |
|---------|--------|---------|
| PurchaseOrder.php | 269 | Modelo principal + hooks de notificación |
| DocumentsTable.php | 529 | Acción para crear órdenes (línea 245-529) |
| PurchaseOrderCreated.php | 59 | Notificación al crear/enviar orden |
| PurchaseOrderStatusChanged.php | 73 | Notificación al cambiar estado |
| PurchaseOrderResource.php | 80 | Interfaz Filament |
| EditPurchaseOrder.php | 55 | Edición y acción "Enviar por Email" |
| PurchaseOrderItem.php | 125 | Pivot entity (documento + orden) |
| OrderStatusHistory.php | - | Historial de cambios |
| PurchaseOrderStatusChanged.php (Event) | 25 | Evento (no usado actualmente) |
| NotifyPurchaseOrderStatusChange.php | 26 | Listener vacío |

---

## Flujo de Notificaciones (Visual)

### Crear Orden (PurchaseOrder::create)
```
DocumentsTable Action: "Crear Órdenes de Pedido"
    ↓
PurchaseOrder::create([status => 'draft'])
    ↓
✅ Hook: creating
   └─ Asigna company_id, order_number, created_by

    ↓
✅ Hook: created
   ├─ Crear OrderStatusHistory (null → 'draft')
   ├─ Si status='sent': Email a proveedor ✉️
   └─ Notificar usuarios internos ✉️ + 📱
```

### Cambiar Estado (PurchaseOrder->update)
```
EditPurchaseOrder: Cambiar status
    ↓
PurchaseOrder->update(['status' => 'sent'])
    ↓
✅ Hook: updating
   └─ Detecta cambio de status

    ↓
✅ Hook: updated (anidado en updating)
   ├─ Crear OrderStatusHistory ('draft' → 'sent')
   ├─ Notificar usuarios internos ✉️ + 📱
   ├─ Si newStatus='sent':
   │  ├─ Notificar usuarios del proveedor ✉️ + 📱
   │  └─ Email adicional a proveedor@company.com ✉️
   └─ Si newStatus='confirmed'/'received':
      └─ Email a empresa cliente ✉️
```

---

## Notificaciones: ¿A Quién Se Envía?

### PurchaseOrderCreated (Notificación)
| Evento | Receptor | Via | Plantilla |
|--------|----------|-----|-----------|
| Crear orden con status='draft' | Usuarios internos | mail + database | emails.purchase-order.created |
| Crear orden con status='sent' | Proveedor (email) | mail | emails.purchase-order.created |
| Crear orden con status='sent' | Usuarios proveedor | mail + database | emails.purchase-order.created |
| Cambiar a status='sent' | Usuarios proveedor | mail + database | emails.purchase-order.created |
| Cambiar a status='sent' | Proveedor (email) | mail | emails.purchase-order.created |

### PurchaseOrderStatusChanged (Notificación)
| Evento | Receptor | Via | Plantilla |
|--------|----------|-----|-----------|
| Cambiar estado | Usuarios internos | mail + database | emails.purchase-order.status-changed |
| Cambiar a 'confirmed'/'received' | Empresa cliente | mail | emails.purchase-order.status-changed |

---

## Puntos de Código Críticos

### 1. Crear Orden desde Cotización
**Archivo**: `app/Filament/Resources/Documents/Tables/DocumentsTable.php`

**Líneas**: 245-529

**Punto de entrada**: Acción `create_purchase_orders`

```php
Action::make('create_purchase_orders')
    ->label('Crear Órdenes de Pedido')
    ->action(function ($record, array $data) {
        // Línea 328-345: Obtener items seleccionados
        // Línea 347-386: Agrupar por proveedor
        // Línea 393: $order = PurchaseOrder::create([...])
        // Línea 404-529: Agregar items a la orden
    })
```

### 2. Notificación al Crear
**Archivo**: `app/Models/PurchaseOrder.php`

**Líneas**: 63-80

```php
static::created(function (PurchaseOrder $order) {
    // Línea 65-69: Crear historial
    // Línea 72-75: Email a proveedor si status='sent'
    // Línea 78-79: Notificar usuarios internos
})
```

### 3. Notificación al Cambiar Estado
**Archivo**: `app/Models/PurchaseOrder.php`

**Líneas**: 82-135

```php
static::updating(function (PurchaseOrder $order) {
    if ($order->isDirty('status')) {
        static::updated(function (PurchaseOrder $updatedOrder) {
            // Línea 91-95: Crear historial
            // Línea 98-103: Notificar usuarios internos
            // Línea 106-118: Si newStatus='sent', notificar proveedor
            // Línea 121-132: Si newStatus='confirmed'/'received', email cliente
        });
    }
});
```

### 4. Cuerpo de Notificaciones
**Archivo**: `app/Notifications/PurchaseOrderCreated.php`

```php
// Línea 25-28: Canales: ['mail', 'database']
// Línea 30-44: toMail() - Adjunta PDF
// Línea 46-57: toArray() - Datos para base de datos
```

---

## Estados de Órdenes (OrderStatus Enum)

```php
DRAFT              // Borrador (creación inicial)
SENT               // Enviada a proveedor
CONFIRMED          // Confirmada por proveedor
RECEIVED           // Completamente recibida
PARTIALLY_RECEIVED // Parcialmente recibida
CANCELLED          // Cancelada
```

---

## Tabla Pivot: document_item_purchase_order

Esta tabla conecta **DocumentItems** con **PurchaseOrder**.

**Campos importantes**:
```
document_item_id       // Referencia a item de cotización
purchase_order_id      // Referencia a orden
paper_id              // Papel específico (cuando aplica)
paper_description     // Descripción del papel
quantity_ordered      // Cantidad ordenada
sheets_quantity       // Pliegos (para cálculo de papel)
cut_width             // Ancho de corte
cut_height            // Alto de corte
unit_price            // Precio unitario
total_price           // Precio total
status                // Estado del item (pending, received, etc.)
```

**Nota**: Para MagazineItem y TalonarioItem, se crea **UNA FILA POR CADA TIPO DE PAPEL**.

---

## Métodos Importantes

### PurchaseOrder Model

| Método | Línea | Función |
|--------|-------|---------|
| generateOrderNumber() | 207 | Genera OP-YYYY-NNNN |
| changeStatus() | 241 | Cambiar estado con validación |
| isPending() | 255 | ¿Está en estado pendiente? |
| canBeApproved() | 260 | ¿Puede ser aprobada? |
| canBeCancelled() | 265 | ¿Puede ser cancelada? |
| recalculateTotal() | 197 | Recalcula total_amount |

### PurchaseOrderItem Model

| Método | Línea | Función |
|--------|-------|---------|
| getPaperNameAttribute() | 54 | Obtiene nombre del papel |
| getCutSizeAttribute() | 116 | Formatea tamaño de corte |

---

## Eventos y Listeners

### Event: PurchaseOrderStatusChanged
**Ubicación**: `app/Events/PurchaseOrderStatusChanged.php`

**Estado**: ⚠️ DEFINIDO PERO NO USADO

**Por qué**: Las notificaciones se envían directamente en los hooks del modelo (crear/actualizar)

```php
class PurchaseOrderStatusChanged {
    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public OrderStatus $oldStatus,
        public OrderStatus $newStatus
    ) {}
}
```

### Listener: NotifyPurchaseOrderStatusChange
**Ubicación**: `app/Listeners/NotifyPurchaseOrderStatusChange.php`

**Estado**: ❌ VACÍO (no implementado)

```php
public function handle(PurchaseOrderStatusChanged $event): void {
    // No hace nada actualmente
}
```

---

## Tabla: OrderStatusHistory

Registra cada cambio de estado de una orden.

**Campos**:
- purchase_order_id
- from_status
- to_status
- user_id (quién hizo el cambio)
- notes (notas opcionales)
- created_at (cuándo)

**Creado automáticamente** en:
- `PurchaseOrder::created` (línea 65-69)
- `PurchaseOrder::updated` (línea 91-95)

---

## Multi-Tenancy (company_id)

La orden siempre se asigna a la empresa del usuario autenticado:

```php
// En PurchaseOrder::creating (línea 50-51)
$order->company_id = auth()->user()->company_id
```

**Query en PurchaseOrderResource** (línea 33-51):
```php
// Mostrar órdenes creadas POR la empresa O RECIBIDAS como proveedor
->where(function ($query) use ($companyId) {
    $query->where('purchase_orders.company_id', $companyId)
        ->orWhere('purchase_orders.supplier_company_id', $companyId);
})
```

---

## Acciones Filament Disponibles

### En DocumentsTable (línea 245)
**Acción**: `create_purchase_orders`
- **Visible si**: `$record->canCreateOrders()` retorna true
- **Resultado**: Crea múltiples órdenes agrupadas por proveedor

### En EditPurchaseOrder (línea 17)
**Acción**: `send_email`
- **Usa**: `PurchaseOrderPdfService`
- **Resultado**: Genera PDF y envía por email

---

## Ejemplos de Código

### Crear una orden manualmente
```php
$order = PurchaseOrder::create([
    'company_id' => 1,
    'supplier_company_id' => 5,
    'order_date' => now(),
    'expected_delivery_date' => now()->addDays(7),
    'status' => OrderStatus::DRAFT,
    'notes' => 'Orden urgente',
]);
// ↓ Se dispara PurchaseOrder::created hook
// ↓ Se envían notificaciones automáticamente
```

### Agregar items a una orden
```php
// Para SimpleItem/Product
$order->documentItems()->attach($documentItem->id, [
    'paper_id' => $paper?->id,
    'quantity_ordered' => $quantity,
    'sheets_quantity' => $sheets,
    'unit_price' => $unitPrice,
    'total_price' => $totalPrice,
    'status' => 'pending',
]);

// Para MagazineItem (múltiples papeles)
foreach ($magazine->getPapersUsed() as $paperId => $paperData) {
    $order->documentItems()->attach($item->id, [
        'paper_id' => $paperId,
        'paper_description' => $paperData['paper']->name,
        'sheets_quantity' => $paperData['total_sheets'],
        ...
    ]);
}
```

### Cambiar estado
```php
$order->status = OrderStatus::SENT;
$order->save();
// ↓ Se dispara PurchaseOrder::updated hook
// ↓ Se envían notificaciones según newStatus
```

### Obtener órdenes de una cotización
```php
$document = Document::find(1);
$orders = $document->purchaseOrders(); // Si existe relación
// O acceder a través de items:
$orders = PurchaseOrder::whereHas('documentItems', 
    fn($q) => $q->where('document_id', $document->id)
)->get();
```

---

## Plantillas de Email

### emails.purchase-order.created
Enviado al crear o cambiar estado a 'sent'

**Variables disponibles**:
- `$purchaseOrder` - Objeto completo
- Adjunto: PDF de la orden

### emails.purchase-order.status-changed
Enviado al cambiar estado

**Variables disponibles**:
- `$purchaseOrder`
- `$oldStatus`, `$newStatus`
- `$oldStatusLabel`, `$newStatusLabel`

---

## Servicios Relacionados

### PurchaseOrderPdfService
**Ubicación**: `app/Services/PurchaseOrderPdfService.php`

**Métodos principales**:
- `generatePdf($purchaseOrder)` - Genera PDF
- `emailPdf($purchaseOrder, $emailArray)` - Genera y envía por email

---

## Relaciones en PurchaseOrder

```php
company()                   // BelongsTo - Empresa creadora
supplierCompany()          // BelongsTo - Empresa proveedor
createdBy()                // BelongsTo - User creador
approvedBy()               // BelongsTo - User aprobador
documentItems()            // BelongsToMany - Items de cotización
statusHistories()          // HasMany - Historial de cambios
purchaseOrderItems()       // HasMany - Items directamente (pivot)
documents()                // Cotizaciones relacionadas (custom)
```

---

## Relaciones en PurchaseOrderItem

```php
documentItem()             // BelongsTo - DocumentItem
purchaseOrder()            // BelongsTo - PurchaseOrder
paper()                    // BelongsTo - Paper
```

---

## Checklist: ¿Cómo Verificar que Funciona?

- [ ] Crear cotización con items
- [ ] Ir a Cotizaciones → seleccionar una → acción "Crear Órdenes de Pedido"
- [ ] Seleccionar items → crear orden
- [ ] Verificar que se crean órdenes agrupadas por proveedor
- [ ] Verificar que se reciben notificaciones en base de datos (`notifications` table)
- [ ] Cambiar estado de orden (draft → sent)
- [ ] Verificar que se crean más notificaciones
- [ ] Verificar email enviado a proveedor (si tiene email)
- [ ] Verificar OrderStatusHistory (cada cambio registrado)

---

## Notas Finales

1. **Las notificaciones se envían automáticamente** - No necesitas disponer eventos
2. **Es multi-tenant** - Solo ve órdenes de su empresa (o que recibe como proveedor)
3. **El evento PurchaseOrderStatusChanged está definido pero no se usa** - Las notificaciones se envían directamente en hooks
4. **Las órdenes se agrupan automáticamente** por proveedor y tipo de item
5. **MagazineItem y TalonarioItem generan múltiples filas** (una por cada tipo de papel)
6. **El historial se crea automáticamente** en cada cambio de estado

