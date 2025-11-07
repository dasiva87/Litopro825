# Índice de Archivos: Sistema de Órdenes de Pedido

## Resumen de Ubicaciones

Total de archivos implicados: **16 archivos principales + 2 eventos/listeners**

---

## Modelos (3 archivos)

### 1. PurchaseOrder.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Models/PurchaseOrder.php`
**Líneas**: 269
**Importancia**: CRÍTICA

**Hooks de Notificación**:
- `creating` (49-61): Asigna company_id, order_number, created_by
- `created` (63-80): Envía notificaciones al crear
  - Línea 65-69: Crear historial
  - Línea 72-75: Email a proveedor si status='sent'
  - Línea 78-79: Notificar usuarios internos
- `updating` (82-135): Detecta cambios de status
- `updated` (89-133): Envía notificaciones al cambiar estado
  - Línea 91-95: Crear historial
  - Línea 98-103: Notificar usuarios internos
  - Línea 106-118: Si status='sent', notificar proveedor
  - Línea 121-132: Si status='confirmed'/'received', email cliente

**Métodos Importantes**:
- `generateOrderNumber()` (207-239): Genera OP-YYYY-NNNN
- `changeStatus()` (241-253): Cambiar con validación
- `isPending()` (255-258): Estado pendiente?
- `canBeApproved()` (260-263): Puede aprobarse?
- `canBeCancelled()` (265-269): Puede cancelarse?
- `recalculateTotal()` (197-205): Recalcula total

**Relaciones**:
- `company()` (138-141)
- `supplierCompany()` (143-146)
- `createdBy()` (148-151)
- `approvedBy()` (153-156)
- `documentItems()` (158-172)
- `statusHistories()` (174-177)
- `purchaseOrderItems()` (182-185)
- `documents()` (190-195)

**Imports Clave**:
- `use App\Notifications\PurchaseOrderCreated;` (línea 7)
- `use App\Notifications\PurchaseOrderStatusChanged;` (línea 8)
- `use Illuminate\Support\Facades\Notification;` (línea 16)

---

### 2. PurchaseOrderItem.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Models/PurchaseOrderItem.php`
**Líneas**: 125
**Importancia**: MEDIA

**Tabla**: `document_item_purchase_order` (pivot)

**Campos Principales** (línea 12-35):
- document_item_id, purchase_order_id, paper_id, paper_description
- quantity_ordered, sheets_quantity, cut_width, cut_height
- unit_price, total_price, status, notes

**Métodos Importantes**:
- `getPaperNameAttribute()` (54-111): Obtiene nombre del papel
  - Prioridades: paper_description → paper.name → itemable
  - Soporta: SimpleItem, Product, TalonarioItem, MagazineItem
- `getCutSizeAttribute()` (116-123): Formatea tamaño de corte

**Relaciones**:
- `documentItem()` (36-39)
- `purchaseOrder()` (41-44)
- `paper()` (46-49)

---

### 3. OrderStatusHistory.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Models/OrderStatusHistory.php`
**Líneas**: Desconocidas
**Importancia**: MEDIA

**Función**: Registra cambios de estado en órdenes

**Campos Relacionados**:
- purchase_order_id, from_status, to_status, user_id, notes, created_at

---

## Eventos (2 archivos)

### 4. PurchaseOrderStatusChanged (Event)
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Events/PurchaseOrderStatusChanged.php`
**Líneas**: 25
**Importancia**: BAJA (no se usa)

**Estado**: DEFINIDO PERO NO UTILIZADO

**Estructura**:
```php
class PurchaseOrderStatusChanged {
    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public OrderStatus $oldStatus,
        public OrderStatus $newStatus
    ) {}
}
```

**Nota**: El evento NO se dispara con `dispatch()` en ningún lugar del código.

---

### 5. NotifyPurchaseOrderStatusChange (Listener)
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Listeners/NotifyPurchaseOrderStatusChange.php`
**Líneas**: 26
**Importancia**: BAJA (vacío)

**Estado**: VACÍO - No implementado

**Método**:
- `handle(PurchaseOrderStatusChanged $event)` (22-25): No hace nada

**Nota**: Podría usarse en el futuro para procesamiento asíncrono.

---

## Notificaciones (2 archivos)

### 6. PurchaseOrderCreated.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Notifications/PurchaseOrderCreated.php`
**Líneas**: 59
**Importancia**: CRÍTICA

**Constructor** (16-18):
- Recibe `int $purchaseOrderId`

**Métodos**:
- `via()` (25-28): Retorna `['mail', 'database']`
- `toMail()` (30-44): Email con PDF adjunto
  - Línea 32: Obtiene PurchaseOrder con relaciones
  - Línea 33-34: Genera PDF usando `PurchaseOrderPdfService`
  - Línea 36-43: Configura MailMessage
  - Plantilla: `emails.purchase-order.created`
  - Adjunto: PDF de la orden
- `toArray()` (46-57): Notificación en base de datos
  - Campos: purchase_order_id, order_number, supplier_company, total_amount, message

**Se envía desde**:
- PurchaseOrder::created (línea 74, 79)
- PurchaseOrder::updated (línea 110, 116)

---

### 7. PurchaseOrderStatusChanged (Notification)
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Notifications/PurchaseOrderStatusChanged.php`
**Líneas**: 73
**Importancia**: CRÍTICA

**Constructor** (16-20):
```php
public function __construct(
    public int $purchaseOrderId,
    public string $oldStatusValue,
    public string $newStatusValue
) {}
```

**Métodos**:
- `via()` (37-40): Retorna `['mail', 'database']`
- `toMail()` (42-57): Email con cambio de estado
  - Línea 44: Obtiene PurchaseOrder
  - Línea 45-46: Convierte status values a enums
  - Plantilla: `emails.purchase-order.status-changed`
  - Variables: purchaseOrder, oldStatus, newStatus, labels
- `toArray()` (59-72): Notificación en base de datos
  - Campos: purchase_order_id, order_number, old_status, new_status, supplier_company, message

**Se envía desde**:
- PurchaseOrder::updated (línea 99-103, 126-130)

---

## Filament Resources (8 archivos)

### 8. PurchaseOrderResource.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/PurchaseOrderResource.php`
**Líneas**: 80
**Importancia**: MEDIA

**Query Builder** (33-51): Multi-tenant query
```php
->where(function ($query) use ($companyId) {
    $query->where('purchase_orders.company_id', $companyId)
        ->orWhere('purchase_orders.supplier_company_id', $companyId);
})
```

**Páginas** (70-78):
- 'index' => ListPurchaseOrders
- 'create' => CreatePurchaseOrder
- 'view' => ViewPurchaseOrder
- 'edit' => EditPurchaseOrder

**RelationManagers** (65-67):
- PurchaseOrderItemsRelationManager

---

### 9. CreatePurchaseOrder.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Pages/CreatePurchaseOrder.php`
**Líneas**: 13
**Importancia**: BAJA

**Nota**: Minimal - creación real ocurre en DocumentsTable

---

### 10. EditPurchaseOrder.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Pages/EditPurchaseOrder.php`
**Líneas**: 55
**Importancia**: MEDIA

**Acción: send_email** (17-50):
- Label: "Enviar por Email" (línea 18)
- Icono: heroicon-o-envelope (línea 19)
- Color: warning (línea 20)
- Formulario (línea 21-30): Email del proveedor (pre-cargado)
- Acción (línea 33-50): Genera PDF y envía
  - Usa: `PurchaseOrderPdfService`
  - Muestra notificación de éxito/error

**Acción: DeleteAction** (línea 52):
- Eliminar orden

---

### 11. ListPurchaseOrders.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Pages/ListPurchaseOrders.php`
**Líneas**: Desconocidas
**Importancia**: BAJA

---

### 12. ViewPurchaseOrder.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php`
**Líneas**: Desconocidas
**Importancia**: BAJA

---

### 13. PurchaseOrderForm.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderForm.php`
**Líneas**: 100+
**Importancia**: MEDIA

**Secciones**:
- Información de la Orden (línea 17-51)
- Fechas (línea 53-72)
- Información Adicional (línea 74-82)
- Metadatos (línea 84-100+)

**Campos**:
- order_number, status, supplier_company_id, total_amount
- order_date, expected_delivery_date, actual_delivery_date
- notes, created_by, approved_by

---

### 14. PurchaseOrdersTable.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/Tables/PurchaseOrdersTable.php`
**Líneas**: Desconocidas
**Importancia**: MEDIA

---

### 15. PurchaseOrderItemsRelationManager.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/PurchaseOrders/RelationManagers/PurchaseOrderItemsRelationManager.php`
**Líneas**: Desconocidas
**Importancia**: MEDIA

---

## Documento Resource (1 archivo principal, acción en tabla)

### 16. DocumentsTable.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Filament/Resources/Documents/Tables/DocumentsTable.php`
**Líneas**: 529+
**Importancia**: CRÍTICA

**Acción: create_purchase_orders** (245-529):

**Visibilidad** (249):
```php
->visible(fn ($record) => $record->canCreateOrders())
```

**Formulario** (250-324):
- Sección: "Seleccionar Items para Orden de Pedido" (251-252)
- CheckboxList: selected_items (254-318)
  - Opciones (256-279): Items disponibles
  - Descripciones (280-314): Cantidad + Proveedor + Costo
  - Validación (316-318): Requerido, mínimo 1
- Textarea: notes (320-323)

**Action Handler** (326-530):
1. Obtener items (328-345):
   - Línea 328-330: `DocumentItem::whereIn(...)->get()`
   - Línea 333-345: Load relaciones morphWith

2. Agrupar items (347-386):
   - Línea 348-386: Agrupar por `{tipo}_{supplierId}`
   - Determinar tipo: 'papel' o 'producto'
   - Obtener supplier_id según itemable_type:
     - SimpleItem → paper.company_id (354-356)
     - TalonarioItem → getMainPaperSupplier() (357-362)
     - MagazineItem → getMainPaperSupplier() (363-368)
     - Paper → company_id (369-371)
     - Product → company_id (374-376)
     - DigitalItem → company_id (377-379)
     - CustomItem → company_id (380-382)

3. Crear órdenes (388-530):
   - Crear PurchaseOrder (393-400):
     ```php
     $order = PurchaseOrder::create([...])
     // ↓ Dispara created hook
     ```
   
   - Agregar items según tipo:
     
     **MagazineItem** (404-441):
     - Una fila POR CADA TIPO DE PAPEL
     - Línea 407: `magazine->getPapersUsed()`
     - Línea 409-440: Para cada papel, attach con datos
     - Línea 444: `$item->updateOrderStatus()`
     
     **TalonarioItem** (446-487):
     - Una fila POR CADA TIPO DE PAPEL
     - Línea 449: `talonario->getPapersUsed()`
     - Línea 451-484: Para cada papel, attach con datos
     - Línea 487: `$item->updateOrderStatus()`
     
     **SimpleItem/Product/Otros** (489-529):
     - Una sola fila POR ITEM
     - Línea 498-505: SimpleItem (extrae paper, sheets, tamaño)
     - Línea 506-508: Product (extrae sale_price)
     - Línea 510-512: Otros (unit_price, quantity)
     - Línea 516-525: Attach con pivot data
     - Línea 528: `$item->updateOrderStatus()`

---

## Servicios (1 archivo)

### 17. PurchaseOrderPdfService.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Services/PurchaseOrderPdfService.php`
**Líneas**: Desconocidas
**Importancia**: MEDIA

**Métodos Principales**:
- `generatePdf($purchaseOrder)` - Genera PDF
- `emailPdf($purchaseOrder, $emailArray)` - Genera y envía

**Usado en**:
- PurchaseOrderCreated::toMail() (línea 33-34)
- EditPurchaseOrder send_email action (línea 34)

---

## Enums (1 archivo)

### 18. OrderStatus.php
**Ruta**: `/home/dasiva/Descargas/litopro825/app/Enums/OrderStatus.php`
**Líneas**: Desconocidas
**Importancia**: MEDIA

**Valores**:
- DRAFT
- SENT
- CONFIRMED
- RECEIVED
- PARTIALLY_RECEIVED
- CANCELLED

**Métodos Probables**:
- `getLabel()` - Etiqueta en español
- `canTransitionTo()` - Validar transición (usado en PurchaseOrder::changeStatus línea 243)

---

## Tablas de Email (2 plantillas)

### emails.purchase-order.created
**Ruta**: `resources/views/emails/purchase-order/created.blade.php`
**Importancia**: MEDIA

**Usado por**: PurchaseOrderCreated::toMail()

**Variables**:
- $purchaseOrder
- $mailable->attachData() - PDF

---

### emails.purchase-order.status-changed
**Ruta**: `resources/views/emails/purchase-order/status-changed.blade.php`
**Importancia**: MEDIA

**Usado por**: PurchaseOrderStatusChanged::toMail()

**Variables**:
- $purchaseOrder
- $oldStatus, $newStatus
- $oldStatusLabel, $newStatusLabel

---

## Tabla de Migración

### purchase_orders table
**Campos principales**:
- id, company_id, supplier_company_id
- order_number (unique por company)
- status (enum: DRAFT, SENT, CONFIRMED, RECEIVED, PARTIALLY_RECEIVED, CANCELLED)
- order_date, expected_delivery_date, actual_delivery_date
- total_amount, notes
- created_by, approved_by, approved_at
- created_at, updated_at

### document_item_purchase_order table (pivot)
**Campos**:
- id, document_item_id, purchase_order_id
- paper_id, paper_description
- quantity_ordered, sheets_quantity
- cut_width, cut_height
- unit_price, total_price
- status, notes
- created_at, updated_at

---

## Resumen de Flujo

```
DocumentsTable (línea 245-529)
├─ Acción: create_purchase_orders
└─ Handlers:
   ├─ Obtener items (328-345)
   ├─ Agrupar por proveedor (347-386)
   └─ Para cada grupo:
      └─ PurchaseOrder::create() (393-400)
         └─ Hook: created (PurchaseOrder.php:63-80)
            ├─ OrderStatusHistory::create()
            ├─ Si status='sent': Email a proveedor
            └─ Notificar usuarios internos
               └─ PurchaseOrderCreated notification
                  ├─ via: ['mail', 'database']
                  ├─ toMail() - adjunta PDF
                  └─ toArray() - stored notification
```

---

## Checklist de Archivos a Modificar/Revisar

Si se necesita cambiar algo:

| Tarea | Archivo |
|-------|---------|
| Cambiar lógica de creación | DocumentsTable.php (245-529) |
| Cambiar lógica de notificación al crear | PurchaseOrder.php (63-80) |
| Cambiar lógica de notificación al actualizar | PurchaseOrder.php (82-135) |
| Cambiar contenido de email | PurchaseOrderCreated.php (30-44) |
| Cambiar contenido de email status | PurchaseOrderStatusChanged.php (42-57) |
| Cambiar interfaz de edición | EditPurchaseOrder.php |
| Cambiar estados disponibles | OrderStatus.php |
| Cambiar generación de número de orden | PurchaseOrder.php (207-239) |
| Cambiar plantilla de email | resources/views/emails/purchase-order/*.blade.php |

---

## Notas Técnicas

1. **El evento `PurchaseOrderStatusChanged` NO se dispara** - Se puede eliminar si no se usa en el futuro

2. **El listener `NotifyPurchaseOrderStatusChange` está vacío** - No hace nada actualmente

3. **Las notificaciones se envían directamente en los hooks del modelo**, no a través del patrón de eventos/listeners

4. **Multi-tenant**: Todas las órdenes se filtran por `company_id` automáticamente

5. **MagazineItem y TalonarioItem generan múltiples filas** - Una por cada tipo de papel usado

6. **Historial automático**: Cada cambio de estado se registra en `OrderStatusHistory`

7. **Agrupación inteligente**: Las órdenes se agrupan automáticamente por proveedor y tipo

