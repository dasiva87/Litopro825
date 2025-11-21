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

### ✅ Sesión Completada (19-Nov-2025)
**SPRINT 21: Sistema de Acabados para Productos en Cotizaciones**

#### Logros de la Sesión

1. **✅ Stock Insuficiente - Modal de Advertencia**
   - Cambió excepción por notificación elegante
   - `Filament\Support\Exceptions\Halt` detiene sin cerrar modal
   - Usuario puede corregir cantidad sin perder datos

2. **✅ Acabados en Productos - Integración Completa**
   - **CalculatesProducts Trait**: Método `calculateProductTotalWithFinishings()`
   - **Cálculo reactivo**: Precio se actualiza al agregar/modificar acabados
   - **Recálculo dinámico**: Acabados se ajustan a cantidad solicitada
   - **Guardado en item_config**: Acabados personalizados por cotización

3. **✅ Formulario de Creación de Productos**
   - Repeater de acabados con campos dinámicos
   - Carga automática de acabados predefinidos del producto
   - Preview de costo en tiempo real
   - Guardado en `item_config` del DocumentItem

4. **✅ Formulario de Edición de Productos (ProductHandler)**
   - Sección de acabados en modal de edición
   - Carga acabados desde `item_config` o producto base
   - `fillForm()`: Carga acabados guardados
   - `handleUpdate()`: Guarda acabados editados y recalcula precio

5. **✅ Debugging Tools**
   - Placeholder de debug para visualizar cálculo
   - Logs en Laravel para tracking de cálculos
   - Información detallada de acabados y costos

#### Archivos Modificados (Sprint 21)

**Handlers (2)**:
1. `app/Filament/Resources/Documents/RelationManagers/Handlers/ProductQuickHandler.php`
   - Cambió excepción por `Halt` + notificación
   - Repeater de acabados con live updates
   - Carga acabados del producto al seleccionar
   - Placeholder de debug agregado
   - handleCreate() calcula y guarda acabados en item_config

2. `app/Filament/Resources/Documents/RelationManagers/Handlers/ProductHandler.php`
   - getEditForm() con sección de acabados
   - fillForm() carga acabados desde item_config
   - handleUpdate() guarda acabados y recalcula precios

**Traits (1)**:
3. `app/Filament/Resources/Documents/RelationManagers/Traits/CalculatesProducts.php`
   - calculateProductTotalWithFinishings() (public)
   - Recálculo de acabados por cantidad solicitada
   - Soporte para acabados personalizados o del producto
   - Todos los métodos cambiados a public

**Total Sprint 21**: 3 archivos modificados

#### Flujo de Acabados Implementado

```
AGREGAR PRODUCTO:
1. Seleccionar producto → Carga acabados predefinidos
2. Usuario modifica/agrega acabados en repeater
3. Precio se calcula: (Producto × Cant) + Acabados + Margen
4. Guardar → item_config = {finishings, finishings_cost}

EDITAR PRODUCTO:
1. Abrir modal → Carga acabados desde item_config
2. Usuario modifica acabados
3. Precio se recalcula en tiempo real
4. Guardar → Actualiza item_config y precios
```

#### Testing Realizado

```php
✅ Producto sin stock → Modal de advertencia (no cierra)
✅ Producto con acabados → Se cargan en repeater
✅ Cálculo reactivo → Precio actualiza al cambiar acabados
✅ Guardado → item_config guarda acabados correctamente
✅ Edición → Carga y guarda acabados modificados
✅ Recálculo → Acabados proporcionales a cantidad
```

---

### ✅ Sesión Completada (16-Nov-2025)
**SPRINT 20: Sistema Completo de Órdenes de Producción con Impresión + Acabados**

#### Logros de la Sesión

1. **✅ Implementación de Órdenes de Producción para Impresión**
   - **getPrintingSupplier()**: Extrae supplier_id desde PrintingMachine
   - **Máquinas propias**: Asignan contacto autorreferencial (ID: 9)
   - **Máquinas externas**: Usan supplier_id de la máquina
   - **buildPrintingDescription()**: Genera descripción detallada del proceso

2. **✅ Auto-Asignación de Proveedores en PrintingMachines**
   - **5 máquinas actualizadas**: Heidelberg, Xerox, Komori, GTO 52 (×2)
   - **supplier_id = 9**: Para todas las máquinas propias (is_own = true)
   - **Contacto reutilizado**: "LitoPro Demo (Producción Propia)"

3. **✅ Agrupación Completa por Proveedor**
   - **Impresión + Acabados**: Ambos procesos en el mismo servicio
   - **Múltiples órdenes**: Separa por proveedor automáticamente
   - **Ejemplo real**: 2 órdenes (1 propia para impresión + 1 externa para acabado)

4. **✅ Testing Exhaustivo**
   - **Caso 1**: Item con impresión propia → 1 orden con 🖨️ Impresión
   - **Caso 2**: Item con acabado propio → 1 orden con 🎯 Acabado
   - **Caso 3**: Impresión propia + Acabado externo → 2 órdenes separadas
   - **Validación**: Todos los campos de pivot correctos

#### Archivos Modificados (Sprint 20)

**Servicios (1)**:
1. `app/Services/ProductionOrderGroupingService.php`
   - getPrintingSupplier() implementado (extrae de PrintingMachine)
   - getSelfContactId() agregado (reutiliza lógica de Finishing)
   - buildPrintingDescription() genera descripción detallada
   - Procesamiento de impresión + acabados en groupBySupplier()

**Total Sprint 20**: 1 archivo modificado, 0 nuevos archivos

#### Testing Realizado

```php
✅ Test 1: Item simple con impresión
   → 1 orden propia con 1 proceso de impresión

✅ Test 2: Item con impresión + acabado mismo proveedor
   → 1 orden propia con 2 procesos (impresión + acabado)

✅ Test 3: Item con impresión propia + acabado externo
   → 2 órdenes:
      - Orden 1 (Propia): Impresión
      - Orden 2 (Externa): Acabado levante

✅ Validación: 5 PrintingMachines actualizadas con supplier_id = 9
```

---

### ✅ Sesión Completada (15-Nov-2025)
**SPRINT 19: Sistema de Acabados con Auto-Asignación de Proveedores**

#### Logros de la Sesión

1. **✅ Fix Error de Columna 'code' en Finishing**
   - **Problema**: boot() auto-generaba campo 'code' que no existe en BD
   - **Solución**:
     - Removido 'code' de $fillable en Finishing.php
     - Eliminada auto-generación en boot()
     - Removido campo del formulario FinishingForm.php
     - Grid cambiado de 3 a 2 columnas

2. **✅ Auto-Asignación Inteligente de Proveedores**
   - **Contacto autorreferencial**: "LitoPro Demo (Producción Propia)" (ID: 9)
   - **Método getSelfContactId()**: Crea/obtiene contacto si no existe
   - **Toggle propio ↔ externo**: Funciona correctamente
   - **boot() events**:
     - creating: Asigna supplier_id si is_own_provider = true
     - updating: Actualiza supplier_id según toggle

3. **✅ Acabados en SimpleItem/DigitalItem - Sistema Completo**
   - **Eliminado duplicado**: Repeater de acabados solo en handlers
   - **Edición funcional**: Carga/guarda acabados desde pivot
   - **Cálculo reactivo**: Precio se actualiza en tiempo real
   - **Relación agregada**: simpleItems() en Finishing model

4. **✅ ProductionOrders - Validación y Agrupación**
   - **Validación temprana**: Items sin proveedores generan error claro
   - **Agrupación correcta**: ProductionOrderGroupingService agrupa por supplier_id
   - **Separación propios/externos**: 2 órdenes (1 propia + 1 externa)

#### Archivos Modificados (Sprint 19)

**Modelos (1)**:
1. `app/Models/Finishing.php`
   - Removido 'code' de $fillable
   - Actualizado boot() para auto-asignar supplier en toggle
   - getSelfContactId() crea contacto autorreferencial
   - Agregada relación simpleItems()

**Formularios (1)**:
2. `app/Filament/Resources/Finishings/Schemas/FinishingForm.php`
   - Removido campo 'code'
   - Grid 3 → 2 columnas

**RelationManagers (1)**:
3. `app/Filament/Resources/Documents/RelationManagers/DocumentItemsRelationManager.php`
   - Carga acabados desde pivot en edición
   - Guarda acabados a pivot (detach → attach)
   - TextInput reactivo para calculated_cost

**Pages (2)**:
4. `app/Filament/Resources/SimpleItems/Pages/CreateSimpleItem.php`
   - afterCreate() guarda acabados a pivot

5. `app/Filament/Resources/SimpleItems/Pages/EditSimpleItem.php`
   - mutateFormDataBeforeFill() carga acabados
   - afterSave() sincroniza acabados

**Total Sprint 19**: 5 archivos modificados, 0 nuevos archivos

#### Testing Realizado

```php
✅ Crear acabado propio → supplier_id = 9 (auto-asignado)
✅ Crear acabado externo → supplier_id = 3 (manual)
✅ Toggle externo → propio → supplier_id = 9
✅ Toggle propio → externo → supplier_id = 3
✅ Agregar acabados a SimpleItem/DigitalItem
✅ Editar items con acabados (carga correctamente)
✅ Cálculo reactivo de costos funciona
✅ ProductionOrderGroupingService agrupa correctamente
✅ 2 órdenes: 1 propia (ID:9) + 1 externa (ID:3)
```

---

### ✅ Sesión Completada (08-Nov-2025)
**SPRINT 18: Sistema Completo de Imágenes para Productos + Múltiples Mejoras de UX**

#### Logros de la Sesión

1. **✅ Sistema Completo de Imágenes para Productos (1-3 imágenes)**
   - **Base de Datos**:
     - Migración: `2025_11_08_201755_add_images_to_products_table.php`
     - Campos: `image_1`, `image_2`, `image_3` (nullable)
   - **Modelo Product**:
     - Agregados a fillable: `image_1`, `image_2`, `image_3`
     - Accessor `getImagesAttribute()`: array de todas las imágenes
     - Accessor `getPrimaryImageAttribute()`: primera imagen disponible
   - **Formulario ProductForm.php**:
     - 3 FileUpload fields configurados
     - Disco: `public`, Directorio: `products`
     - Tamaño máximo: 2MB por imagen
     - Formatos: JPEG, PNG, WebP
   - **Tabla ProductsTable.php**:
     - ImageColumn circular en primera columna
     - Configurado con `->disk('public')` para correcta visualización
     - Imagen por defecto si no existe

2. **✅ Botones de Items Ocultos en Modo Vista**
   - **PurchaseOrderItemsRelationManager**: Botones solo visibles en modo edición
   - **ProductionOrderItemsRelationManager**: Botones solo visibles en modo edición
   - Implementado mismo patrón que Documents y CollectionAccounts

3. **✅ Item Personalizado para Órdenes de Producción**
   - **Archivo nuevo**: `ProductionOrders/Handlers/CustomItemQuickHandler.php` (192 líneas)
   - Formulario especializado con:
     - Descripción del trabajo
     - Cantidad a producir (default: 1000)
     - Tamaño: ancho × alto (default: 21.5 × 28 cm)
     - Tintas frente/reverso (default: 4/0)
     - Notas de producción
   - Crea CustomItem + DocumentItem + adjunta a ProductionOrder
   - Botón visible solo en modo edición

4. **✅ Fix Sistema de Clientes Dual en Múltiples Recursos**
   - **CollectionAccounts**: Agregado soporte para Contact además de Company
     - Migración: `add_contact_support_to_collection_accounts_table.php`
     - Selector dual: "Empresa Conectada" o "Cliente/Proveedor"
   - **Documents (Cotizaciones)**: Agregado soporte para client_company_id
     - Migración: `add_client_company_id_to_documents_table.php`
   - **ProductionOrders**: Agregado soporte dual para proveedores
     - Migración: `add_supplier_company_id_to_production_orders_table.php`
   - **PurchaseOrders**: Agregado soporte dual para proveedores
     - Migración: `add_supplier_id_to_purchase_orders_table.php`

5. **✅ Fix Crítico: Validación de Órdenes de Producción**
   - **Problema**: Items sin acabados/proveedores causaban error silencioso
   - **Solución**: Validación temprana en DocumentsTable.php
   - Mensaje claro: "Los items seleccionados no tienen acabados con proveedores asignados"
   - Mejor manejo de errores con notificaciones específicas

6. **✅ Fix Crítico: Error CORS en FileUpload**
   - **Problema**: CORS bloqueaba imágenes por inconsistencia localhost vs 127.0.0.1
   - **Solución**: Actualizado `.env` → `APP_URL=http://127.0.0.1:8000`
   - Caché limpiada y configuración recacheada

7. **✅ Protecciones en Collection Accounts**
   - Cuentas en estado PAID no se pueden editar
   - Redirect automático a vista con notificación
   - Botones de edición/cambio de estado ocultos si PAID
   - Botón "Descargar PDF" removido de vistas

#### Archivos Creados (Sprint 18)

**Migraciones (5)**:
1. `2025_11_08_192838_add_contact_support_to_collection_accounts_table.php`
2. `2025_11_08_193507_add_client_company_id_to_documents_table.php`
3. `2025_11_08_194018_add_supplier_company_id_to_production_orders_table.php`
4. `2025_11_08_194649_add_supplier_id_to_purchase_orders_table.php`
5. `2025_11_08_201755_add_images_to_products_table.php`

**Handlers (1)**:
6. `app/Filament/Resources/ProductionOrders/Handlers/CustomItemQuickHandler.php`

**Total**: 6 archivos nuevos

#### Archivos Modificados (Sprint 18)

**Modelos (5)**:
1. `app/Models/CollectionAccount.php` - Relación contact + accessors
2. `app/Models/Document.php` - Relación clientCompany + accessors
3. `app/Models/ProductionOrder.php` - Relación supplierCompany + accessors
4. `app/Models/PurchaseOrder.php` - Relación supplier + accessors
5. `app/Models/Product.php` - Campos de imágenes + accessors

**Formularios (6)**:
6. `app/Filament/Resources/CollectionAccounts/Schemas/CollectionAccountForm.php` - Selector dual cliente
7. `app/Filament/Resources/Documents/Schemas/DocumentForm.php` - Selector dual cliente
8. `app/Filament/Resources/ProductionOrders/Schemas/ProductionOrderForm.php` - Selector dual proveedor
9. `app/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderForm.php` - Selector dual proveedor
10. `app/Filament/Resources/Products/Schemas/ProductForm.php` - Sección de imágenes

**Tablas (2)**:
11. `app/Filament/Resources/Products/Tables/ProductsTable.php` - ImageColumn con disk
12. `app/Filament/Resources/Documents/Tables/DocumentsTable.php` - Validación producción

**RelationManagers (4)**:
13. `app/Filament/Resources/CollectionAccounts/RelationManagers/CollectionAccountItemsRelationManager.php` - Botones en edit only
14. `app/Filament/Resources/PurchaseOrders/RelationManagers/PurchaseOrderItemsRelationManager.php` - Botones en edit only
15. `app/Filament/Resources/ProductionOrders/RelationManagers/ProductionOrderItemsRelationManager.php` - Item personalizado + botones

**Páginas (2)**:
16. `app/Filament/Resources/CollectionAccounts/Pages/EditCollectionAccount.php` - Protección PAID
17. `app/Filament/Resources/CollectionAccounts/Pages/ViewCollectionAccount.php` - Protección PAID

**Configuración (2)**:
18. `.env` - APP_URL actualizado a 127.0.0.1:8000
19. `config/livewire.php` - Publicado para configuración temporal files

**Total Sprint 18**: 6 archivos nuevos, 19 archivos modificados, 5 migraciones ejecutadas

#### Problemas Resueltos

**FileUpload - Carga Infinita**:
- ❌ Problema: Imágenes se quedaban cargando infinitamente
- ✅ Solución: Error CORS por inconsistencia localhost vs 127.0.0.1
- ✅ Fix: APP_URL actualizado + simplificación de FileUpload

**FileUpload - No se guarda en BD**:
- ❌ Problema: Archivos se subían pero ruta no se guardaba
- ✅ Verificación: mutateFormDataBeforeSave() mostró que SÍ llegaban los datos
- ✅ Conclusión: El guardado funcionaba correctamente

**ImageColumn - No muestra imágenes**:
- ❌ Problema: Tabla no mostraba imágenes aunque estaban en BD
- ✅ Solución: Agregar `->disk('public')` a ImageColumn
- ✅ Resultado: Imágenes circulares visibles en tabla

**Órdenes de Producción - Error Silencioso**:
- ❌ Problema: Items sin acabados/proveedores causaban error
- ✅ Solución: Validación temprana con mensaje claro
- ✅ Mejora: Manejo de errores detallado

#### Testing Realizado

```bash
✅ Sintaxis PHP verificada en todos los archivos
✅ Migraciones ejecutadas exitosamente
✅ Imágenes se suben, guardan y muestran correctamente
✅ Botones de items ocultos en modo vista
✅ Item personalizado en órdenes de producción funcional
✅ Sistema dual cliente/proveedor funcionando
✅ Protecciones de estado PAID operativas
✅ CORS resuelto, sin errores de carga
```

---

### 📋 Sprints Anteriores (Resumen)

**SPRINT 18** (08-Nov): Sistema de Imágenes para Productos + Cliente Dual + Item Personalizado
**SPRINT 17** (07-Nov): Nomenclatura "Papelería → Papelería y Productos"
**SPRINT 16** (07-Nov): Sistema de Permisos 100% + Policies
**SPRINT 15** (06-Nov): Documentación Sistema de Notificaciones (4 tipos)
**SPRINT 14** (06-Nov): Sistema base de Acabados + UI
**SPRINT 13** (05-Nov): Sistema de Montaje con Divisor

---

## 🎯 PRÓXIMA TAREA PRIORITARIA

**Remover Placeholder de Debug de ProductQuickHandler**

El placeholder de debug agregado en líneas 141-180 debe ser removido ahora que el sistema funciona correctamente.

**Tareas Pendientes**:
1. **Limpiar ProductQuickHandler.php**
   - Remover sección `calculation_debug` (líneas 141-180)
   - Remover log de debug en `CalculatesProducts.php` (líneas 30-37)

2. **Sistema de Acabados para DigitalItems**
   - Implementar mismo patrón que Products
   - Repeater en creación y edición
   - Guardado en item_config

3. **Dashboard de Producción**
   - Widget con órdenes activas
   - Métricas de eficiencia por proveedor
   - Alertas de órdenes atrasadas

---

## COMANDO PARA EMPEZAR MAÑANA

```bash
# Iniciar LitoPro 3.0 - SPRINT 21 COMPLETADO (Acabados para Productos)
cd /home/dasiva/Descargas/litopro825 && php artisan serve --port=8000

# Estado del Proyecto
echo "✅ SPRINT 21 COMPLETADO (19-Nov-2025) - Sistema de Acabados para Productos"
echo ""
echo "📍 URLs de Testing:"
echo "   🏠 Dashboard: http://127.0.0.1:8000/admin"
echo "   🎨 Acabados: http://127.0.0.1:8000/admin/finishings"
echo "   📋 Cotizaciones: http://127.0.0.1:8000/admin/documents"
echo "   📦 Productos: http://127.0.0.1:8000/admin/products"
echo "   🏭 Órdenes de Producción: http://127.0.0.1:8000/admin/production-orders"
echo ""
echo "⚠️  IMPORTANTE: Usar http://127.0.0.1:8000 (NO localhost) - CORS configurado"
echo ""
echo "🎉 SPRINT 21 - ACABADOS EN PRODUCTOS COMPLETO:"
echo "   • ✅ Stock insuficiente → Modal de advertencia (Halt)"
echo "   • ✅ Productos con acabados → Carga/edición completa"
echo "   • ✅ Cálculo reactivo → Precio actualiza en tiempo real"
echo "   • ✅ Guardado en item_config → Acabados por cotización"
echo "   • ✅ Recálculo dinámico → Proporcional a cantidad"
echo ""
echo "📊 FLUJO DE ACABADOS EN PRODUCTOS:"
echo "   Producto → Seleccionar en cotización"
echo "      ↓"
echo "   Carga acabados predefinidos en repeater"
echo "      ↓"
echo "   Usuario modifica/agrega acabados"
echo "      ↓"
echo "   Precio = (Producto × Cant) + Acabados + Margen"
echo "      ↓"
echo "   Guardar → item_config + precios actualizados"
echo ""
echo "🎯 PRÓXIMA TAREA:"
echo "   1. Remover placeholder de debug (líneas 141-180)"
echo "   2. Remover logs de debug en CalculatesProducts.php"
echo "   3. Implementar acabados para DigitalItems"
```

---

## Notas Técnicas Importantes

### Sistema de Acabados para Productos en Cotizaciones (Sprint 21)

```php
// AGREGAR PRODUCTO CON ACABADOS A COTIZACIÓN
// ProductQuickHandler::handleCreate()

// 1. Cargar producto con acabados
$product = Product::with('finishings')->find($productId);

// 2. Calcular costo de acabados (personalizados o del producto)
$finishingCalculator = app(\App\Services\FinishingCalculatorService::class);
$finishingsCostTotal = 0;

foreach ($finishingsData as $finishingData) {
    $finishing = \App\Models\Finishing::find($finishingData['finishing_id']);

    // Parámetros según tipo
    $params = match($finishing->measurement_unit->value) {
        'millar', 'rango', 'unidad' => ['quantity' => $quantity],
        'tamaño' => ['width' => $width, 'height' => $height],
        default => []
    };

    $cost = $finishingCalculator->calculateCost($finishing, $params);
    $finishingsCostTotal += $cost;
}

// 3. Calcular precio total con acabados
$baseTotal = ($product->sale_price * $quantity) + $finishingsCostTotal;
$totalWithMargin = $baseTotal * (1 + ($profitMargin / 100));

// 4. Guardar en item_config
$documentItem->update([
    'item_config' => [
        'finishings' => $finishingsData,
        'finishings_cost' => $finishingsCostTotal,
    ],
]);

// EDITAR PRODUCTO CON ACABADOS
// ProductHandler::fillForm() - Carga acabados
$finishingsData = $record->item_config['finishings'] ?? [];

// ProductHandler::handleUpdate() - Guarda acabados editados
// Mismo proceso de cálculo que handleCreate()
```

**Características**:
- **item_config**: Almacena acabados específicos por cotización
- **Recálculo dinámico**: Acabados se ajustan a cantidad solicitada
- **Fallback inteligente**: Si no hay en item_config, usa acabados del producto
- **Cálculo reactivo**: Precio se actualiza en tiempo real (frontend)

---

### Auto-Asignación de Proveedores en Acabados (Sprint 19)

```php
use App\Models\Finishing;

// 1. CREAR ACABADO PROPIO (auto-asigna supplier_id)
$acabadoPropio = Finishing::create([
    'company_id' => 1,
    'name' => 'Plastificado',
    'unit_price' => 50,
    'measurement_unit' => 'millar',
    'is_own_provider' => true,  // ← AUTO-ASIGNA SUPPLIER
    'active' => true,
]);
// supplier_id = 9 (LitoPro Demo (Producción Propia))

// 2. CREAR ACABADO EXTERNO (manual)
$acabadoExterno = Finishing::create([
    'company_id' => 1,
    'name' => 'Barniz UV',
    'unit_price' => 80,
    'measurement_unit' => 'tamaño',
    'is_own_provider' => false,
    'supplier_id' => 3,  // Distribuidora de Papel Colombia
    'active' => true,
]);

// 3. TOGGLE EXTERNO → PROPIO
$acabado = Finishing::find(12);
$acabado->update(['is_own_provider' => true]);
// supplier_id automáticamente cambia a 9

// 4. TOGGLE PROPIO → EXTERNO
$acabado->update([
    'is_own_provider' => false,
    'supplier_id' => 3,  // Asignar proveedor manualmente
]);

// 5. CONTACTO AUTORREFERENCIAL
// Método getSelfContactId() crea automáticamente:
// - Nombre: "{Nombre Empresa} (Producción Propia)"
// - Email: "produccion@{empresa}.com"
// - Se crea solo una vez, se reutiliza después
```

**Arquitectura**:
```
boot() → creating/updating events
    ↓
is_own_provider = true?
    ↓ YES
getSelfContactId(company_id)
    ↓
Buscar/Crear Contact autorreferencial
    ↓
supplier_id = {self_contact_id}
```

**Producción de Órdenes**:
```php
$service = new ProductionOrderGroupingService();
$grouped = $service->groupBySupplier($documentItems);

// Resultado: 2 órdenes
// [
//     9 => ['finishings' => [Plastificado, Numeración]],  // Propia
//     3 => ['finishings' => [Barniz UV, Levante]]         // Externa
// ]
```

---

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
