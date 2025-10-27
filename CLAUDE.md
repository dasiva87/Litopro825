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

### ✅ Sesión Completada (26-Oct-2025)
**SPRINT 12.5: Calculadora de Cortes - SVG Boundary Fix**

#### Logros de la Sesión

1. **✅ Fix Overflow en SVG de Calculadora de Cortes**
   - **Problema**: Piezas auxiliares (naranja) se dibujaban fuera de los límites del papel
   - **Solución**: Validación de límites antes de dibujar cada pieza
   - **Archivo**: `app/Filament/Widgets/CalculadoraCorteWidget.php`
   - **Cambios**:
     - Líneas 410-423: Validación para arreglo principal (verde)
     - Líneas 453-466: Validación para arreglo auxiliar (naranja)
   - **Lógica**: Solo dibujar si `pieceEndX <= svgWidth && pieceEndY <= svgHeight`

2. **✅ Testing Completo en Todos los Modos**
   - **Caso 100×70 con 42×28**:
     - Óptimo: 5 piezas ✅
     - Vertical: 5 piezas ✅
     - Horizontal: 4 piezas ✅
   - **Caso 70×100 con 22×28** (regresión):
     - Óptimo: 10 piezas ✅
   - Todas las piezas dentro de límites del papel

#### Testing Realizado

✅ **Papel siempre vertical** (portrait orientation)
✅ **Piezas rotan** según modo (no el papel)
✅ **Arreglo principal** (verde) + **arreglo auxiliar** (naranja)
✅ **Sin overflow** en ningún modo

---

### ✅ Sesión Completada (26-Oct-2025 - Anterior)
**SPRINT 12: MountingCalculatorService - Cálculo Puro de Montaje**

#### Logros de la Sesión

1. **✅ Nuevo Servicio: MountingCalculatorService**
   - **Propósito**: Cálculo puro de montaje (cuántas copias caben en un pliego)
   - **Características**:
     - Totalmente desacoplado de modelos (función pura)
     - Reutilizable para SimpleItem, MagazineItem, o cualquier tipo
     - 3 métodos principales: `calculateMounting()`, `calculateRequiredSheets()`, `calculateEfficiency()`
   - **Inputs**: Dimensiones del trabajo, dimensiones de la máquina, márgenes
   - **Outputs**: Montaje horizontal, vertical, y máximo (mejor opción)

2. **✅ Integración con SimpleItemCalculatorService**
   - **Nuevo método**: `calculatePureMounting()` - usa MountingCalculatorService
   - **Retrocompatibilidad**: Método `calculateMountingOptions()` sigue funcionando

3. **✅ Métodos Agregados a SimpleItem**
   - `getPureMounting()`: Retorna montaje completo (horizontal, vertical, maximum)
   - `getBestMounting()`: Retorna solo el mejor montaje (maximum)

#### Archivos Creados

**Servicio**: `app/Services/MountingCalculatorService.php`
**Documentación**: `MOUNTING_SERVICE_USAGE.md`, `TEST_MOUNTING_INTEGRATION.md`

---

### ✅ Sesión Completada (25-Oct-2025)
**SPRINT 11: Purchase Orders - Magazine Items Multi-Paper Support**

#### Logros

1. **✅ Purchase Orders para Magazine Items**
   - Sistema de rows múltiples - un row por cada tipo de papel usado
   - Migración: `add_paper_details_to_document_item_purchase_order_table`
   - Modelo nuevo: `PurchaseOrderItem` (pivot table como entity)

2. **✅ Accessor paper_name Optimizado**
   - Carga dinámica: Solo carga relaciones cuando son necesarias
   - Maneja 3 casos: SimpleItem (papel), Product, MagazineItem

---

## 🎯 PRÓXIMA TAREA PRIORITARIA
**Sprint 13: Nuevo Sistema de Montaje para SimpleItem**

### Contexto

El usuario quiere implementar un **nuevo sistema de cálculo de montaje** para SimpleItem:

**Concepto**: Si un trabajo cabe múltiples veces en el tamaño máximo de la máquina, usar ese montaje para reducir pliegos necesarios.

**Ejemplo**:
- Trabajo: 22×28cm
- Máquina: 50×35cm
- Montaje: 2 copias caben en 50×35 (1×2 o 2×1)
- Papel disponible: 100×70cm (pliego completo)
- Corte: 50×35 es 1/4 de 100×70 (100÷50=2, 70÷35=2 → 2×2=4)

**Cálculo**:
```
Cantidad: 1000 membretes
Montaje: 2 copias por impresión
Corte: 1/4 de pliego
Pliegos = (1000 ÷ 2) ÷ 4 = 125 pliegos + sobrante
```

### Preguntas Pendientes (Usuario debe responder)

1. **¿El papel siempre será el pliego completo (70×100)?** ¿O puede haber papeles ya cortados a 50×35?

2. **¿El divisor (1/4, 1/2, etc.) se calcula automáticamente** comparando:
   - Tamaño máximo de máquina (50×35)
   - Tamaño del papel disponible (100×70)

   O ¿se debe ingresar manualmente?

3. **¿Cómo afecta esto a la impresión?**:
   - ¿Se imprime 1 vez por cada papel de 50×35 (con 2 copias montadas)?
   - ¿O se imprimen las 2 copias en pasadas separadas?

4. **¿El cálculo de millares cambia?**:
   - Antes: `millares = 1000 pliegos / 1000 = 1 millar`
   - Ahora: `millares = 125 pliegos / 1000 = 0.125 millares`

   ¿O se calcula sobre las **impresiones** (125 impresiones × 2 copias = 250)?

### Tareas Técnicas (una vez aclarado)

1. **Modificar SimpleItemCalculatorService**:
   - Usar `MountingCalculatorService::calculateMounting()` con tamaño de máquina
   - Calcular divisor de papel (cuántas veces cabe tamaño máquina en pliego)
   - Ajustar cálculo de pliegos: `(cantidad ÷ montaje) ÷ divisor + sobrante`

2. **Actualizar campos en SimpleItem**:
   - `mounting_quantity`: Copias que caben en tamaño máximo máquina
   - `paper_cuts_h/v`: Cuántos cortes del tamaño de máquina en el pliego
   - `sheets_needed`: Pliegos necesarios con nuevo cálculo

3. **Actualizar cálculo de millares**:
   - Definir si millares = impresiones o pliegos × montaje
   - Ajustar `calculatePrintingMillares()` en SimpleItemCalculatorService

---

## COMANDO PARA EMPEZAR MAÑANA

```bash
# Iniciar LitoPro 3.0 - SPRINT 12.5 COMPLETADO (Calculadora SVG Fix)
cd /home/dasiva/Descargas/litopro825 && php artisan serve --port=8000

# URLs Operativas
echo "✅ SPRINT 12.5 COMPLETADO (26-Oct-2025) - Calculadora de Cortes SVG Fix"
echo ""
echo "📍 URLs de Testing:"
echo "   🏠 Home (Calculadora): http://localhost:8000/admin/home"
echo "   📋 Cotizaciones: http://localhost:8000/admin/documents"
echo "   🛒 Purchase Orders: http://localhost:8000/admin/purchase-orders"
echo "   🏭 Production Orders: http://localhost:8000/admin/production-orders"
echo ""
echo "✅ CAMBIOS SESIÓN 26-OCT (PARTE 2):"
echo "   ✅ Calculadora de Cortes: Fix overflow de piezas auxiliares en SVG"
echo "   ✅ Validación de límites: Solo dibujar piezas dentro del papel"
echo "   ✅ Testing: ✅ 100×70 con 42×28, ✅ 70×100 con 22×28"
echo "   ✅ 3 modos: Óptimo, Vertical, Horizontal - todos funcionando"
echo ""
echo "✅ CAMBIOS SESIÓN 26-OCT (PARTE 1):"
echo "   ✅ MountingCalculatorService: Nuevo servicio para cálculo puro de montaje"
echo "   ✅ SimpleItem: getPureMounting() y getBestMounting() agregados"
echo "   ✅ Documentación: MOUNTING_SERVICE_USAGE.md + TEST_MOUNTING_INTEGRATION.md"
echo ""
echo "🎯 PRÓXIMA SESIÓN: Sprint 13 - Nuevo Sistema de Montaje SimpleItem"
echo ""
echo "❓ DECISIONES PENDIENTES (USUARIO DEBE RESPONDER):"
echo "   1. ¿Papel siempre es pliego completo o puede ser pre-cortado?"
echo "   2. ¿Divisor se calcula automático o manual?"
echo "   3. ¿Impresión: 1 pasada con montaje o pasadas separadas?"
echo "   4. ¿Millares = impresiones o pliegos × montaje?"
echo ""
echo "💡 CONCEPTO NUEVO SISTEMA:"
echo "   Trabajo 22×28 → Caben 2 en máquina 50×35"
echo "   Papel 100×70 → 50×35 es 1/4 de pliego"
echo "   1000 membretes ÷ 2 (montaje) ÷ 4 (corte) = 125 pliegos"
echo ""
echo "📍 Una vez aclarado, modificar SimpleItemCalculatorService"
```

---

## Notas Técnicas Importantes

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
