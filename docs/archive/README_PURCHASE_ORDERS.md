# Documentación del Sistema de Órdenes de Pedido

Bienvenido a la documentación completa del sistema de órdenes de pedido en GrafiRed 3.0.

Esta documentación consta de **3 documentos principales** que se complementan entre sí.

---

## Documentos Disponibles

### 1. PURCHASE_ORDER_SYSTEM.md (757 líneas - 26 KB)
**Descripción**: Análisis técnico exhaustivo del sistema

**Contenido**:
- Visión general del sistema
- Descripción detallada de todos los modelos (PurchaseOrder, PurchaseOrderItem, OrderStatusHistory)
- Análisis completo de eventos y notificaciones
- Flujo de creación de órdenes paso a paso
- Sistema de notificaciones con diagrama de flujo
- Estado actual de eventos/listeners
- Interfaz Filament completa
- Configuración y campos
- Historial de cambios
- Mapeo línea por línea de código
- Ejemplos de código
- Notas sobre la arquitectura

**Cuándo leerlo**:
- Necesitas comprender a fondo cómo funciona todo
- Quieres modificar la lógica de notificaciones
- Necesitas debuggear problemas complejos
- Quieres entender la arquitectura multi-tenant

**Tiempo estimado**: 30-45 minutos

---

### 2. PURCHASE_ORDER_QUICK_REFERENCE.md (430 líneas - 13 KB)
**Descripción**: Guía de referencia rápida y visual

**Contenido**:
- Resumen ejecutivo (2 minutos)
- Archivos clave (tabla rápida)
- Flujos visuales de notificación
- Tabla de quién recibe notificaciones
- Puntos de código críticos
- Estados de órdenes
- Tabla pivot
- Métodos importantes
- Eventos y listeners
- Relaciones del modelo
- Ejemplos de código
- Checklist de verificación

**Cuándo leerlo**:
- Necesitas una referencia rápida
- Quieres recordar dónde está algo específico
- Necesitas los ejemplos de código
- Tienes 10 minutos para entender el sistema

**Tiempo estimado**: 15-20 minutos

---

### 3. PURCHASE_ORDER_FILE_REFERENCES.md (499 líneas - 15 KB)
**Descripción**: Índice detallado de archivos con ubicaciones exactas

**Contenido**:
- Resumen de ubicaciones de los 18 archivos
- Descripción de cada archivo con:
  - Ruta exacta
  - Número de líneas
  - Importancia (CRÍTICA/MEDIA/BAJA)
  - Hooks/métodos con números de línea
  - Relaciones
  - Imports clave
- Tabla de migración (schema)
- Resumen de flujo
- Checklist de archivos a modificar

**Cuándo leerlo**:
- Necesitas encontrar un archivo específico
- Quieres ir directamente a una línea de código
- Necesitas saber qué archivo modificar para algo específico
- Buscas hacer cambios precisos

**Tiempo estimado**: 10-15 minutos (referencia, no lectura lineal)

---

## Roadmap de Lectura Recomendado

### Opción A: Principiante (30 minutos)
1. Lee esta página (README)
2. Lee **PURCHASE_ORDER_QUICK_REFERENCE.md** - "Resumen Ejecutivo"
3. Lee "Flujo de Notificaciones (Visual)" 
4. Consulta ejemplos de código según necesidad

### Opción B: Desarrollador (45 minutos)
1. Lee **PURCHASE_ORDER_QUICK_REFERENCE.md** completo
2. Lee secciones específicas de **PURCHASE_ORDER_SYSTEM.md**:
   - Modelos (sección 1)
   - Flujo de creación (sección 3)
   - Sistema de notificaciones (sección 4)
3. Usa **PURCHASE_ORDER_FILE_REFERENCES.md** como referencia

### Opción C: Investigación Profunda (2 horas)
1. Lee **PURCHASE_ORDER_SYSTEM.md** completo (lineal)
2. Consulta **PURCHASE_ORDER_FILE_REFERENCES.md** para ubicaciones exactas
3. Abre el código fuente para verificar detalles

### Opción D: Búsqueda Específica
1. Usa **PURCHASE_ORDER_FILE_REFERENCES.md** para encontrar el archivo
2. Consulta **PURCHASE_ORDER_QUICK_REFERENCE.md** para contexto rápido
3. Lee sección específica de **PURCHASE_ORDER_SYSTEM.md** si necesitas detalle

---

## Preguntas Rápidas: Cuál Documento Leer

| Pregunta | Leer |
|----------|------|
| ¿Qué es el sistema de órdenes? | QUICK_REFERENCE.md (Resumen Ejecutivo) |
| ¿Cómo se crean las órdenes? | SYSTEM.md (Sección 3) |
| ¿A quién se envían notificaciones? | QUICK_REFERENCE.md (Tabla de notificaciones) |
| ¿Dónde está el archivo X? | FILE_REFERENCES.md |
| ¿Qué hace la línea Y del archivo Z? | SYSTEM.md (Sección 11: Mapeo línea por línea) |
| Necesito modificar notificaciones | SYSTEM.md (Sección 4) + QUICK_REFERENCE.md (Puntos críticos) |
| ¿Cómo funciona multi-tenant? | SYSTEM.md (Sección 1.1: Hook creating) |
| Necesito cambiar estados de órdenes | FILE_REFERENCES.md (OrderStatus.php) |
| ¿Cuál es el flujo visual? | QUICK_REFERENCE.md (Flujo de notificaciones) |
| Necesito ejemplos de código | QUICK_REFERENCE.md (Ejemplos de código) |

---

## Resumen Ejecutivo del Sistema

### ¿Qué es?
Sistema que permite crear **órdenes de compra** a proveedores desde **cotizaciones**.

### ¿Cómo funciona?
```
Cotización (Document)
    ↓
Usuario: Acción "Crear Órdenes de Pedido"
    ↓
Selecciona Items → Sistema agrupa por proveedor
    ↓
PurchaseOrder::create() → Hook: created
    ↓
Notificaciones automáticas enviadas ✉️
```

### 4 Puntos Clave
1. **Creación**: DocumentsTable.php línea 245-529 → Crea PurchaseOrder
2. **Notificación al Crear**: PurchaseOrder.php línea 63-80 → Notifica usuarios internos + proveedor
3. **Cambio de Estado**: PurchaseOrder.php línea 82-135 → Notifica cambios
4. **Vía**: Siempre email + base de datos (stored notification)

---

## Archivos Críticos (5 archivos)

| Archivo | Función | Líneas |
|---------|---------|--------|
| app/Models/PurchaseOrder.php | Modelo principal + hooks | 269 |
| app/Filament/Resources/Documents/Tables/DocumentsTable.php | Acción crear órdenes | 245-529 |
| app/Notifications/PurchaseOrderCreated.php | Notificación al crear | 59 |
| app/Notifications/PurchaseOrderStatusChanged.php | Notificación al cambiar | 73 |
| app/Filament/Resources/PurchaseOrders/Pages/EditPurchaseOrder.php | Edición + enviar email | 55 |

---

## Nota Importante sobre Eventos

**Situación Actual**:
- ✅ Evento `PurchaseOrderStatusChanged` está definido
- ❌ Listener `NotifyPurchaseOrderStatusChange` está vacío
- ✅ Las notificaciones se envían directamente en los **hooks del modelo**

**NO se usa el patrón Event/Listener**, se usan **Model Hooks** (creating, created, updating, updated).

---

## Multi-Tenancy

El sistema aisla órdenes por `company_id`:
- Cada usuario solo ve órdenes de su empresa
- O las que recibe como proveedor
- Query: `where('company_id', $userId->company_id) OR where('supplier_company_id', $userId->company_id)`

---

## Tabla Pivot

**Tabla**: `document_item_purchase_order`

Conecta:
- DocumentItem (del cotización) 
- PurchaseOrder (orden)

Con detalles:
- paper_id, paper_description
- quantity_ordered, sheets_quantity
- cut_width, cut_height
- unit_price, total_price
- status, notes

**Nota Especial**: MagazineItem y TalonarioItem generan **múltiples filas** (una por cada tipo de papel).

---

## Flujo Simplificado

```
CREAR ORDEN:
  Filament Action: "Crear Órdenes"
  ↓
  DocumentsTable (línea 245)
  ├─ Seleccionar items (formulario)
  ├─ Agrupar por proveedor (línea 347)
  └─ Para cada grupo:
     └─ PurchaseOrder::create() (línea 393)
        └─ Hook: created (PurchaseOrder línea 63)
           ├─ OrderStatusHistory::create()
           ├─ Si status='sent': Email a proveedor
           └─ PurchaseOrderCreated notification → usuarios internos


CAMBIAR ESTADO:
  EditPurchaseOrder: cambiar status
  ↓
  PurchaseOrder->update(['status' => 'sent'])
  ├─ Hook: updating (detecta cambio)
  └─ Hook: updated (línea 89)
     ├─ OrderStatusHistory::create()
     ├─ PurchaseOrderStatusChanged notification → usuarios internos
     └─ Si newStatus='sent': notificar proveedor
```

---

## Canales de Notificación

**Siempre**: `['mail', 'database']`

Significa:
- ✉️ **Email**: Se envía correo al destinatario
- 📱 **Database**: Se almacena en tabla `notifications` (Stored Notification)

**Ventaja**: El usuario ve la notificación en el sistema Y recibe email.

---

## Estados de Órdenes

```php
DRAFT              // Borrador (inicial)
SENT               // Enviada al proveedor
CONFIRMED          // Confirmada por proveedor
RECEIVED           // Completamente recibida
PARTIALLY_RECEIVED // Parcialmente recibida
CANCELLED          // Cancelada
```

---

## Relaciones Clave

### PurchaseOrder
```
company()             // Empresa que crea
supplierCompany()     // Empresa proveedor
createdBy()          // Usuario creador
documentItems()      // Items de cotización (BelongsToMany)
statusHistories()    // Historial de cambios
```

### DocumentItem ← PurchaseOrder
```
Relación: BelongsToMany
Tabla Pivot: document_item_purchase_order
```

---

## Servicios Relacionados

### PurchaseOrderPdfService
- Genera PDF de la orden
- Envía por email
- Usado en notificaciones y action "Enviar por Email"

---

## Validaciones

### Cambio de Estado
```php
$order->changeStatus($newStatus) → valida usando OrderStatus::canTransitionTo()
```

### Estimado de Crear Orden
```
Visible si: $record->canCreateOrders()
```

---

## Acciones Filament Disponibles

### En Document (Cotización)
- **Acción**: "Crear Órdenes de Pedido" (línea 245)
- **Visible si**: `canCreateOrders()` = true
- **Resultado**: Crea 1+ órdenes agrupadas por proveedor

### En PurchaseOrder (Orden)
- **Acción**: "Enviar por Email" (línea 17)
- **Función**: Genera PDF + envía por email
- **Usa**: `PurchaseOrderPdfService`

---

## Tiempo de Lectura

- **Este README**: 5 minutos
- **QUICK_REFERENCE.md**: 15-20 minutos
- **Secciones de SYSTEM.md**: 10-20 minutos (según necesidad)
- **FILE_REFERENCES.md**: Consulta (no lectura lineal)

**Total para entender completamente**: 30-45 minutos

---

## Cómo Usar Esta Documentación

1. **Primera vez**: Lee QUICK_REFERENCE.md sección "Resumen Ejecutivo"

2. **Necesito más detalles**: Ve a SYSTEM.md sección correspondiente

3. **Dónde está X?**: Busca en FILE_REFERENCES.md

4. **Quiero ver código**: Mira "Ejemplos de código" en QUICK_REFERENCE.md

5. **Necesito modificar**: 
   - Ve a FILE_REFERENCES.md → encontrar archivo
   - Abre SYSTEM.md sección 11 (Mapeo línea por línea)
   - Mira QUICK_REFERENCE.md "Puntos de código críticos"

---

## Notas Finales

1. **Las notificaciones son automáticas** - Se disparan en model hooks
2. **El sistema es multi-tenant** - Aislamiento por company_id
3. **El evento PurchaseOrderStatusChanged NO se usa** - Está definido pero vacío
4. **Las órdenes se agrupan inteligentemente** - Por proveedor y tipo de item
5. **MagazineItem/TalonarioItem son especiales** - Generan múltiples filas
6. **El historial es automático** - Cada cambio se registra en OrderStatusHistory

---

## Versión de Documentación

- **Fecha**: 06-Nov-2025
- **GrafiRed**: 3.0
- **Total líneas de documentación**: 1,686 líneas
- **Total KB**: 54 KB

---

## Próximos Pasos

1. Lee el documento que corresponda a tu necesidad
2. Abre los archivos mencionados en tu IDE
3. Busca las líneas específicas mencionadas
4. Experimenta con el código
5. Consulta nuevamente según necesites

Happy coding!

