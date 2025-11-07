# LitoPro 3.0 - SaaS para Litografías

## Stack & Arquitectura
- **Laravel 12.25.0 + PHP 8.3.21 + Filament 4.0.3 + MySQL**
- **Multi-tenant**: Scopes automáticos por `company_id`
- **Frontend**: Livewire 3.6.4 + TailwindCSS 4.1.12

## Comandos Core
```bash
php artisan test                    # Testing completo
php artisan pint && composer analyse    # Lint + análisis
php artisan migrate && php artisan db:seed  # Setup BD
php artisan litopro:setup-demo --fresh     # Demo completo
```

## Convenciones Filament v4

### Namespaces Críticos
- **Layout**: `Filament\Schemas\Components\*` (Section, Grid, Tab)
- **Forms**: `Filament\Forms\Components\*` (TextInput, Select, etc.)
- **Actions**: `Filament\Actions\*` (NO Tables\Actions)
- **Columns**: `Filament\Tables\Columns\*`
- **FileUpload**: SIEMPRE usar `->disk('public')` para archivos públicos

### Estructura Resources
```
app/Filament/Resources/[Entity]/
├── [Entity]Resource.php
├── Schemas/[Entity]Form.php
├── Tables/[Entity]sTable.php
└── Pages/
```

---

## PROGRESO RECIENTE

### ✅ Sesión Completada (06-Nov-2025 - Parte 6)
**SPRINT 15: Documentación Sistema de Notificaciones**

#### Logros de la Sesión

1. **✅ Análisis Completo del Sistema de Notificaciones**
   - **Alcance**: Exploración exhaustiva de 27 archivos (2600+ líneas de código)
   - **4 tipos de notificaciones identificados**:
     - Notificaciones Sociales (SocialNotification) - Red social interna
     - Alertas de Inventario (StockAlert + StockMovement) - Stock crítico
     - Sistema Avanzado (NotificationChannel + Rule + Log) - Canales configurables
     - Sistema Laravel Base (Notifications) - Notificaciones estándar

2. **✅ Documentación Técnica Generada (66 KB)**
   - `NOTIFICATION_SYSTEM_ANALYSIS.md` (40 KB) - Análisis técnico completo
   - `NOTIFICATION_SYSTEM_SUMMARY.md` (15 KB) - Resumen ejecutivo
   - `NOTIFICATION_FILE_REFERENCES.md` (11 KB) - Índice de archivos con líneas exactas
   - `README_NOTIFICATIONS.md` - Guía de navegación

3. **✅ Arquitectura Multi-Tenant Verificada**
   - Aislamiento automático por `company_id` en todos los modelos
   - 7 tablas de notificaciones documentadas con DDL completo
   - 2 servicios principales (NotificationService + StockNotificationService)
   - 5 canales de comunicación (email, database, SMS, push, custom)

#### Componentes Documentados

**Modelos (7)**:
- `SocialNotification` (11 campos) - Posts y actividad social
- `StockAlert` (27 campos) - Alertas de inventario crítico
- `StockMovement` (21 campos) - Movimientos de stock
- `NotificationChannel` (34 campos) - Canales configurables
- `NotificationRule` (49 campos) - Reglas de envío
- `NotificationLog` (40 campos) - Auditoría completa
- `Notification` (Laravel) - Sistema base

**Servicios (2)**:
- `NotificationService` (219 líneas, 7 métodos) - Servicio principal
- `StockNotificationService` (290 líneas, 8 métodos) - Alertas de stock

**Características Clave**:
- ✅ Multi-tenant con aislamiento automático
- ✅ Procesamiento asíncrono (Laravel Queue)
- ✅ Deduplicación de notificaciones
- ✅ Filtrado por rol y severidad
- ✅ Auditoría completa (notification_logs)
- ✅ Configuración flexible (canales + reglas)

#### Archivos de Documentación Creados

```
/home/dasiva/Descargas/litopro825/
├── NOTIFICATION_SYSTEM_ANALYSIS.md      # 40 KB - Análisis técnico
├── NOTIFICATION_SYSTEM_SUMMARY.md       # 15 KB - Guía rápida
├── NOTIFICATION_FILE_REFERENCES.md      # 11 KB - Índice de archivos
└── README_NOTIFICATIONS.md              # Navegación
```

---

### ✅ Sesión Completada (06-Nov-2025 - Parte 5)
**SPRINT 14.4: Fix de Verificación de Permisos en Acciones**

#### Logros de la Sesión

1. **✅ Problema Identificado: Permisos no se verificaban en acciones**
   - **Caso**: Usuario Salesperson sin permiso `create-posts` podía crear posts
   - **Causa raíz**: CreatePostWidget NO verificaba permisos antes de permitir la acción
   - **Alcance**: Problema encontrado en widgets y algunos recursos

2. **✅ Solución Implementada: Policy + Widget Protection**
   - **Creada Policy**: `SocialPostPolicy` con verificación completa
   - **Widget protegido**: `CreatePostWidget` ahora verifica permisos
   - **Métodos agregados**:
     - `canView()` - Solo muestra widget si puede crear posts
     - Verificación en `createPost()` antes de ejecutar acción

3. **✅ Arquitectura de Permisos Explicada**
   - **Spatie Permission**: Base del sistema (roles, permisos, BD)
   - **Laravel Policies**: Capa de lógica de negocio
   - **Filament Resources**: Capa de interfaz (canViewAny, canCreate, etc.)
   - **Combinación**: Máxima seguridad con 3 capas de verificación

#### Archivos Creados/Modificados

1. **Creado**: `app/Policies/SocialPostPolicy.php`
   - `viewAny()`: Requiere `view-posts`
   - `create()`: Requiere `create-posts`
   - `update()`: Requiere `edit-posts` O ser autor
   - `delete()`: Requiere `delete-posts` O ser autor
   - Todas las acciones verifican `company_id`

2. **Modificado**: `app/Filament/Widgets/CreatePostWidget.php`
   - Agregado `canView()`: Oculta widget si no puede crear
   - Agregada verificación en `createPost()`: Previene acción si no tiene permiso

#### Estado de Verificación de Permisos por Recurso

| Recurso | Estado | Protección |
|---------|--------|------------|
| Users | ✅ Completo | Policy + canViewAny() |
| Roles | ✅ Completo | Policy + canViewAny() |
| Papers | ✅ Completo | canViewAny() |
| PrintingMachines | ✅ Completo | canViewAny() |
| Finishings | ✅ Completo | canViewAny() |
| CollectionAccounts | ✅ Completo | canViewAny() |
| Posts (Widget) | ✅ Completo | Policy + canView() |
| Documents | ⚠️ Parcial | Solo Policy |
| Contacts | ⚠️ Parcial | Solo Policy |
| Products | ⚠️ Parcial | Solo Policy |
| SimpleItems | ⚠️ Parcial | Solo Policy |
| PurchaseOrders | ⚠️ Parcial | Solo Policy |
| ProductionOrders | ❌ Sin verificación | Ninguna |

#### Métodos de Verificación de Permisos

**Usando Spatie Permission (Base):**
```php
// Verificar permiso directo
$user->hasPermissionTo('create-posts')

// Verificar rol
$user->hasRole('Manager')

// Verificar cualquier rol
$user->hasAnyRole(['Manager', 'Admin'])
```

**Usando Policies (Recomendado):**
```php
// En código
$user->can('create', SocialPost::class)
$user->can('update', $post)

// En Filament Resources
public static function canViewAny(): bool {
    return auth()->user()->can('viewAny', Model::class);
}
```

**Arquitectura (3 Capas):**
```
Interfaz (Resource/Widget)
    ↓ can('create', Model)
Policy (Lógica de Negocio)
    ↓ hasPermissionTo('create-posts')
Spatie (Base de Datos)
    ↓ role_has_permissions
✅ Acceso Permitido
```

#### Testing Realizado

✅ **Caso 1: Salesperson sin create-posts**
- Widget "Crear Post" NO aparece en dashboard
- Si intenta acceder por URL: Error 403

✅ **Caso 2: Manager con create-posts**
- Widget visible
- Puede crear posts exitosamente

---

### ✅ Sesión Completada (06-Nov-2025 - Parte 4)
**SPRINT 14.3: Fix de Interfaz de Gestión de Roles**

#### Logros de la Sesión

1. **✅ Problema Identificado: Formulario de roles incompleto**
   - **Causa raíz**: Solo mostraba 43 permisos de 56 existentes en BD
   - **Permisos faltantes**:
     - Gestión de Empresas (view/create/edit/delete-companies)
     - Inventario (manage-inventory, manage-paper-catalog, manage-printing-machines)
   - **Resultado**: No se podían asignar todos los permisos disponibles

2. **✅ Solución Implementada: Categorías Completas**
   - **Nueva sección agregada**: "Gestión de Empresas" (solo Super Admin)
   - **Nueva sección agregada**: "Inventario"
   - **Formulario actualizado**: Ahora muestra TODOS los 56 permisos del sistema
   - **Categorización mejorada**: Separación clara entre inventario y sistema

3. **✅ Archivos Actualizados**
   - `RoleForm.php`: Agregadas secciones de Companies e Inventory
   - `EditRole.php`: Actualizado para cargar/guardar nuevas categorías
   - Sincronización correcta entre formulario y BD

#### Archivos Modificados

1. `app/Filament/Resources/Roles/Schemas/RoleForm.php`
   - Agregada sección "Gestión de Empresas" (línea 93-102)
   - Agregada sección "Inventario" (línea 104-111)
   - Actualizado `getPermissionsByCategory()` con nuevas categorías (línea 152-153)

2. `app/Filament/Resources/Roles/Pages/EditRole.php`
   - Agregado `company_permissions` e `inventory_permissions` en carga (línea 28-29)
   - Agregado `company_permissions` e `inventory_permissions` en guardado (línea 59-60)

#### Permisos por Categoría Actualizados

```
Gestión de Usuarios: 4 permisos
Gestión de Contactos: 4 permisos
Cotizaciones: 6 permisos
Documentos: 5 permisos
Órdenes de Producción: 5 permisos
Órdenes de Papel: 4 permisos
Productos: 4 permisos
Equipos: 4 permisos
Empresas: 4 permisos (solo Super Admin)
Inventario: 3 permisos
Sistema: 6 permisos
Reportes: 2 permisos
Red Social: 5 permisos
---
TOTAL: 56 permisos ✅
```

---

### ✅ Sesión Completada (06-Nov-2025 - Parte 3)
**SPRINT 14.2: Fix Crítico de Permisos por Rol**

#### Logros de la Sesión

1. **✅ Problema Identificado: Salesperson tenía acceso a TODO**
   - **Causa raíz**: Recursos críticos NO tenían método `canViewAny()` configurado
   - **Afectados**: Papers, PrintingMachines, Finishings, CollectionAccounts
   - **Resultado**: Cualquier usuario autenticado podía acceder a estos recursos

2. **✅ Solución Implementada: Restricciones por Rol**
   - **Método agregado**: `canViewAny()` a recursos críticos
   - **Roles permitidos**: Solo `Super Admin`, `Company Admin`, `Manager`
   - **Salesperson bloqueado** de:
     - Papers (gestión de papeles)
     - PrintingMachines (máquinas de impresión)
     - Finishings (acabados)
     - CollectionAccounts (cuentas de cobro)

3. **✅ Sistema de Roles Verificado**
   - 8 roles en el sistema: Super Admin, Company Admin, Manager, Salesperson, Operator, Customer, Employee, Client
   - Salesperson tiene 15 permisos específicos (contactos, cotizaciones, órdenes de producción)
   - UserResource ya tenía restricciones correctas (solo Admin)
   - RoleResource ya tenía restricciones correctas (solo Admin)

#### Archivos Modificados

1. `app/Filament/Resources/Papers/PaperResource.php`
   - Agregado `canViewAny()` - Solo Admin/Manager (línea 42-46)

2. `app/Filament/Resources/PrintingMachines/PrintingMachineResource.php`
   - Agregado `canViewAny()` - Solo Admin/Manager (línea 45-49)

3. `app/Filament/Resources/Finishings/FinishingResource.php`
   - Agregado `canViewAny()` - Solo Admin/Manager (línea 44-48)

4. `app/Filament/Resources/CollectionAccounts/CollectionAccountResource.php`
   - Agregado `canViewAny()` - Solo Admin/Manager (línea 38-42)

#### Testing Sugerido

```bash
# Crear usuario Salesperson y verificar:
# ✅ Puede ver: Documents, Contacts, ProductionOrders
# ❌ NO puede ver: Papers, PrintingMachines, Finishings, CollectionAccounts, Users, Roles
```

---

### ✅ Sesión Completada (06-Nov-2025 - Parte 2)
**SPRINT 14.1: UI de Acabados + Fix de Billing**

#### Logros de la Sesión

1. **✅ Interfaz de Acabados en SimpleItem**
   - **Archivo**: `app/Filament/Resources/SimpleItems/Schemas/SimpleItemForm.php`
   - **Nueva sección**: "🎨 Acabados Sugeridos" (collapsed por defecto)
   - **Características**:
     - Repeater con relación `finishings` (tabla pivot)
     - Auto-población de parámetros según tipo de acabado
     - Campos dinámicos (cantidad para MILLAR/RANGO/UNIDAD, ancho/alto para TAMAÑO)
     - Cálculo de costo en tiempo real
     - Total de acabados al final de la sección
     - Toggle `is_default` para marcar sugerencias automáticas

2. **✅ Ocultada Opción "Tiro y Retiro en Misma Plancha"**
   - **Cambio**: Removido Toggle `front_back_plate` de la interfaz
   - **Grid cambiado**: De 4 columnas a 3 columnas
   - **Backend intacto**: Campo sigue existiendo en BD pero no es visible

3. **✅ Fix Crítico: Redirección a /admin/billing**
   - **Problema**: Usuarios quedaban atrapados en página de billing
   - **Causa raíz 1**: Método `getCurrentPlan()` retornaba `null` para plan "free"
   - **Causa raíz 2**: Método buscaba por `name` en lugar de `slug`
   - **Causa raíz 3**: Company tenía `status = 'incomplete'` en lugar de `'active'`
   - **Solución**:
     - `app/Models/Company.php:313-321` - Corregido `getCurrentPlan()` para buscar por slug
     - Removida condición que excluía plan "free"
     - Actualizado status de empresa a 'active'

#### Testing Realizado

✅ **getCurrentPlan() corregido**:
```php
$company->subscription_plan = 'free';
$plan = $company->getCurrentPlan(); // Ahora retorna Plan Gratuito ✅
```

✅ **Interfaz de acabados**:
- Repeater funcional con relación pivot
- Auto-población de campos según tipo
- Cálculo en tiempo real funciona

#### Archivos Modificados

1. `app/Filament/Resources/SimpleItems/Schemas/SimpleItemForm.php`
   - Agregada sección de acabados (líneas 679-858)
   - Removido toggle `front_back_plate` (línea 169-199)

2. `app/Models/Company.php`
   - `getCurrentPlan()` ahora busca por `slug` en lugar de `name`
   - Removida exclusión de plan "free"

---

### ✅ Sprint 13 (05-Nov-2025)
**Nuevo Sistema de Montaje con Divisor de Cortes**
- Método `calculateMountingWithCuts()`: Integración MountingCalculatorService + CuttingCalculatorService
- Millares calculados sobre **impresiones** (no pliegos)
- Fórmula: `pliegos = ceil(impresiones ÷ divisor)`
- Ver sección "Notas Técnicas" para detalles de implementación

---

### ✅ Sprint 14 (06-Nov-2025)
**Sistema de Acabados para SimpleItem**
- Sistema híbrido: SimpleItem (sugerencias) + DocumentItem (aplicados)
- Tabla pivot `simple_item_finishing` con parámetros dinámicos
- Métodos: `addFinishing()`, `calculateFinishingsCost()`, `getFinishingsBreakdown()`
- Integración completa con SimpleItemCalculatorService
- Ver sección "Notas Técnicas" para ejemplos de uso

---

## 🎯 PRÓXIMA TAREA PRIORITARIA

**Completar Sistema de Permisos en Recursos Faltantes**

Recursos con verificación parcial (solo Policy, falta `canViewAny()`):
- Documents
- Contacts
- Products
- SimpleItems
- PurchaseOrders

Recursos sin verificación:
- ProductionOrders (sin Policy ni canViewAny)

**Acción requerida**: Agregar método `canViewAny()` a estos recursos para completar arquitectura de seguridad de 3 capas.

---

## COMANDO PARA EMPEZAR MAÑANA

```bash
# Iniciar LitoPro 3.0 - SPRINT 15 COMPLETADO (Documentación Sistema Notificaciones)
cd /home/dasiva/Descargas/litopro825 && php artisan serve --port=8000

# Estado del Proyecto
echo "✅ SPRINT 15 COMPLETADO (06-Nov-2025) - Sistema de Notificaciones Documentado"
echo ""
echo "📍 URLs de Testing:"
echo "   🏠 Dashboard: http://localhost:8000/admin"
echo "   📋 Cotizaciones: http://localhost:8000/admin/documents"
echo "   🔔 Sistema Notificaciones: Ver NOTIFICATION_SYSTEM_SUMMARY.md"
echo ""
echo "📚 DOCUMENTACIÓN GENERADA (66 KB):"
echo "   • NOTIFICATION_SYSTEM_ANALYSIS.md - Análisis técnico completo"
echo "   • NOTIFICATION_SYSTEM_SUMMARY.md - Guía rápida de uso"
echo "   • NOTIFICATION_FILE_REFERENCES.md - Índice de 27 archivos"
echo "   • README_NOTIFICATIONS.md - Navegación"
echo ""
echo "🔔 SISTEMA DE NOTIFICACIONES:"
echo "   • 4 tipos: Social, Stock, Avanzado, Laravel Base"
echo "   • 7 tablas multi-tenant con aislamiento por company_id"
echo "   • 2 servicios principales documentados"
echo "   • 5 canales: email, database, SMS, push, custom"
echo ""
echo "🎯 PRÓXIMA TAREA PRIORITARIA:"
echo "   Completar verificación canViewAny() en recursos faltantes:"
echo "   - Documents, Contacts, Products, SimpleItems"
echo "   - PurchaseOrders, ProductionOrders"
```

---

## Notas Técnicas Importantes

### Sistema de Notificaciones Multi-Tenant (Sprint 15)

**4 Tipos de Notificaciones**:

```php
// 1. NOTIFICACIONES SOCIALES (Red Social Interna)
use App\Models\SocialPost;

SocialPost::create([
    'company_id' => auth()->user()->company_id,
    'content' => 'Actualización importante...',
    'visibility' => 'company' // company, department, role
]);
// Genera notificaciones automáticamente en social_notifications

// 2. ALERTAS DE INVENTARIO (Stock Crítico)
use App\Services\StockNotificationService;

$service = app(StockNotificationService::class);
// Verifica automáticamente niveles críticos
// Tabla: stock_alerts (min_stock, current_stock, alert_level)

// 3. SISTEMA AVANZADO (Canales Configurables)
use App\Services\NotificationService;

$notificationService = app(NotificationService::class);
$notificationService->send(
    type: 'order_completed',
    userId: $user->id,
    data: ['order_id' => 123],
    priority: 'high' // low, medium, high, urgent
);
// Canales: email, database, SMS, push, custom
// Tablas: notification_channels, notification_rules, notification_logs

// 4. LARAVEL NOTIFICATIONS (Sistema Base)
$user->notify(new DocumentCreatedNotification($document));
```

**Aislamiento Multi-Tenant**:
- Todos los modelos tienen `company_id` scope global
- Usuario de Empresa A solo ve notificaciones de Empresa A
- Verificación automática en queries

**Documentación Completa**: Ver `NOTIFICATION_SYSTEM_SUMMARY.md` para guía de uso completa.

---

### Sistema de Acabados para SimpleItem (Sprint 14)

```php
use App\Models\SimpleItem;
use App\Models\Finishing;

// 1. AGREGAR ACABADOS A UN SIMPLEITEM
$item = SimpleItem::first();

// Opción A: Parámetros automáticos (usa dimensiones/cantidad del item)
$plastificado = Finishing::where('measurement_unit', 'millar')->first();
$item->addFinishing($plastificado);
// Construye automáticamente: ['quantity' => $item->quantity]

// Opción B: Parámetros manuales
$barnizUV = Finishing::where('measurement_unit', 'tamaño')->first();
$item->addFinishing($barnizUV, ['width' => 20, 'height' => 13], isDefault: true);

// 2. OBTENER DESGLOSE DETALLADO
$breakdown = $item->getFinishingsBreakdown();
// Retorna array con: finishing_id, finishing_name, measurement_unit, params, cost, is_default

// 3. CALCULAR COSTO TOTAL
$item->load('finishings'); // Cargar relación
$totalCost = $item->calculateFinishingsCost();

// 4. VERIFICAR SI TIENE ACABADOS
if ($item->hasFinishings()) {
    // Procesar acabados
}

// 5. PRICING COMPLETO CON ACABADOS
$pricing = $item->calculateAll();
// $pricing->costBreakdown['finishings'] incluye el costo de acabados
```

**Parámetros Auto-construidos por Tipo**:
- `MILLAR/RANGO/UNIDAD` → `['quantity' => $item->quantity]`
- `TAMAÑO` → `['width' => $item->horizontal_size, 'height' => $item->vertical_size]`
- Otros tipos → `[]` (parámetros vacíos)

**Integración con SimpleItemCalculatorService**:
```php
// Método privado que calcula acabados
private function calculateFinishingsCost(SimpleItem $item): float
{
    if (!$item->relationLoaded('finishings') || $item->finishings->isEmpty()) {
        return 0; // Opcional: no afecta si no hay acabados
    }

    $total = 0;
    $finishingCalculator = new FinishingCalculatorService();

    foreach ($item->finishings as $finishing) {
        $params = $this->buildFinishingParams($item, $finishing);
        $cost = $finishingCalculator->calculateCost($finishing, $params);
        $total += $cost;
    }

    return $total;
}
```

---

### Nuevo Sistema de Montaje con Divisor (Sprint 13)

```php
use App\Services\SimpleItemCalculatorService;

$calculator = new SimpleItemCalculatorService();

// PASO 1: Obtener montaje completo con divisor
$mountingWithCuts = $calculator->calculateMountingWithCuts($item);

// Resultado:
// [
//     'mounting' => [...],                  // Info del MountingCalculatorService
//     'copies_per_mounting' => 2,           // Copias en tamaño máquina
//     'divisor' => 4,                       // Cortes de máquina en pliego
//     'divisor_layout' => [
//         'horizontal_cuts' => 2,
//         'vertical_cuts' => 2
//     ],
//     'impressions_needed' => 500,          // 1000 ÷ 2
//     'sheets_needed' => 125,               // 500 ÷ 4
//     'total_impressions' => 500,           // 125 × 4
//     'total_copies_produced' => 1000,      // 500 × 2
//     'waste_copies' => 0,
//     'paper_cost' => 62500.0
// ]

// PASO 2: Calcular millares sobre IMPRESIONES
$printingCalc = $calculator->calculatePrintingMillaresNew($item, $mountingWithCuts);

// Resultado:
// PrintingCalculation {
//     totalColors: 4,
//     millaresRaw: 0.5,                     // 500 ÷ 1000
//     millaresFinal: 4,                     // ceil(0.5) × 4 colores
//     printingCost: 1400.0,
//     setupCost: 15000.0,
//     totalCost: 16400.0
// }

// PASO 3: Pricing completo
$pricingResult = $calculator->calculateFinalPricingNew($item);

// Usar en SimpleItem directamente:
$item = SimpleItem::first();
$details = $item->getMountingWithCuts();
// Retorna el mismo array que calculateMountingWithCuts()
```

### Diferencia: Sistema Anterior vs Nuevo

```php
// ❌ SISTEMA ANTERIOR (sin divisor)
// Trabajo 22×28 en pliego 100×70
// Montaje: 9 copias (3×3) directamente en pliego
// Pliegos: 1000 ÷ 9 = 112 pliegos
// Millares: 112 ÷ 1000 = 0.112 → 1 millar

// ✅ SISTEMA NUEVO (con divisor)
// Trabajo 22×28 en máquina 50×35 → Montaje: 2 copias
// Divisor: 50×35 en pliego 100×70 → 4 cortes
// Impresiones: 1000 ÷ 2 = 500
// Pliegos: 500 ÷ 4 = 125 pliegos
// Impresiones totales: 125 × 4 = 500
// Millares: 500 ÷ 1000 = 0.5 → 1 millar
```

### MountingCalculatorService - Cálculo Puro
```php
use App\Services\MountingCalculatorService;

$calc = new MountingCalculatorService();

// Calcular montaje (3 orientaciones)
$result = $calc->calculateMounting(
    workWidth: 22.0,       // Ancho del trabajo en cm
    workHeight: 28.0,      // Alto del trabajo en cm
    machineWidth: 50.0,    // Ancho máximo máquina en cm
    machineHeight: 35.0,   // Alto máximo máquina en cm
    marginPerSide: 1.0     // Margen por lado en cm
);

// Resultado:
// [
//     'horizontal' => ['copies_per_sheet' => 2, 'layout' => '1 × 2', ...],
//     'vertical' => ['copies_per_sheet' => 2, 'layout' => '2 × 1', ...],
//     'maximum' => ['copies_per_sheet' => 2, ...] // La mejor opción
// ]

// Calcular pliegos necesarios
$sheets = $calc->calculateRequiredSheets(500, 2);
// ['sheets_needed' => 250, 'total_copies_produced' => 500, 'waste_copies' => 0]
```

### Integración con SimpleItem
```php
$item = SimpleItem::first();

// Obtener montaje completo
$mounting = $item->getPureMounting();
// Retorna: ['horizontal', 'vertical', 'maximum', 'sheets_info', 'efficiency']

// Solo la mejor opción
$best = $item->getBestMounting();
// Retorna: ['copies_per_sheet' => 2, 'layout' => '2 × 1', ...]
```

### Calculadora de Cortes - SVG Boundary Validation
```php
// app/Filament/Widgets/CalculadoraCorteWidget.php

// Validación antes de dibujar cada pieza
$pieceEndX = $x + $pieceWidth;
$pieceEndY = $y + $pieceHeight;

if ($pieceEndX <= $svgWidth && $pieceEndY <= $svgHeight) {
    // Dibujar pieza
    $svg .= '<rect x="' . $x . '" y="' . $y . '" ...>';
}
```

### Purchase Orders - Multi-Paper Support
```php
// PurchaseOrderItem (pivot como entity)
// Permite múltiples rows por DocumentItem (revistas con varios papeles)

// Relación en PurchaseOrder:
public function purchaseOrderItems(): HasMany {
    return $this->hasMany(PurchaseOrderItem::class);
}

// Accessor con carga dinámica:
public function getPaperNameAttribute(): string {
    if ($this->paper_description) return $this->paper_description;
    if ($this->paper_id && $this->paper) return $this->paper->name;

    // Carga itemable dinámicamente si no está cargado
    if (!$this->documentItem->relationLoaded('itemable')) {
        $this->documentItem->load('itemable');
    }
}
```

### Filament Pages - Slug Pattern
```php
// ✅ CORRECTO: Slug dinámico con parámetro Panel
public static function getSlug(?\Filament\Panel $panel = null): string {
    return 'empresa/{slug}';
}
```

### Document Relationships
```php
// ✅ CORRECTO: Relación definida como items()
$document->items()->create([...]);

// ❌ INCORRECTO: documentItems() no existe
public function items(): HasMany {
    return $this->hasMany(DocumentItem::class);
}
```
