# Sistema de Envío Manual de Emails - Sesión 17-Dic-2025

## 📋 **Resumen de la Sesión**

Esta sesión implementó el **envío manual de emails** para Purchase Orders, eliminando el envío automático que causaba que los proveedores recibieran emails con órdenes vacías.

---

## 🎯 **Problema Resuelto**

### **Antes:**
```
Usuario crea Purchase Order
    ↓
❌ Sistema envía email INMEDIATAMENTE
    ↓
❌ Proveedor recibe orden SIN items ni valores
    ↓
❌ Email incompleto e inútil
```

### **Después:**
```
Usuario crea Purchase Order
    ↓
Usuario agrega items y valores
    ↓
Usuario revisa todo
    ↓
✅ Usuario click "Enviar Email al Proveedor"
    ↓
✅ Sistema valida items + total + email
    ↓
✅ Proveedor recibe email COMPLETO con PDF
```

---

## 🛠️ **Implementación Completa**

### **Paso 1: Migración para Tracking de Envío**

**Archivo**: `database/migrations/2025_12_17_041054_add_email_sent_at_to_purchase_orders_table.php`

```php
Schema::table('purchase_orders', function (Blueprint $table) {
    $table->timestamp('email_sent_at')->nullable()->after('approved_at');
    $table->unsignedBigInteger('email_sent_by')->nullable()->after('email_sent_at');
    $table->foreign('email_sent_by')->references('id')->on('users')->onDelete('set null');
});
```

**Propósito:**
- `email_sent_at`: Timestamp de cuándo se envió
- `email_sent_by`: Usuario que envió el email

**Ejecutar:**
```bash
php artisan migrate
```

---

### **Paso 2: Actualizar Modelo**

**Archivo**: `app/Models/PurchaseOrder.php`

**2.1 - Agregar a `$fillable`:**
```php
protected $fillable = [
    // ... campos existentes
    'email_sent_at',
    'email_sent_by',
];
```

**2.2 - Agregar a `$casts`:**
```php
protected $casts = [
    // ... casts existentes
    'email_sent_at' => 'datetime',
];
```

**2.3 - Agregar relación:**
```php
public function emailSentBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'email_sent_by');
}
```

**2.4 - DESACTIVAR envío automático en `static::created()`:**
```php
static::created(function (PurchaseOrder $order) {
    // Crear historial...

    // ❌ COMENTAR/ELIMINAR este bloque:
    // if ($order->status === OrderStatus::SENT) {
    //     Notification::route('mail', $supplierEmail)
    //         ->notify(new PurchaseOrderCreated($order->id));
    // }

    // ✅ AGREGAR comentario explicativo:
    // ❌ DESACTIVADO: Envío automático de email al proveedor
    // Ahora se envía manualmente con el botón "Enviar Email al Proveedor"
    // Ver: ViewPurchaseOrder::sendEmailAction() y ListPurchaseOrders::getTableActions()

    // Notificar a usuarios internos (solo notificación en app)
    $companyUsers = User::forTenant($order->company_id)->get();
    Notification::send($companyUsers, new PurchaseOrderCreated($order->id));
});
```

**2.5 - DESACTIVAR envío automático en `static::updating()`:**
```php
static::updating(function (PurchaseOrder $order) {
    if ($order->isDirty('status')) {
        // ...

        // ❌ COMENTAR/ELIMINAR bloque de envío cuando cambia a SENT:
        // if ($newStatus === OrderStatus::SENT) {
        //     // Envío automático...
        // }

        // ✅ AGREGAR comentario:
        // ❌ DESACTIVADO: Envío automático de email cuando cambia a SENT
        // Ahora se envía manualmente con el botón "Enviar Email al Proveedor"
    }
});
```

---

### **Paso 3: Acción Manual en Página de Detalle**

**Archivo**: `app/Filament/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php`

**3.1 - Reemplazar acción `send_email` existente:**

```php
Actions\Action::make('send_email')
    ->label(fn () => $this->record->email_sent_at ? 'Reenviar Email' : 'Enviar Email al Proveedor')
    ->icon('heroicon-o-envelope')
    ->color(fn () => $this->record->email_sent_at ? 'success' : 'warning')
    ->badge(fn () => $this->record->email_sent_at ? 'Enviado' : null)
    ->badgeColor('success')
    ->requiresConfirmation()
    ->modalHeading(fn () => $this->record->email_sent_at
        ? 'Reenviar Orden por Email'
        : 'Enviar Orden por Email')
    ->modalDescription(function () {
        $supplierName = $this->record->supplierCompany->name
            ?? $this->record->supplier->name
            ?? 'Sin proveedor';

        $description = "Orden #{$this->record->order_number} para {$supplierName}\n\n";

        if ($this->record->email_sent_at) {
            $description .= "⚠️ Esta orden ya fue enviada el {$this->record->email_sent_at->format('d/m/Y H:i')}\n";
            $description .= "¿Deseas reenviar el email?";
        } else {
            $description .= "Se enviará el email con el PDF de la orden al proveedor.";
        }

        return $description;
    })
    ->modalIcon('heroicon-o-envelope')
    ->action(function () {
        // VALIDACIÓN 1: Verificar items
        if ($this->record->purchaseOrderItems->isEmpty()) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('No se puede enviar')
                ->body('La orden no tiene items. Agrega items antes de enviar.')
                ->send();
            return;
        }

        // VALIDACIÓN 2: Verificar total
        if ($this->record->total_amount <= 0) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('No se puede enviar')
                ->body('La orden tiene un total de $0. Verifica los items.')
                ->send();
            return;
        }

        // VALIDACIÓN 3: Verificar email del proveedor
        $supplierEmail = $this->record->supplierCompany->email
            ?? $this->record->supplier->email;

        if (!$supplierEmail) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('No se puede enviar')
                ->body('El proveedor no tiene email configurado.')
                ->send();
            return;
        }

        try {
            // Enviar notificación con PDF
            \Illuminate\Support\Facades\Notification::route('mail', $supplierEmail)
                ->notify(new \App\Notifications\PurchaseOrderCreated($this->record->id));

            // Actualizar registro de envío
            $this->record->update([
                'email_sent_at' => now(),
                'email_sent_by' => auth()->id(),
            ]);

            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Email enviado')
                ->body("Orden enviada exitosamente a {$supplierEmail}")
                ->send();

        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error al enviar email')
                ->body($e->getMessage())
                ->send();
        }
    }),
```

**Características:**
- ✅ Label dinámico: "Enviar" vs "Reenviar"
- ✅ Color dinámico: warning (no enviado) vs success (enviado)
- ✅ Badge "Enviado" cuando corresponde
- ✅ 3 validaciones antes de enviar
- ✅ Modal de confirmación con info
- ✅ Tracking de envío (timestamp + usuario)
- ✅ Manejo de errores

---

### **Paso 4: Acción Manual en Tabla**

**Archivo**: `app/Filament/Resources/PurchaseOrders/Tables/PurchaseOrdersTable.php`

**4.1 - Reemplazar acción `send_email` en `->actions([])`:**

```php
Action::make('send_email')
    ->label('')
    ->icon('heroicon-o-envelope')
    ->color(fn ($record) => $record->email_sent_at ? 'success' : 'warning')
    ->tooltip(fn ($record) => $record->email_sent_at
        ? 'Reenviar Email (enviado ' . $record->email_sent_at->diffForHumans() . ')'
        : 'Enviar Email al Proveedor')
    ->requiresConfirmation()
    ->modalHeading(fn ($record) => $record->email_sent_at
        ? 'Reenviar Orden por Email'
        : 'Enviar Orden por Email')
    ->modalDescription(function ($record) {
        $supplierName = $record->supplierCompany->name
            ?? $record->supplier->name
            ?? 'Sin proveedor';

        $description = "Orden #{$record->order_number} para {$supplierName}";

        if ($record->email_sent_at) {
            $description .= "\n\n⚠️ Esta orden ya fue enviada el {$record->email_sent_at->format('d/m/Y H:i')}";
        }

        return $description;
    })
    ->modalIcon('heroicon-o-envelope')
    ->action(function ($record) {
        // Mismas 3 validaciones que ViewPurchaseOrder
        if ($record->purchaseOrderItems->isEmpty()) { /* ... */ }
        if ($record->total_amount <= 0) { /* ... */ }

        $supplierEmail = $record->supplierCompany->email ?? $record->supplier->email;
        if (!$supplierEmail) { /* ... */ }

        try {
            // Enviar email
            \Illuminate\Support\Facades\Notification::route('mail', $supplierEmail)
                ->notify(new \App\Notifications\PurchaseOrderCreated($record->id));

            // Actualizar tracking
            $record->update([
                'email_sent_at' => now(),
                'email_sent_by' => auth()->id(),
            ]);

            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Email enviado')
                ->body("Orden enviada exitosamente a {$supplierEmail}")
                ->send();

        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error al enviar email')
                ->body($e->getMessage())
                ->send();
        }
    }),
```

**4.2 - Agregar columna "Email" en `->columns([])`:**

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
    ->sortable()
    ->toggleable(),
```

---

## ✅ **Sesión Completada (17-Dic-2025) - COTIZACIONES**

### **SPRINT 26: Envío Manual de Emails para Cotizaciones (Documents/Quotes)**

#### **Implementación Completa:**

**Archivos Creados:**
1. `database/migrations/2025_12_17_234302_add_email_sent_at_to_documents_table.php`
   - Campos: `email_sent_at`, `email_sent_by`
   - Foreign key a `users`

2. `app/Notifications/QuoteSent.php`
   - Envío de email con PDF adjunto
   - Notificación database para usuarios internos
   - Usa DomPDF (mismo que DocumentPdfController)

3. `resources/views/emails/quote/sent.blade.php`
   - Template Markdown para email
   - Información completa del documento
   - Botón para ver PDF completo

**Archivos Modificados:**
1. `app/Models/Document.php`
   - Agregado `email_sent_at`, `email_sent_by` a `$fillable`
   - Agregado `email_sent_at` a `$casts` (datetime)
   - Relación `emailSentBy()` a User

2. `app/Filament/Resources/Documents/Pages/ViewDocument.php`
   - Acción `send_email` con label dinámico
   - 3 validaciones: items, total, email cliente
   - Badge "Enviado" cuando corresponde
   - Modal de confirmación con advertencia de reenvío

3. `app/Filament/Resources/Documents/Tables/DocumentsTable.php`
   - Columna `email_sent_at` con badge y tooltip
   - Acción `send_email` en tabla
   - Mismas validaciones que ViewDocument

#### **Testing Realizado:**
```bash
✅ Migración ejecutada sin errores
✅ No hay errores de sintaxis en archivos PHP
✅ Caché limpiada (views + config)
✅ Campos agregados correctamente a BD
✅ Relación emailSentBy() funcional
```

#### **Características Implementadas:**
- ✅ **Envío manual**: Usuario controla cuándo enviar
- ✅ **Validaciones**: No permite enviar documentos vacíos o sin email
- ✅ **Tracking**: Registra cuándo y quién envió
- ✅ **Reenvío**: Permite reenviar con advertencia
- ✅ **PDF adjunto**: Email incluye PDF generado con DomPDF
- ✅ **UI dinámica**: Label, color y badge según estado

---

## 🎯 **Próxima Sesión: Implementar en Otros Módulos**

### **Módulos Pendientes:**

#### **1. ~~Cotizaciones (Documents/Quotes)~~ ✅ COMPLETADO**
**Status**: ✅ Implementado en Sprint 26

---

#### **2. Cuentas de Cobro (Collection Accounts)**
**Aplicar mismo patrón:**
- Migración: `email_sent_at`, `email_sent_by` en tabla `collection_accounts`
- Modelo: `CollectionAccount.php`
- Página: `ViewCollectionAccount.php` o equivalente
- Tabla: `CollectionAccountsTable.php` o equivalente
- Notificación: `CollectionAccountCreated` o similar

**Consideraciones:**
- Cliente debe recibir cuenta de cobro completa
- Validar que tenga items/facturas asociadas
- Validar total a cobrar > 0
- Validar que cliente tenga email

---

#### **3. Órdenes de Producción (Production Orders)**
**Aplicar mismo patrón:**
- Migración: `email_sent_at`, `email_sent_by` en tabla `production_orders`
- Modelo: `ProductionOrder.php`
- Página: `ViewProductionOrder.php` o equivalente
- Tabla: `ProductionOrdersTable.php` o equivalente
- Notificación: `ProductionOrderCreated` o similar

**Consideraciones:**
- Proveedor/operador debe recibir orden de producción completa
- Validar que tenga items y especificaciones
- Validar que proveedor tenga email
- Considerar si va a cliente interno o proveedor externo

---

## 📝 **Template para Implementación Rápida**

### **Checklist por Módulo:**

```
□ 1. Crear migración add_email_sent_at_to_[tabla]
   - Campo: email_sent_at (timestamp nullable)
   - Campo: email_sent_by (unsignedBigInteger nullable)
   - Foreign key a users

□ 2. Actualizar Modelo
   - Agregar campos a $fillable
   - Agregar email_sent_at a $casts (datetime)
   - Agregar relación emailSentBy()
   - DESACTIVAR envío automático en static::created()
   - DESACTIVAR envío automático en static::updating()

□ 3. Actualizar Página de Detalle (View[Modulo])
   - Reemplazar acción send_email con nuevo código
   - Label dinámico (Enviar vs Reenviar)
   - Color dinámico (warning vs success)
   - Badge "Enviado"
   - 3 Validaciones (items, total, email)
   - Tracking de envío

□ 4. Actualizar Tabla ([Modulo]sTable)
   - Reemplazar acción send_email
   - Agregar columna email_sent_at
   - Tooltip dinámico
   - Mismo código de validaciones

□ 5. Testing
   - Crear registro sin items → Error
   - Crear registro con items → Envío exitoso
   - Reenviar → Modal de advertencia
   - Verificar columna en tabla
   - Verificar email en Mailtrap
```

---

## 🔧 **Código Reutilizable**

### **Template de Migración:**
```php
Schema::table('[tabla]', function (Blueprint $table) {
    $table->timestamp('email_sent_at')->nullable()->after('approved_at');
    $table->unsignedBigInteger('email_sent_by')->nullable()->after('email_sent_at');
    $table->foreign('email_sent_by')->references('id')->on('users')->onDelete('set null');
});

// Down
Schema::table('[tabla]', function (Blueprint $table) {
    $table->dropForeign(['email_sent_by']);
    $table->dropColumn(['email_sent_at', 'email_sent_by']);
});
```

### **Template de Validaciones:**
```php
// 1. Validar items
if ($record->[items_relation]->isEmpty()) {
    \Filament\Notifications\Notification::make()
        ->danger()
        ->title('No se puede enviar')
        ->body('El [documento] no tiene items.')
        ->send();
    return;
}

// 2. Validar total
if ($record->total_amount <= 0) {
    \Filament\Notifications\Notification::make()
        ->danger()
        ->title('No se puede enviar')
        ->body('El [documento] tiene un total de $0.')
        ->send();
    return;
}

// 3. Validar email destinatario
$recipientEmail = $record->[cliente/proveedor]->email;

if (!$recipientEmail) {
    \Filament\Notifications\Notification::make()
        ->danger()
        ->title('No se puede enviar')
        ->body('El [cliente/proveedor] no tiene email configurado.')
        ->send();
    return;
}
```

### **Template de Envío:**
```php
try {
    // Enviar notificación
    \Illuminate\Support\Facades\Notification::route('mail', $recipientEmail)
        ->notify(new \App\Notifications\[NotificationClass]($record->id));

    // Actualizar tracking
    $record->update([
        'email_sent_at' => now(),
        'email_sent_by' => auth()->id(),
    ]);

    \Filament\Notifications\Notification::make()
        ->success()
        ->title('Email enviado')
        ->body("[Documento] enviado exitosamente a {$recipientEmail}")
        ->send();

} catch (\Exception $e) {
    \Filament\Notifications\Notification::make()
        ->danger()
        ->title('Error al enviar email')
        ->body($e->getMessage())
        ->send();
}
```

---

## 📊 **Archivos de Referencia**

**Implementación completa en Purchase Orders:**
1. `database/migrations/2025_12_17_041054_add_email_sent_at_to_purchase_orders_table.php`
2. `app/Models/PurchaseOrder.php`
3. `app/Filament/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php`
4. `app/Filament/Resources/PurchaseOrders/Tables/PurchaseOrdersTable.php`

**Documentación detallada:**
- `PURCHASE_ORDER_MANUAL_EMAIL.md`

---

## ✅ **Sesión Completada**

**Fecha**: 17-Dic-2025
**Módulo**: Purchase Orders
**Status**: ✅ 100% Funcional

**Próxima sesión**: Implementar en Cotizaciones, Cuentas de Cobro y Órdenes de Producción usando este mismo patrón.

---

## 🎯 **Comando para Próxima Sesión**

```bash
# Leer este archivo antes de empezar
cat EMAIL.md

# Decidir módulo a implementar:
# - Cotizaciones (Documents/Quotes)
# - Cuentas de Cobro (Collection Accounts)
# - Órdenes de Producción (Production Orders)

# Seguir checklist paso por paso
# Copiar código template y adaptar
# Testing completo
```

---

**¡Sistema de envío manual implementado exitosamente!** 🚀
