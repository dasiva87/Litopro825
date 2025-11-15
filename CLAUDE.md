# LitoPro 3.0 - SaaS para Litografías

## Stack & Arquitectura
- **Laravel 12.25.0 + PHP 8.3.21 + Filament 4.0.3 + MySQL**
- **Multi-tenant**: Scopes automáticos por `company_id`
- **Frontend**: Livewire 3.6.4 + TailwindCSS 4.1.12

## Comandos Core
```bash
php artisan test                              # Testing completo
php artisan pint && composer analyse          # Lint + análisis
php artisan migrate && php artisan db:seed    # Setup BD
php artisan litopro:setup-demo --fresh        # Demo completo
php artisan serve --port=8000                 # Servidor local
```

## Convenciones Filament v4
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

## 🎯 ÚLTIMA SESIÓN COMPLETADA

### ✅ Sesión (12-Nov-2025)
**SPRINT 19: Sistema Completo de Acabados para SimpleItems**

#### Logros Principales

1. **✅ Sistema de Acabados Completo en SimpleItems**
   - **Tabla Pivot**: `simple_item_finishing` (many-to-many)
   - **Campos**: finishing_id, quantity, width, height, calculated_cost, is_default, sort_order
   - **Relación en Finishing**: `simpleItems()` agregada (simetría con digitalItems)
   - **Formulario**: Repeater con 6 tipos de acabados soportados
   - **Auto-población**: Campos se llenan automáticamente desde dimensiones del item

2. **✅ 6 Tipos de Acabados Soportados**
   - **MILLAR**: `ceil(cantidad ÷ 1000) × precio_millar`
   - **RANGO**: Busca rango según cantidad, retorna precio fijo
   - **UNIDAD**: `cantidad × precio_unitario`
   - **TAMAÑO**: `ancho × alto × cantidad × precio_unitario` ⭐
   - **POR_NUMERO**: `cantidad × precio_por_numero` (numeración)
   - **POR_TALONARIO**: `cantidad × precio_por_talonario` (bloques)

3. **✅ Fixes Críticos Aplicados**
   - **Colisión de nombres**: Repeater renombrado `simple_item_finishings` (evita conflicto con DocumentItem::finishings)
   - **Fórmula TAMAÑO corregida**: Ahora multiplica por cantidad (antes solo calculaba costo unitario)
   - **Relación faltante**: Agregado `Finishing::simpleItems()` para Policy
   - **Toggle removido**: Campo `is_default` oculto de UI (se guarda automáticamente como true)

4. **✅ Integración con Calculadora**
   - `SimpleItemCalculatorService::calculateFinishingsCost()` suma acabados al total
   - `FinishingCalculatorService` con 6 tipos completamente implementados
   - Validaciones completas para cada tipo de medición
   - Preview en tiempo real en formulario

#### Archivos Clave

**Modelos**:
- `app/Models/SimpleItem.php` - Métodos: `addFinishing()`, `calculateFinishingsCost()`, `getFinishingsBreakdown()`, `buildFinishingParams()`
- `app/Models/Finishing.php` - Relación `simpleItems()` agregada

**Servicios**:
- `app/Services/FinishingCalculatorService.php` - 6 tipos de cálculo implementados
- `app/Services/SimpleItemCalculatorService.php` - Integración con acabados

**Formularios**:
- `app/Filament/Resources/SimpleItems/Schemas/SimpleItemForm.php` - Sección "🎨 Acabados"
- `app/Filament/Resources/Documents/RelationManagers/DocumentItemsRelationManager.php` - Carga/guardado de acabados
- `app/Filament/Resources/Documents/RelationManagers/Handlers/SimpleItemQuickHandler.php` - Sincronización de acabados

**Policies**:
- `app/Policies/FinishingPolicy.php` - Protección: no eliminar si tiene items asociados

#### Migraciones
- `2025_11_06_030243_create_simple_item_finishing_table.php`

---

## 📚 SISTEMA DE ACABADOS - Guía Técnica

### Arquitectura de Relaciones

```
┌─────────────────────────────────────────────────────────────┐
│                      FINISHING                               │
│                  (Modelo de Acabados)                        │
└──────────────────────┬──────────────────────────────────────┘
                       │
        ┌──────────────┴──────────────┐
        │                             │
        ▼                             ▼
┌───────────────────┐       ┌──────────────────────┐
│   DigitalItem     │       │    SimpleItem        │
├───────────────────┤       ├──────────────────────┤
│ ✅ digitalItems() │       │ ✅ simpleItems()     │
│    (en Finishing) │       │    (en Finishing)    │
│ ✅ finishings()   │       │ ✅ finishings()      │
│    (en Digital)   │       │    (en SimpleItem)   │
└───────────────────┘       └──────────────────────┘
        │                            │
        ▼                            ▼
digital_item_finishing     simple_item_finishing
   (tabla pivot)             (tabla pivot)
```

### Uso del Sistema

#### 1. Agregar Acabado a SimpleItem
```php
$item = SimpleItem::create([
    'description' => 'Tarjetas de presentación',
    'quantity' => 1000,
    'horizontal_size' => 9,
    'vertical_size' => 5,
]);

// Opción A: Parámetros automáticos (usa dimensiones del item)
$plastificado = Finishing::find(1);
$item->addFinishing($plastificado);

// Opción B: Parámetros manuales (para acabados selectivos)
$barnizUV = Finishing::find(2);
$item->addFinishing($barnizUV, ['width' => 5, 'height' => 3], isDefault: false);
```

#### 2. Calcular Costo de Acabados
```php
// Cargar relación
$item->load('finishings');

// Calcular total
$costoAcabados = $item->calculateFinishingsCost();

// Obtener desglose detallado
$desglose = $item->getFinishingsBreakdown();
// Retorna: [['finishing_name' => 'Barniz UV', 'cost' => 50000, ...], ...]
```

#### 3. Crear Acabado Tipo TAMAÑO
```php
$acabado = Finishing::create([
    'name' => 'Barniz UV Brillante',
    'measurement_unit' => FinishingMeasurementUnit::TAMAÑO,
    'unit_price' => 100, // $100 por cm²
]);

// Calcular costo
$calculator = new FinishingCalculatorService();
$costo = $calculator->calculateCost($acabado, [
    'width' => 21.5,
    'height' => 28,
    'quantity' => 1000
]);
// Resultado: (21.5 × 28 × 1000) × 100 = $60,200,000
```

### Fórmulas por Tipo

| Tipo | Parámetros | Fórmula | Ejemplo |
|------|-----------|---------|---------|
| **MILLAR** | quantity | `ceil(qty ÷ 1000) × precio` | 1,500 unid → 2 millares × $50k = $100k |
| **UNIDAD** | quantity | `quantity × precio` | 100 unid × $500 = $50k |
| **TAMAÑO** | width, height, quantity | `(w × h × qty) × precio` | (10×15×1000) × $50 = $7,500k |
| **RANGO** | quantity | Busca rango → precio fijo | 750 unid → rango 501-1000 = $50k |
| **POR_NUMERO** | quantity | `quantity × precio` | 100 números × $50 = $5k |
| **POR_TALONARIO** | quantity | `quantity × precio` | 10 talonarios × $2k = $20k |

---

## 🔧 SISTEMAS PRINCIPALES

### Sistema de Montaje con Divisor
```php
// Obtener montaje completo con divisor de cortes
$mountingWithCuts = $calculator->calculateMountingWithCuts($item);

// Resultado incluye:
// - mounting: Info del MountingCalculatorService
// - copies_per_mounting: Copias en tamaño máquina
// - divisor: Cortes de máquina en pliego
// - impressions_needed: Cantidad ÷ copias_per_mounting
// - sheets_needed: Impresiones ÷ divisor
// - paper_cost: Costo calculado del papel
```

### Sistema de Permisos (3 Capas)
```
Interfaz (Resource/Widget)
    ↓ can('action', Model)
Policy (Lógica de Negocio)
    ↓ hasPermissionTo('permission')
Spatie (Base de Datos)
    ↓ role_has_permissions
✅ Acceso Permitido/Denegado
```

**Estado**: 12/12 recursos con verificación completa

---

## 🎯 PRÓXIMAS TAREAS

### Implementar Acabados en Otros Items
- [ ] **DigitalItems**: Ya tiene sistema implementado, verificar consistencia
- [ ] **MagazineItems**: Agregar sistema de acabados
- [ ] **TalonarioItems**: Agregar sistema de acabados
- [ ] **CustomItems**: Agregar sistema de acabados
- [ ] **Products**: Evaluar si necesita acabados

### Mejoras Pendientes
- [ ] Widget de estadísticas de acabados más usados
- [ ] Matriz de permisos por rol documentada
- [ ] Testing automatizado de permisos
- [ ] Seeders para testing completo

---

## 📋 NOTAS TÉCNICAS IMPORTANTES

### Acabados: Parámetros Auto-construidos
```php
// En SimpleItem/DigitalItem::buildFinishingParams()
MILLAR/RANGO/UNIDAD/POR_NUMERO/POR_TALONARIO → ['quantity' => $item->quantity]
TAMAÑO → ['width' => $item->horizontal_size, 'height' => $item->vertical_size, 'quantity' => $item->quantity]
```

### FileUpload - Configuración Correcta
```php
FileUpload::make('image')
    ->disk('public')              // SIEMPRE disk public
    ->directory('products')        // Directorio específico
    ->image()                      // Solo imágenes
    ->maxSize(2048);              // 2MB máximo
```

### ImageColumn - Mostrar Imágenes
```php
ImageColumn::make('image')
    ->disk('public')              // CRÍTICO: agregar disk
    ->circular()
    ->defaultImageUrl(url('/images/placeholder.png'));
```

### Relaciones Many-to-Many con Pivot
```php
// En el modelo
public function finishings(): BelongsToMany
{
    return $this->belongsToMany(Finishing::class, 'simple_item_finishing')
        ->withPivot(['quantity', 'width', 'height', 'calculated_cost'])
        ->withTimestamps();
}

// Sincronizar datos
$item->finishings()->sync([
    $finishing->id => [
        'quantity' => 1000,
        'calculated_cost' => 50000
    ]
]);
```

### Filament Repeater - Evitar Colisiones
```php
// ❌ MALO: Nombre colisiona con relación
Repeater::make('finishings')

// ✅ BUENO: Nombre único
Repeater::make('simple_item_finishings')
```

---

## 🚀 COMANDO PARA EMPEZAR

```bash
cd /home/dasiva/Descargas/litopro825 && php artisan serve --port=8000

# URLs de Testing
echo "🏠 Dashboard: http://127.0.0.1:8000/admin"
echo "📦 Productos: http://127.0.0.1:8000/admin/products"
echo "📋 SimpleItems: http://127.0.0.1:8000/admin/simple-items"
echo "🎨 Acabados: http://127.0.0.1:8000/admin/finishings"

# ⚠️ IMPORTANTE: Usar 127.0.0.1 (NO localhost) - CORS configurado
```

---

## 📖 DOCUMENTACIÓN ADICIONAL

- `LITOPRO_SITEMAP.md` (145 KB) - Sitemap completo del SaaS
- `NOTIFICATION_SYSTEM_SUMMARY.md` (15 KB) - Sistema de notificaciones
- `NOTIFICATION_SYSTEM_ANALYSIS.md` (40 KB) - Análisis técnico notificaciones

---

## 🔍 REFERENCIAS RÁPIDAS

### Git Status (Snapshot inicial)
```
Current branch: main
Status: M app/Filament/Resources/...
Recent commits:
- ac218ad: Botones agregar item solo en modo edición
- f219a3f: Pequeños detalles UI
```

### Configuración Importante
- **APP_URL**: `http://127.0.0.1:8000` (NO localhost)
- **Disk público**: Configurado en `config/filesystems.php`
- **Multi-tenant**: Scope global por `company_id`

---

**Última actualización**: 12-Nov-2025 - Sprint 19 Completado
