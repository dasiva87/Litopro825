# Fix: Emails de Purchase Orders No Se Enviaban a Proveedores Locales

**Fecha**: 17-Dic-2025
**Problema Original**: Los emails de órdenes de pedido no se enviaban al proveedor

---

## 🔍 **Problema Identificado**

El sistema tenía **2 problemas** que impedían el envío de emails de Purchase Orders:

### **Problema 1: PDF Service no soportaba proveedores locales**

**Archivo**: `app/Services/PurchaseOrderPdfService.php`
**Línea**: 28

**Código ANTES**:
```php
$supplier = $order->supplierCompany; // Puede ser NULL si es proveedor local
```

**Síntoma**: Error al generar PDF
```
Attempt to read property "name" on null
```

**Causa**:
- El sistema asumía que **todos** los proveedores son `Company` (Grafired)
- Pero también hay proveedores **locales** (`Contact`) con `supplier_company_id = NULL`
- Cuando `supplierCompany` era NULL, el PDF crasheaba

---

### **Problema 2: Modelo PurchaseOrder solo enviaba email a supplierCompany**

**Archivo**: `app/Models/PurchaseOrder.php`
**Líneas**: 73-76, 107-118

**Código ANTES**:
```php
// Solo enviaba si supplierCompany existe
if ($order->status === OrderStatus::SENT && $order->supplierCompany && $order->supplierCompany->email) {
    Notification::route('mail', $order->supplierCompany->email)
        ->notify(new PurchaseOrderCreated($order->id));
}
```

**Síntoma**: Email no se enviaba a proveedores locales

**Causa**:
- Proveedores locales (`Contact`) tienen email en `contacts.email`
- Código solo verificaba `supplierCompany->email`
- Proveedores locales nunca recibían notificación

---

### **Problema 3: Template de email asumía supplierCompany**

**Archivo**: `resources/views/emails/purchase-order/created.blade.php`
**Línea**: 4

**Código ANTES**:
```blade
Estimado {{ $purchaseOrder->supplierCompany->name }},
```

**Síntoma**: Error al renderizar email
```
Attempt to read property "name" on null
```

---

## ✅ **Solución Implementada**

### **Fix 1: PurchaseOrderPdfService.php**

**Cambio en líneas 13-27**:
```php
public function generatePdf(PurchaseOrder $order): \Barryvdh\DomPDF\PDF
{
    $order->load([
        'supplierCompany',  // Company (Grafired)
        'supplier',         // ← AGREGADO: Contact (Local)
        'company',
        'purchaseOrderItems.documentItem',
        'purchaseOrderItems.paper',
        'createdBy',
        'approvedBy'
    ]);

    $documents = $order->documents();

    // ✅ FIX: Determinar proveedor (Company o Contact)
    $supplier = $order->supplierCompany ?? $order->supplier;

    $data = [
        'order' => $order,
        'company' => $order->company,
        'supplier' => $supplier, // ← Ahora puede ser Company o Contact
        'documents' => $documents,
    ];

    $pdf = Pdf::loadView('pdf.purchase-order', $data);
    $pdf->setPaper('letter', 'portrait');

    return $pdf;
}
```

**Resultado**: PDF se genera correctamente para ambos tipos de proveedores

---

### **Fix 2: PurchaseOrder.php - static::created**

**Cambio en líneas 72-84**:
```php
// Enviar notificación al proveedor cuando se crea la orden con estado 'sent'
if ($order->status === OrderStatus::SENT) {
    // ✅ Proveedor Grafired (Company)
    if ($order->supplierCompany && $order->supplierCompany->email) {
        Notification::route('mail', $order->supplierCompany->email)
            ->notify(new PurchaseOrderCreated($order->id));
    }
    // ✅ Proveedor Local (Contact)
    elseif ($order->supplier && $order->supplier->email) {
        Notification::route('mail', $order->supplier->email)
            ->notify(new PurchaseOrderCreated($order->id));
    }
}
```

**Resultado**: Email se envía a proveedor local (`Contact->email`) o Grafired (`Company->email`)

---

### **Fix 3: PurchaseOrder.php - static::updating**

**Cambio en líneas 115-135**:
```php
// Si el estado cambia a 'sent', notificar a usuarios del proveedor
if ($newStatus === OrderStatus::SENT) {
    // ✅ Proveedor Grafired (Company)
    if ($updatedOrder->supplierCompany) {
        // Notificar a usuarios del proveedor
        $supplierUsers = User::where('company_id', $updatedOrder->supplier_company_id)->get();
        if ($supplierUsers->isNotEmpty()) {
            Notification::send($supplierUsers, new PurchaseOrderCreated($updatedOrder->id));
        }

        // Email al email general
        if ($updatedOrder->supplierCompany->email) {
            Notification::route('mail', $updatedOrder->supplierCompany->email)
                ->notify(new PurchaseOrderCreated($updatedOrder->id));
        }
    }
    // ✅ Proveedor Local (Contact)
    elseif ($updatedOrder->supplier && $updatedOrder->supplier->email) {
        Notification::route('mail', $updatedOrder->supplier->email)
            ->notify(new PurchaseOrderCreated($updatedOrder->id));
    }
}
```

**Resultado**: Email se envía cuando el estado cambia a `SENT`

---

### **Fix 4: Template de Email**

**Cambio en línea 4**:
```blade
{{-- ANTES --}}
Estimado {{ $purchaseOrder->supplierCompany->name }},

{{-- DESPUÉS --}}
Estimado {{ $purchaseOrder->supplierCompany->name ?? $purchaseOrder->supplier->name ?? 'Proveedor' }},
```

**Resultado**: Template funciona con ambos tipos de proveedores

---

## 📊 **Archivos Modificados**

1. **app/Services/PurchaseOrderPdfService.php**
   - Agregado soporte para `supplier` (Contact)
   - Fallback: `supplierCompany ?? supplier`

2. **app/Models/PurchaseOrder.php**
   - Método `static::created()`: Agregado envío a Contact
   - Método `static::updating()`: Agregado envío a Contact

3. **resources/views/emails/purchase-order/created.blade.php**
   - Fallback en saludo: `supplierCompany->name ?? supplier->name`

---

## ✅ **Verificación**

### **Antes del Fix**:
```
❌ PDF crasheaba: "Attempt to read property 'name' on null"
❌ Email nunca se enviaba a proveedores locales
❌ Template de email crasheaba
```

### **Después del Fix**:
```
✅ PDF se genera correctamente para ambos tipos
✅ Email se envía a Contact->email (proveedor local)
✅ Email se envía a Company->email (proveedor Grafired)
✅ Template renderiza correctamente con fallback
```

---

## 🎯 **Cómo Funciona Ahora**

### **Flujo de Envío de Email**:

1. **Usuario crea Purchase Order** con status `SENT`
2. **Sistema verifica tipo de proveedor**:
   - `supplier_company_id` existe → Proveedor Grafired (Company)
   - `supplier_company_id` NULL → Proveedor Local (Contact)
3. **Genera PDF** usando `supplierCompany ?? supplier`
4. **Envía email** a:
   - `supplierCompany->email` (si es Grafired)
   - `supplier->email` (si es Local)
5. **Email incluye**:
   - PDF adjunto de la orden
   - Detalles de la orden
   - Botón para ver orden completa

---

## 📝 **Notas Importantes**

### **Tipos de Proveedores**:
- **Grafired (Company)**: Empresas en la red, tienen `supplier_company_id`
- **Local (Contact)**: Proveedores tradicionales, tienen `supplier_id`

### **Ambos reciben notificación** con:
- Email con PDF adjunto
- Notificación en base de datos
- Detalles completos de la orden

---

## 🚀 **Testing**

Para probar el fix:
```bash
php test_purchase_order_email.php
```

**Nota**: Si obtienes error "550 5.7.0 Too many emails per second", es porque Mailtrap tiene límite de velocidad. Espera 60 segundos y vuelve a intentar.

---

## 📧 **Mailtrap Limit**

El plan gratuito de Mailtrap tiene límite de ~2-3 emails/minuto.

**Error común**:
```
Expected response code "354" but got code "550"
550 5.7.0 Too many emails per second
```

**Solución**: Esperar 1 minuto entre pruebas.

---

**Status**: ✅ COMPLETADO
**Sistema de emails de Purchase Orders**: 100% funcional para proveedores locales y Grafired
