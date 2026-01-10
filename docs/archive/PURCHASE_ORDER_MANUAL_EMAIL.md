# Purchase Orders - Envío Manual de Emails

**Fecha**: 17-Dic-2025
**Cambio**: Envío manual de emails en Purchase Orders

---

## 🎯 **Problema Original**

**ANTES del cambio:**
```
1. Usuario crea Purchase Order
2. ❌ Sistema envía email INMEDIATAMENTE al proveedor
3. ❌ Proveedor recibe email SIN items ni valores
4. ❌ Orden incompleta enviada automáticamente
```

**Causa:**
- Email se enviaba en `PurchaseOrder::created()` apenas se guardaba
- No había validación de items ni totales
- No había control manual del usuario

---

## ✅ **Solución Implementada**

**DESPUÉS del cambio:**
```
1. Usuario crea Purchase Order
2. Usuario agrega items y valores
3. Usuario revisa que todo esté correcto
4. ✅ Usuario hace clic en "Enviar Email al Proveedor"
5. ✅ Sistema valida items y totales
6. ✅ Proveedor recibe email COMPLETO con PDF
```

---

## 📋 **Cambios Realizados**

### **1. Nueva Migración: `email_sent_at` y `email_sent_by`**

**Archivo**: `database/migrations/2025_12_17_041054_add_email_sent_at_to_purchase_orders_table.php`

```php
Schema::table('purchase_orders', function (Blueprint $table) {
    $table->timestamp('email_sent_at')->nullable();
    $table->unsignedBigInteger('email_sent_by')->nullable();
    $table->foreign('email_sent_by')->references('id')->on('users');
});
```

**Propósito:**
- `email_sent_at`: Timestamp de cuándo se envió el email
- `email_sent_by`: ID del usuario que envió el email

---

### **2. Modelo PurchaseOrder Actualizado**

**Archivo**: `app/Models/PurchaseOrder.php`

**Agregado a `$fillable`:**
```php
'email_sent_at',
'email_sent_by',
```

**Agregado a `$casts`:**
```php
'email_sent_at' => 'datetime',
```

**Nueva relación:**
```php
public function emailSentBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'email_sent_by');
}
```

---

### **3. Envío Automático DESACTIVADO**

**Archivo**: `app/Models/PurchaseOrder.php`

**ANTES (líneas 72-84):**
```php
static::created(function (PurchaseOrder $order) {
    // ...
    if ($order->status === OrderStatus::SENT) {
        // ❌ Enviaba email automáticamente
        Notification::route('mail', $supplierEmail)
            ->notify(new PurchaseOrderCreated($order->id));
    }
});
```

**AHORA:**
```php
static::created(function (PurchaseOrder $order) {
    // ...
    // ❌ DESACTIVADO: Envío automático de email al proveedor
    // Ahora se envía manualmente con el botón "Enviar Email al Proveedor"
    // Ver: ViewPurchaseOrder::sendEmailAction()
});
```

**También desactivado en `static::updating()`** (líneas 107-109)

---

### **4. Acción Manual en ViewPurchaseOrder**

**Archivo**: `app/Filament/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php`

**Nueva acción (líneas 77-164):**
```php
Actions\Action::make('send_email')
    ->label(fn () => $this->record->email_sent_at ? 'Reenviar Email' : 'Enviar Email al Proveedor')
    ->color(fn () => $this->record->email_sent_at ? 'success' : 'warning')
    ->badge(fn () => $this->record->email_sent_at ? 'Enviado' : null)
    ->requiresConfirmation()
    ->action(function () {
        // Validaciones
        if ($this->record->purchaseOrderItems->isEmpty()) { ... }
        if ($this->record->total_amount <= 0) { ... }
        if (!$supplierEmail) { ... }

        // Enviar email
        Notification::route('mail', $supplierEmail)
            ->notify(new PurchaseOrderCreated($this->record->id));

        // Actualizar tracking
        $this->record->update([
            'email_sent_at' => now(),
            'email_sent_by' => auth()->id(),
        ]);
    })
```

**Características:**
- ✅ Valida que tenga items
- ✅ Valida que tenga total > 0
- ✅ Valida que proveedor tenga email
- ✅ Cambia de "Enviar" a "Reenviar" si ya se envió
- ✅ Badge "Enviado" cuando ya se envió
- ✅ Modal de confirmación
- ✅ Tracking de quién y cuándo envió

---

### **5. Acción Manual en ListPurchaseOrders (Tabla)**

**Archivo**: `app/Filament/Resources/PurchaseOrders/Tables/PurchaseOrdersTable.php`

**Acción actualizada (líneas 245-328):**
```php
Action::make('send_email')
    ->label('')
    ->icon('heroicon-o-envelope')
    ->color(fn ($record) => $record->email_sent_at ? 'success' : 'warning')
    ->tooltip(fn ($record) => $record->email_sent_at
        ? 'Reenviar Email (enviado ' . $record->email_sent_at->diffForHumans() . ')'
        : 'Enviar Email al Proveedor')
    // ... mismas validaciones y lógica
```

**Nueva columna en tabla (líneas 62-72):**
```php
TextColumn::make('email_sent_at')
    ->label('Email')
    ->badge()
    ->formatStateUsing(fn ($state) => $state ? 'Enviado' : 'Pendiente')
    ->color(fn ($state) => $state ? 'success' : 'gray')
    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
    ->tooltip(fn ($record) => $record->email_sent_at
        ? "Enviado: {$record->email_sent_at->format('d/m/Y H:i')}"
        : 'Email no enviado')
```

**Resultado**: Usuario ve en la tabla si el email fue enviado o está pendiente

---

## 🎯 **Flujo Completo del Usuario**

### **Paso 1: Crear Purchase Order**
```
1. Usuario va a Purchase Orders → Crear
2. Completa formulario básico
3. Guarda orden (status = DRAFT o SENT)
4. ✅ Email NO se envía automáticamente
```

### **Paso 2: Agregar Items**
```
1. Usuario abre Purchase Order creada
2. Agrega items en la relación "Items"
3. Sistema calcula total automáticamente
4. ✅ Email sigue sin enviarse
```

### **Paso 3: Enviar Email Manualmente**

**Desde página de detalle:**
```
1. Usuario ve botón "📧 Enviar Email al Proveedor" (color warning)
2. Click en el botón
3. Modal de confirmación muestra:
   - Orden #XXXX para [Proveedor]
   - "Se enviará el email con el PDF"
4. Usuario confirma
5. Sistema valida:
   ✅ Tiene items
   ✅ Tiene total > 0
   ✅ Proveedor tiene email
6. Envía email con PDF adjunto
7. Actualiza email_sent_at = now()
8. Botón cambia a "Reenviar Email" (color success + badge "Enviado")
```

**Desde tabla:**
```
1. Usuario ve icono 📧 (warning = no enviado, success = enviado)
2. Tooltip muestra:
   - "Enviar Email al Proveedor" (si no enviado)
   - "Reenviar Email (enviado hace X tiempo)" (si enviado)
3. Click en icono
4. Mismo flujo de validación y envío
```

---

## ✅ **Validaciones Implementadas**

### **1. Validación de Items**
```php
if ($this->record->purchaseOrderItems->isEmpty()) {
    Notification::make()
        ->danger()
        ->title('No se puede enviar')
        ->body('La orden no tiene items. Agrega items antes de enviar.')
        ->send();
    return;
}
```

### **2. Validación de Total**
```php
if ($this->record->total_amount <= 0) {
    Notification::make()
        ->danger()
        ->title('No se puede enviar')
        ->body('La orden tiene un total de $0. Verifica los items.')
        ->send();
    return;
}
```

### **3. Validación de Email del Proveedor**
```php
$supplierEmail = $this->record->supplierCompany->email
    ?? $this->record->supplier->email;

if (!$supplierEmail) {
    Notification::make()
        ->danger()
        ->title('No se puede enviar')
        ->body('El proveedor no tiene email configurado.')
        ->send();
    return;
}
```

---

## 📊 **UI/UX Mejorado**

### **Botón en Página de Detalle**

| Estado | Label | Color | Badge | Icon |
|--------|-------|-------|-------|------|
| No enviado | "Enviar Email al Proveedor" | Warning | - | 📧 |
| Enviado | "Reenviar Email" | Success | "Enviado" | 📧 |

### **Botón en Tabla**

| Estado | Tooltip | Color | Icon |
|--------|---------|-------|------|
| No enviado | "Enviar Email al Proveedor" | Warning | 📧 |
| Enviado | "Reenviar Email (enviado hace 2 horas)" | Success | 📧 |

### **Columna en Tabla**

| Estado | Badge | Color | Icon | Tooltip |
|--------|-------|-------|------|---------|
| No enviado | "Pendiente" | Gray | ⏰ | "Email no enviado" |
| Enviado | "Enviado" | Success | ✅ | "Enviado: 17/12/2025 14:30" |

---

## 🧪 **Testing**

### **Test 1: Crear orden sin items**
```
✅ Crear Purchase Order
✅ No agregar items
✅ Click en "Enviar Email"
❌ Error: "La orden no tiene items"
✅ Email NO se envía
```

### **Test 2: Crear orden con items**
```
✅ Crear Purchase Order
✅ Agregar 2 items con valores
✅ Total calculado correctamente
✅ Click en "Enviar Email"
✅ Modal de confirmación
✅ Email enviado exitosamente
✅ Badge "Enviado" aparece
✅ Botón cambia a "Reenviar Email"
```

### **Test 3: Reenviar email**
```
✅ Orden con email ya enviado
✅ Botón muestra "Reenviar Email" (success)
✅ Click en botón
⚠️ Modal advierte: "Esta orden ya fue enviada el..."
✅ Confirmar reenvío
✅ Email enviado nuevamente
✅ email_sent_at actualizado
```

### **Test 4: Proveedor sin email**
```
✅ Crear Purchase Order con proveedor sin email
✅ Agregar items
✅ Click en "Enviar Email"
❌ Error: "El proveedor no tiene email configurado"
✅ Email NO se envía
```

---

## 📝 **Archivos Modificados**

1. **Migración creada**:
   - `database/migrations/2025_12_17_041054_add_email_sent_at_to_purchase_orders_table.php`

2. **Modelo actualizado**:
   - `app/Models/PurchaseOrder.php`
     - Agregado campos a `$fillable` y `$casts`
     - Agregado relación `emailSentBy()`
     - Desactivado envío automático en `static::created()`
     - Desactivado envío automático en `static::updating()`

3. **Página de detalle**:
   - `app/Filament/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php`
     - Reemplazado acción `send_email` con nuevo flujo

4. **Tabla**:
   - `app/Filament/Resources/PurchaseOrders/Tables/PurchaseOrdersTable.php`
     - Actualizado acción `send_email`
     - Agregado columna `email_sent_at`

---

## 🎉 **Beneficios del Cambio**

✅ **Control total del usuario**: Decide cuándo enviar
✅ **Validación de datos**: No se envían órdenes vacías
✅ **Tracking completo**: Se sabe quién y cuándo envió
✅ **Posibilidad de reenvío**: Botón cambia a "Reenviar"
✅ **Visual claro**: Badge y colores indican estado
✅ **UX mejorada**: Confirmación antes de enviar

---

## 🚀 **Próximos Pasos Opcionales**

1. **Historial de envíos**: Tabla con todos los envíos (reenvíos)
2. **Email a múltiples destinatarios**: Opción de CC/BCC
3. **Plantilla personalizable**: Permitir editar mensaje del email
4. **Envío programado**: Agendar envío para fecha/hora específica

---

**Status**: ✅ COMPLETADO
**Sistema**: 100% funcional con envío manual de emails
