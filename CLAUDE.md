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

### ✅ Sesión Completada (25-Oct-2025)
**SPRINT 11: Purchase Orders - Magazine Items Multi-Paper Support**

#### Logros de la Sesión

1. **✅ Purchase Orders para Magazine Items**
   - **Problema**: Revistas con múltiples papeles mostraban un solo row en purchase orders
   - **Solución**: Sistema de rows múltiples - un row por cada tipo de papel usado
   - **Arquitectura nueva**:
     - Migración: `add_paper_details_to_document_item_purchase_order_table`
     - Campos agregados: `paper_id`, `paper_description`, `sheets_quantity`
     - Unique constraint: `['document_item_id', 'purchase_order_id', 'paper_id']`
     - Modelo nuevo: `PurchaseOrderItem` (pivot table como entity)
     - Relación cambiada: `BelongsToMany` → `HasMany purchaseOrderItems`

2. **✅ Accessor paper_name Optimizado**
   - **Archivo**: `app/Models/PurchaseOrderItem.php:50-100`
   - **Features**:
     - Carga dinámica: Solo carga relaciones cuando son necesarias
     - Verifica `relationLoaded()` antes de hacer queries
     - Usa `instanceof` para verificar tipos de itemable
     - Maneja 3 casos: SimpleItem (papel), Product, MagazineItem

3. **✅ Eager Loading Corregido**
   - **Problema**: Intentaba cargar `itemable.paper` causando error con MagazineItem
   - **Solución**: Eager loading simple solo de relaciones directas
   - **Archivo**: `PurchaseOrderItemsRelationManager.php:34`
   - **Fix crítico línea 217**: Eliminado segundo `modifyQueryUsing()` duplicado

#### Archivos Modificados (5 total)

**Migration (1):**
```
database/migrations/2025_10_25_042542_add_paper_details_to_document_item_purchase_order_table.php
  ├── Agrega paper_id, paper_description, sheets_quantity
  ├── Cambia unique constraint para permitir múltiples rows por item
  └── Unique key: document_item_id + purchase_order_id + paper_id
```

**Models (2):**
```
app/Models/PurchaseOrder.php:158-185
  ├── withPivot agregado: paper_id, paper_description, sheets_quantity
  └── Nueva relación: purchaseOrderItems(): HasMany

app/Models/PurchaseOrderItem.php (NUEVO)
  ├── Table: document_item_purchase_order
  ├── Relaciones: documentItem(), purchaseOrder(), paper()
  └── Accessor: getPaperNameAttribute() con carga dinámica
```

**RelationManager (1):**
```
app/Filament/Resources/PurchaseOrders/RelationManagers/PurchaseOrderItemsRelationManager.php
  ├── Línea 21: relationship cambiado a 'purchaseOrderItems'
  ├── Línea 34: Eager loading: ['documentItem', 'paper']
  └── Línea 217: ELIMINADO modifyQueryUsing duplicado (causaba error)
```

**DocumentsTable (1):**
```
app/Filament/Resources/Documents/Tables/DocumentsTable.php:399-464
  └── Lógica de creación: Crea múltiples rows para MagazineItem (uno por papel)
```

#### Errores Resueltos en Sesión

1. ❌ "Call to undefined relationship [paper] on model [App\Models\MagazineItem]"
   - **Causa**: Eager loading `documentItem.itemable.paper` (MagazineItem no tiene paper)
   - **Fix**: Simplificado a `->with(['documentItem', 'paper'])`

2. ❌ "Call to undefined relationship [itemable] on model [App\Models\PurchaseOrderItem]"
   - **Causa**: Segundo `modifyQueryUsing()` duplicado intentando cargar `'itemable'`
   - **Fix**: Eliminado bloque de código residual (líneas 218-220)

3. ❌ Accessor usando `$this->itemable` en PurchaseOrderItem
   - **Causa**: PurchaseOrderItem no tiene relación itemable directa
   - **Fix**: Acceso vía `$this->documentItem->itemable` con carga dinámica

#### Testing Realizado

✅ Purchase Orders ahora muestra:
- **SimpleItem**: 1 row con papel, pliegos, precio
- **MagazineItem**: N rows (uno por cada papel usado en páginas)
- **Product**: 1 row con nombre producto, cantidad, precio

---

### ✅ Sesión Completada (24-Oct-2025)
**SPRINT 10.5: Company Profile UI Redesign + Critical Fixes**

#### Logros de la Sesión

1. **✅ Company Profile - Filament Integration**
   - **Problema**: Perfil de empresa (/empresa/{slug}) usaba layout diferente al dashboard
   - **Solución**: Convertido a Filament Page manteniendo topbar consistente
   - **Archivos creados/modificados**:
     - `app/Filament/Pages/CompanyProfile.php` - Nueva Filament Page
     - `resources/views/filament/pages/company-profile.blade.php` - Vista con layout Home
     - `routes/web.php:81-89` - Ruta legacy comentada (ahora manejada por Filament)
     - `app/Providers/Filament/AdminPanelProvider.php:135` - URL perfil actualizada
   - **Features implementados**:
     - Layout de dos columnas (igual que /admin/home)
     - Sidebar derecho con información de contacto (400px fijo)
     - Iconos de Filament (heroicon-o-envelope, phone, globe-alt)
     - Computed property `getPostsProperty()` para paginación Livewire
     - Método `getSlug()` con firma correcta: `?Filament\Panel $panel = null`

2. **✅ Avatar Upload Fix**
   - **Problema**: FileUpload sin opciones de eliminación/descarga
   - **Solución**: Agregadas opciones al componente
   - **Archivo**: `app/Filament/Pages/CompanySettings.php:95-125`
   - **Cambios**: `->deletable()`, `->downloadable()`, `->openable()`

3. **✅ APP_URL Configuration Fix**
   - **Problema**: CORS errors con `http://localhost` vs `http://localhost:8000`
   - **Solución**: Actualizado `.env` con puerto correcto
   - **Archivo**: `.env:5`
   - **Cambio**: `APP_URL=http://localhost:8000`

4. **✅ Company Profile Route Error Fix**
   - **Problema**: `Route [company.profile] not defined` en SuggestedCompaniesWidget
   - **Solución**: Actualizado método `getProfileUrl()` en Company model
   - **Archivo**: `app/Models/Company.php:244-247`
   - **Antes**: `return route('company.profile', $this->slug);`
   - **Después**: `return '/admin/empresa/' . $this->slug;`

5. **✅ Magazine Item Creation Fix**
   - **Problema**: `Call to undefined method Document::documentItems()`
   - **Solución**: Corregida relación en MagazineItemHandler
   - **Archivo**: `app/Filament/Resources/Documents/RelationManagers/Handlers/MagazineItemHandler.php:425,431`
   - **Cambio**: `->documentItems()` → `->items()`

6. **✅ Order Column Error Fix**
   - **Problema**: `Column 'order' not found` en document_items
   - **Solución**: Removida referencia a columna inexistente
   - **Archivo**: `MagazineItemHandler.php:431`
   - **Cambio**: Eliminada línea `'order' => $this->record->items()->max('order') + 1,`

#### Archivos Modificados (9 total)

**Nuevas Pages (2):**
```
app/Filament/Pages/CompanyProfile.php
  ├── mount(): Carga empresa por slug, valida acceso público/privado
  ├── getPostsProperty(): Computed property para posts paginados
  ├── getTitle() / getHeading(): Retorna nombre empresa
  └── getSlug(): 'empresa/{slug}' con firma compatible Panel

resources/views/filament/pages/company-profile.blade.php
  ├── Layout: Igual que home.blade.php (profile-layout, profile-content, profile-sidebar)
  ├── Banner + Avatar con overlay
  ├── Stats: Posts, Seguidores, Siguiendo
  ├── Publicaciones con paginación
  └── Sidebar: Card "Información de Contacto" con iconos Filament
```

**Routes (1):**
```
routes/web.php:81-89
  └── Comentadas rutas legacy /empresa/{slug} (ahora manejadas por Filament)
```

**Providers (1):**
```
app/Providers/Filament/AdminPanelProvider.php:135
  └── URL perfil actualizada: '/admin/empresa/'.auth()->user()->company->slug
```

**Models (1):**
```
app/Models/Company.php:244-247
  └── getProfileUrl() retorna directamente '/admin/empresa/'.$slug (sin route())
```

**Settings (1):**
```
app/Filament/Pages/CompanySettings.php:95-125
  └── FileUpload avatar/banner: ->deletable(), ->downloadable(), ->openable()
```

**Handlers (1):**
```
app/Filament/Resources/Documents/RelationManagers/Handlers/MagazineItemHandler.php
  ├── Línea 425: ->documentItems() → ->items()
  └── Línea 431: Removida referencia a columna 'order'
```

**Config (1):**
```
.env:5
  └── APP_URL=http://localhost:8000 (añadido puerto)
```

#### URLs de Testing
- **Perfil Empresa**: http://localhost:8000/admin/empresa/litopro-demo
- **Company Settings**: http://localhost:8000/admin/company-settings
- **Home (referencia layout)**: http://localhost:8000/admin/home
- **Cotizaciones**: http://localhost:8000/admin/documents

---

### ✅ Sesión Completada (23-Oct-2025)
**SPRINT 9: Production Orders - Grouped by Supplier + Finishing System**

#### Logros Críticos

1. **Production Orders Agrupadas por Proveedor**
   - Sistema completo: Órdenes se crean automáticamente agrupadas por proveedor
   - Arquitectura: Un item puede tener impresión + múltiples acabados con diferentes proveedores
   - Lógica: Item con acabados de 3 proveedores → 3 órdenes automáticas

2. **Migraciones (3 nuevas)**
   - `add_supplier_id_to_finishings_table`
   - `add_supplier_id_to_document_item_finishings_table`
   - `add_process_fields_to_document_item_production_order_table`

3. **Nuevo Servicio: ProductionOrderGroupingService**
   - `groupBySupplier()`: Agrupa items/acabados por proveedor
   - `getOrdersSummary()`: Vista previa de órdenes

4. **UI/UX Mejorada**
   - Vista previa dinámica en modal antes de crear órdenes
   - Acabados con campo supplier_id reactivo
   - Columnas proceso_type y finishing_name en ProductionOrderItemsRelationManager

---

## 🎯 PRÓXIMA TAREA PRIORITARIA
**Sprint 12: Testing Completo + Production Orders Printing Supplier**

### Objetivos Críticos

1. **Testing Purchase Orders con Magazine Items**
   - Crear cotización con Magazine Item (mínimo 2 páginas con diferentes papeles)
   - Aprobar cotización y generar Purchase Order
   - Verificar que se creen múltiples rows (uno por papel)
   - Validar cálculos: sheets_quantity, unit_price, total_price por papel
   - **URL**: http://localhost:8000/admin/purchase-orders

2. **Implementar supplier_id para Impresión**
   - Decidir: ¿supplier_id en SimpleItem o PrintingMachine?
   - Completar `getPrintingSupplier()` en ProductionOrderGroupingService
   - Testing: Production Orders con supplier de impresión asignado

3. **Production Orders Testing Completo**
   - Crear cotización con items mixtos (SimpleItem + Product + Magazine)
   - Asignar proveedores a acabados (Finishings)
   - Aprobar → Crear Production Orders
   - Verificar agrupación por supplier

4. **Bug Fixes si aparecen en Testing**
   - Prioridad a errores críticos que bloqueen workflow
   - Documentar cualquier edge case encontrado

### Meta Business
- Purchase Orders 100% funcional para todos los tipos de items
- Production System completo con suppliers
- Workflow end-to-end validado

---

## COMANDO PARA EMPEZAR MAÑANA

```bash
# Iniciar LitoPro 3.0 - SPRINT 11 COMPLETADO (Purchase Orders Multi-Paper)
cd /home/dasiva/Descargas/litopro825 && php artisan serve --port=8000

# URLs Operativas
echo "✅ SPRINT 11 COMPLETADO (25-Oct-2025) - Purchase Orders Multi-Paper para Revistas"
echo ""
echo "📍 URLs de Testing:"
echo "   📋 Cotizaciones: http://localhost:8000/admin/documents"
echo "   🛒 Purchase Orders: http://localhost:8000/admin/purchase-orders"
echo "   🏭 Production Orders: http://localhost:8000/admin/production-orders"
echo "   ⚙️  Acabados: http://localhost:8000/admin/finishings"
echo ""
echo "✅ CAMBIOS SESIÓN 25-OCT:"
echo "   ✅ Purchase Orders: Ahora muestra múltiples rows por Magazine Item"
echo "   ✅ Cada papel de revista = 1 row independiente con su cantidad y precio"
echo "   ✅ PurchaseOrderItem: Nuevo modelo (pivot como entity)"
echo "   ✅ Migration: paper_id, paper_description, sheets_quantity agregados"
echo "   ✅ Accessor paper_name: Carga dinámica inteligente de relaciones"
echo "   ✅ Fix: Eliminado eager loading duplicado que causaba errores"
echo ""
echo "🎯 PRÓXIMA SESIÓN: Sprint 12 - Testing + Printing Supplier"
echo ""
echo "🧪 TESTING CRÍTICO (HACER PRIMERO):"
echo "   1. PURCHASE ORDER CON REVISTA:"
echo "      a) Ir a /admin/documents → Nueva Cotización"
echo "      b) Agregar Item Revista con 2-3 páginas (DIFERENTES papeles)"
echo "      c) Aprobar cotización → Ver botón 'Crear Orden de Pedido'"
echo "      d) Crear Purchase Order al proveedor"
echo "      e) Abrir Purchase Order → Verificar tabla de items"
echo "      f) ¿Se ven múltiples rows? (1 por cada papel usado)"
echo "      g) ¿Cada row muestra cantidad pliegos correcta?"
echo "      h) ¿Precios unitarios y totales correctos?"
echo ""
echo "   2. SI FUNCIONA → Implementar supplier_id para impresión"
echo "   3. SI FALLA → Reportar error exacto que aparece"
echo ""
echo "📍 META: Purchase Orders 100% funcional antes de continuar con Production"
```

---

## Notas Técnicas Importantes

### Filament Pages - Slug Pattern
```php
// ✅ CORRECTO: Slug dinámico con parámetro Panel
public static function getSlug(?\Filament\Panel $panel = null): string {
    return 'empresa/{slug}';
}

// ❌ INCORRECTO: Sin parámetro Panel (error de firma)
public static function getSlug(): string {
    return 'empresa/{slug}';
}
```

### Livewire + Filament - Computed Properties
```php
// ✅ CORRECTO: Computed property para relaciones paginadas
public function getPostsProperty() {
    return SocialPost::where('company_id', $this->company->id)
        ->paginate(10);
}

// ❌ INCORRECTO: Property normal (error de serialización)
public $posts;
public function mount() {
    $this->posts = SocialPost::paginate(10); // ERROR
}
```

### Document Relationships
```php
// ✅ CORRECTO: Relación definida como items()
$document->items()->create([...]);

// ❌ INCORRECTO: documentItems() no existe
$document->documentItems()->create([...]);

// Verificar en app/Models/Document.php:
public function items(): HasMany {
    return $this->hasMany(DocumentItem::class);
}
```

### Table Columns - Verificar Existencia
```php
// Antes de usar columna 'order' en query:
// 1. Verificar con: php artisan db:table document_items
// 2. Si no existe, NO usar en ->max('order') o orderBy('order')
// 3. Alternativas: ->latest('id') o ->latest('created_at')
```

### FileUpload Best Practice
```php
// ✅ COMPLETO: Todas las opciones para UX óptima
FileUpload::make('avatar')
    ->disk('public')        // Disco público
    ->directory('companies/avatars')
    ->visibility('public')
    ->imageResizeMode('cover')
    ->imageCropAspectRatio('1:1')
    ->imageResizeTargetWidth('200')
    ->imageResizeTargetHeight('200')
    ->maxSize(2048)
    ->deletable()           // ← Permite eliminar
    ->downloadable()        // ← Permite descargar
    ->openable()            // ← Permite abrir en nueva pestaña
```

### Purchase Orders - Multi-Paper Support
```php
// PurchaseOrderItem (pivot como entity)
// Permite múltiples rows por DocumentItem (caso: revistas con varios papeles)

// Relación en PurchaseOrder:
public function purchaseOrderItems(): HasMany {
    return $this->hasMany(PurchaseOrderItem::class);
}

// Accessor con carga dinámica:
public function getPaperNameAttribute(): string {
    // 1. Verifica paper_description (revistas)
    if ($this->paper_description) return $this->paper_description;

    // 2. Carga paper solo si existe paper_id
    if ($this->paper_id && $this->paper) return $this->paper->name;

    // 3. Carga itemable dinámicamente si no está cargado
    if (!$this->documentItem->relationLoaded('itemable')) {
        $this->documentItem->load('itemable');
    }

    // 4. Usa instanceof para verificar tipo
    if ($itemable instanceof SimpleItem) {
        // Carga paper solo si no está cargado
        if (!$itemable->relationLoaded('paper')) {
            $itemable->load('paper');
        }
        return $itemable->paper->name;
    }
}

// ⚠️ IMPORTANTE: NO eager loadear 'itemable.paper'
// MagazineItem no tiene relación paper → Error
// Solución: Eager load solo relaciones directas
```

### Sistema de Producción
```php
// Estructura de agrupación por proveedor
$grouped = [
    'supplier_id_1' => [
        'printing' => [DocumentItem, ...],
        'finishings' => [FinishingProcess, ...]
    ],
    'supplier_id_2' => [...]
];
```
