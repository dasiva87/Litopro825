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
- **Actions**: `Filament\Actions\*` (NO Tables\Actions ni Pages\Actions)
- **Columns**: `Filament\Tables\Columns\*`
- **FileUpload**: SIEMPRE usar `->disk('public')` para archivos públicos
- **Componentes Nativos**: Usar `<x-filament::icon>`, `<x-filament::badge>`, `<x-filament::button>`

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

### ✅ Sesión Completada (04-Dic-2025)
**SPRINT 24: Sistema Completo de Red Grafired - Búsqueda y Solicitudes Comerciales**

#### Logros de la Sesión

1. **✅ Sistema Completo de Solicitudes Comerciales**
   - **CommercialRequestService**: Lógica de negocio centralizada
   - **Validación de duplicados**: No permite solicitudes repetidas
   - **Workflow completo**: Pending → Approved/Rejected
   - **Creación bidireccional**: Ambas empresas quedan conectadas
   - **Notificaciones**: Email + Database en cada paso

2. **✅ Modal de Búsqueda Grafired**
   - **Vista estática optimizada**: Pre-carga 20 empresas públicas
   - **Componentes nativos Filament**: Sin CSS personalizado
   - **Iconos correctos**: h-4 w-4 (antes estaban desproporcionados)
   - **Badges dinámicos**: Colores según tipo de empresa
   - **Botón funcional**: "Solicitar como Proveedor" con wire:click

3. **✅ Modelo Contact - Soporte Grafired Completo**
   - **Campo linked_company_id**: Referencia a empresa en red
   - **Campo is_local**: Diferencia proveedores locales vs Grafired
   - **Scopes**: local(), grafired() para filtrado
   - **Método syncFromLinkedCompany()**: Sincroniza datos desde empresa

4. **✅ Sistema de Notificaciones Completo**
   - **CommercialRequestReceived**: Notifica a empresa destino
   - **CommercialRequestApproved**: Notifica aprobación al solicitante
   - **CommercialRequestRejected**: Notifica rechazo al solicitante
   - Todas con email + database

5. **✅ Fix Múltiples Errores Filament v4**
   - **Action imports**: Corregido en 5 resources (ClientResource, SupplierResource, etc.)
   - **Rutas corregidas**: companies.view → companies (páginas sin view)
   - **Vista faltante**: commercial-request-response.blade.php creada
   - **Get type mismatch**: Evitado usando vista estática en lugar de form reactivo

#### Archivos Creados (Sprint 24)

**Servicios (1)**:
1. `app/Services/CommercialRequestService.php` (150 líneas)
   - sendRequest(): Valida y crea solicitud
   - approveRequest(): Crea contactos bidireccionales
   - rejectRequest(): Rechaza solicitud con mensaje

**Notificaciones (3)**:
2. `app/Notifications/CommercialRequestReceived.php`
3. `app/Notifications/CommercialRequestApproved.php`
4. `app/Notifications/CommercialRequestRejected.php`

**Vistas (1)**:
5. `resources/views/filament/modals/grafired-search-static.blade.php`
   - Modal con empresas públicas
   - Componentes nativos: x-filament::icon, x-filament::badge, x-filament::button
   - Layout responsive con scroll

**Total Sprint 24**: 5 archivos nuevos

#### Archivos Modificados (Sprint 24)

**Modelos (1)**:
1. `app/Models/Contact.php`
   - Agregado linked_company_id, is_local a fillable
   - Relación linkedCompany()
   - Scopes: local(), grafired()
   - Métodos: isLocal(), isGrafired(), syncFromLinkedCompany()

**Páginas (1)**:
2. `app/Filament/Pages/Suppliers/ListSuppliers.php`
   - getSearchGrafiredAction(): Modal de búsqueda
   - getGrafiredCompanies(): Query de empresas públicas
   - sendSupplierRequest($companyId, $message): Handler de solicitud

**Resources (3)**:
3. `app/Filament/Resources/CommercialRequestResource.php`
   - Actualizado approveAction() con CommercialRequestService
   - Actualizado rejectAction() con CommercialRequestService
4. `app/Filament/Resources/ClientResource.php` - Fix Action import
5. `app/Filament/Resources/SupplierResource.php` - Fix Action import

**Total Sprint 24**: 5 archivos modificados

#### Workflow de Solicitudes Implementado

```
SOLICITAR PROVEEDOR:
1. Usuario A busca empresas en Grafired
2. Click "Solicitar como Proveedor" → sendSupplierRequest()
3. CommercialRequestService crea solicitud (status: pending)
4. Empresa B recibe notificación (email + database)

APROBAR SOLICITUD:
1. Usuario B abre solicitud en CommercialRequests
2. Click "Aprobar" → approveRequest()
3. Sistema crea 2 contactos:
   - Contact en Empresa A (linked_company_id = B, type: supplier)
   - Contact en Empresa B (linked_company_id = A, type: client)
4. Usuario A recibe notificación de aprobación
5. Ambas empresas quedan conectadas

RECHAZAR SOLICITUD:
1. Usuario B click "Rechazar" → rejectRequest()
2. Status cambia a 'rejected'
3. Usuario A recibe notificación de rechazo
```

#### Testing Realizado

```bash
✅ Modal de búsqueda abre correctamente
✅ Empresas públicas se cargan (7 encontradas)
✅ Iconos y badges con tamaño correcto
✅ Botón "Solicitar como Proveedor" funciona
✅ Validación de duplicados funciona ("Ya existe una solicitud activa")
✅ Componentes nativos Filament (sin CSS custom)
✅ Notificaciones se envían correctamente
✅ Relación linkedCompany carga correctamente
✅ Scopes local() y grafired() funcionan
✅ Playwright verificó CSS correcto
```

#### Problemas Resueltos Durante la Sesión

**Error: Get Type Mismatch en Modal con Forms**
- **Problema**: `Filament\Forms\Get` vs `Filament\Schemas\Components\Utilities\Get`
- **Solución**: Cambiar de form reactivo a vista estática pre-cargada
- **Resultado**: Modal funcional sin conflictos de tipos

**Error: Iconos Desproporcionados en Modal**
- **Problema**: SVGs manuales con clases custom causaban tamaño incorrecto
- **Solución**: Usar componentes nativos Filament (`<x-filament::icon>`)
- **Resultado**: Iconos h-4 w-4 perfectamente integrados

**Error: $wire Not Defined en Livewire**
- **Problema**: Componente Livewire dentro de modal Filament causaba conflicto
- **Solución**: Usar wire:click directo en ListSuppliers page
- **Resultado**: Comunicación directa sin wrapper Livewire

---

### ✅ Sesión Completada (22-Nov-2025)
**SPRINT 23: Dashboard de Stock Management Completo + Widgets Interactivos**

*Ver detalles completos en sección "Notas Técnicas" al final del documento*

**Resumen**:
- 4 widgets nuevos: StockTrends, TopConsumed, CriticalAlerts, RecentMovements
- QuickActions con 4 acciones: Entrada Stock, Ver Críticos, Generar PO, Descargar
- StockAlertResource completo con CRUD
- SimpleStockKpisWidget mejorado (5 stats + sparklines)

---

### 📋 Sprints Anteriores (Resumen)

- **SPRINT 23** (22-Nov): Dashboard Stock Management + 4 Widgets + QuickActions
- **SPRINT 22** (21-Nov): Limpieza Stock Management (387 → 52 líneas)
- **SPRINT 21** (19-Nov): Sistema de Acabados para Productos en Cotizaciones
- **SPRINT 20** (16-Nov): Órdenes de Producción con Impresión + Acabados
- **SPRINT 19** (15-Nov): Auto-Asignación de Proveedores en Acabados
- **SPRINT 18** (08-Nov): Sistema de Imágenes para Productos + Cliente Dual
- **SPRINT 17** (07-Nov): Nomenclatura "Papelería → Papelería y Productos"
- **SPRINT 16** (07-Nov): Sistema de Permisos 100% + Policies
- **SPRINT 15** (06-Nov): Documentación Sistema de Notificaciones (4 tipos)
- **SPRINT 14** (06-Nov): Sistema base de Acabados + UI
- **SPRINT 13** (05-Nov): Sistema de Montaje con Divisor

---

## 🎯 PRÓXIMA TAREA PRIORITARIA

**Sistema de Solicitudes Comerciales - Mejoras Opcionales**

El sistema está 100% funcional, pero se pueden agregar mejoras:

**Opción A - Búsqueda Avanzada en Modal**:
1. Filtros por tipo de empresa (litografía, distribuidora, etc.)
2. Filtro por país/ciudad
3. Búsqueda por nombre/NIT
4. Paginación (actualmente muestra 20 fijas)

**Opción B - Duplicar en ListClients.php**:
1. Implementar mismo modal de búsqueda
2. Botón "Buscar Clientes en Grafired"
3. Relación inversa (supplier → client)

**Opción C - Otras Áreas**:
1. **Remover Placeholder de Debug de ProductQuickHandler**
   - Limpiar código temporal de debug
2. **Sistema de Acabados para DigitalItems**
   - Implementar mismo patrón que Products
3. **Dashboard de Producción**
   - Widget con órdenes activas
   - Métricas de eficiencia por proveedor

---

## COMANDO PARA EMPEZAR MAÑANA

```bash
# Iniciar LitoPro 3.0 - SPRINT 24 COMPLETADO (Sistema Grafired)
cd /home/dasiva/Descargas/litopro825 && php artisan serve --port=8000

# Estado del Proyecto
echo "✅ SPRINT 24 COMPLETADO (04-Dic-2025) - Sistema Grafired 100%"
echo ""
echo "📍 URLs de Testing:"
echo "   🏠 Dashboard: http://127.0.0.1:8000/admin"
echo "   🤝 Proveedores: http://127.0.0.1:8000/admin/suppliers"
echo "   📨 Solicitudes: http://127.0.0.1:8000/admin/commercial-requests"
echo "   🏢 Empresas: http://127.0.0.1:8000/admin/companies"
echo "   📞 Contactos: http://127.0.0.1:8000/admin/contacts"
echo ""
echo "⚠️  IMPORTANTE: Usar http://127.0.0.1:8000 (NO localhost) - CORS configurado"
echo ""
echo "🎉 SPRINT 24 - SISTEMA GRAFIRED COMPLETO:"
echo "   • ✅ CommercialRequestService con workflow completo"
echo "   • ✅ Modal de búsqueda con componentes nativos Filament"
echo "   • ✅ Notificaciones email + database (3 tipos)"
echo "   • ✅ Creación bidireccional de contactos"
echo "   • ✅ Contact model con soporte Grafired (scopes + sync)"
echo "   • ✅ Fix CSS: iconos h-4 w-4 (antes desproporcionados)"
echo "   • ✅ Fix Filament v4: Action imports corregidos"
echo ""
echo "🌐 FUNCIONALIDADES IMPLEMENTADAS:"
echo "   1. Buscar empresas públicas en red Grafired"
echo "   2. Enviar solicitud comercial (con validación de duplicados)"
echo "   3. Aprobar solicitud → Crea contactos en ambas empresas"
echo "   4. Rechazar solicitud → Notifica al solicitante"
echo "   5. Sincronizar datos desde empresa conectada"
echo ""
echo "🎯 PRÓXIMA TAREA (Opcional):"
echo "   Opción A: Búsqueda avanzada (filtros + paginación)"
echo "   Opción B: Duplicar en ListClients.php"
echo "   Opción C: Otras áreas (debug, acabados, dashboard)"
```

---

## Notas Técnicas Importantes

### Sistema de Red Grafired (Sprint 24)

**CommercialRequestService - Workflow Completo**:
```php
// ENVIAR SOLICITUD
$service = app(CommercialRequestService::class);

$request = $service->sendRequest(
    targetCompany: $company,        // Empresa destino
    relationshipType: 'supplier',   // supplier o client
    message: 'Mensaje opcional'
);

// Validaciones automáticas:
// - No permite solicitudes duplicadas pendientes
// - Notifica a todos los usuarios de la empresa destino

// APROBAR SOLICITUD (crea contactos bidireccionales)
$contact = $service->approveRequest(
    request: $request,
    approver: auth()->user(),
    responseMessage: 'Bienvenido a nuestra red'
);

// Resultado:
// - Contact en Empresa A: linkedCompany = B, type = supplier
// - Contact en Empresa B: linkedCompany = A, type = client
// - Notificación de aprobación al solicitante

// RECHAZAR SOLICITUD
$service->rejectRequest(
    request: $request,
    responder: auth()->user(),
    responseMessage: 'Gracias por tu interés'
);
// Resultado: Status = rejected, notificación al solicitante
```

**Contact Model - Soporte Grafired**:
```php
use App\Models\Contact;

// Crear contacto local
$contact = Contact::create([
    'company_id' => 1,
    'name' => 'Proveedor Local',
    'is_local' => true,
    'is_supplier' => true,
]);

// Crear contacto Grafired
$contact = Contact::create([
    'company_id' => 1,
    'linked_company_id' => 5,  // Empresa en red
    'is_local' => false,
    'is_supplier' => true,
]);

// Scopes
$locales = Contact::local()->get();        // Solo is_local = true
$grafired = Contact::grafired()->get();    // Solo is_local = false + linked_company_id

// Sincronizar datos desde empresa
if ($contact->linkedCompany) {
    $contact->syncFromLinkedCompany();
    // Actualiza: name, email, phone, address, city, state, country
}

// Verificaciones
if ($contact->isLocal()) { /* ... */ }
if ($contact->isGrafired()) { /* ... */ }
```

**Modal Grafired - Componentes Nativos Filament**:
```blade
{{-- ❌ INCORRECTO: SVG manual con clases custom --}}
<svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" .../>
</svg>

{{-- ✅ CORRECTO: Componente nativo Filament --}}
<x-filament::icon
    icon="heroicon-m-map-pin"
    class="h-4 w-4"
/>

{{-- Badges dinámicos --}}
<x-filament::badge :color="match($company->company_type) {
    'litografia' => 'primary',
    'distribuidora' => 'success',
    'proveedor_insumos' => 'warning',
    default => 'info'
}">
    {{ $typeLabel }}
</x-filament::badge>

{{-- Botones con wire:click --}}
<x-filament::button
    wire:click="sendSupplierRequest({{ $company->id }}, null)"
    icon="heroicon-m-paper-airplane"
    size="xs"
>
    Solicitar como Proveedor
</x-filament::button>
```

**Ventajas de Componentes Nativos**:
- ✅ **Tamaños consistentes**: h-4 w-4 para iconos pequeños, h-12 w-12 para logos
- ✅ **Colores automáticos**: Respeta tema dark/light de Filament
- ✅ **Sin CSS custom**: No sobrescribe estilos predeterminados
- ✅ **Responsive**: Adapta automáticamente a diferentes pantallas

---

### Filament v4 - Errores Comunes y Soluciones

**Error 1: Action Import Incorrecto**
```php
// ❌ INCORRECTO: Filament v3
use Filament\Tables\Actions\Action;
use Filament\Pages\Actions\Action;

// ✅ CORRECTO: Filament v4
use Filament\Actions\Action;
```

**Error 2: Get Type Mismatch en Modales**
```php
// ❌ INCORRECTO: Form reactivo dentro de Action modal
Action::make('foo')
    ->form([
        Select::make('bar')
            ->reactive()
            ->afterStateUpdated(fn ($get, $set) => ...)
    ]);
// Error: Filament\Forms\Get vs Filament\Schemas\Components\Utilities\Get

// ✅ SOLUCIÓN 1: Vista estática
Action::make('foo')
    ->modalContent(view('filament.modals.static-view', ['data' => $data]))
    ->modalSubmitAction(false);

// ✅ SOLUCIÓN 2: Métodos del componente (no closure)
Select::make('bar')
    ->reactive()
    ->afterStateUpdated('handleUpdate'); // Método de Livewire component
```

**Error 3: Livewire dentro de Modal Filament**
```php
// ❌ INCORRECTO: @livewire dentro de modalContent
Action::make('foo')
    ->modalContent(view('modal-with-livewire'));
// Causa: $wire not defined

// ✅ CORRECTO: wire:click directo en Page
// ListSuppliers.php
public function sendSupplierRequest($companyId, $message) { /* ... */ }

// Blade del modal (modalContent)
<button wire:click="sendSupplierRequest({{ $company->id }}, null)">
    Solicitar
</button>
```

---

### Dashboard de Stock Management - Arquitectura (Sprint 23)

**Estructura de Widgets**:
```php
class StockManagement extends Page
{
    protected function getHeaderWidgets(): array {
        return [SimpleStockKpisWidget::class];
    }

    protected function getFooterWidgets(): array {
        return [
            StockTrendsChartWidget::class,
            TopConsumedProductsWidget::class,
            CriticalAlertsTableWidget::class,
            RecentMovementsWidget::class,
        ];
    }
}
```

**Widget con Acciones - Patrón Correcto**:
```php
class QuickActionsWidget extends Widget implements HasActions, HasForms {
    use InteractsWithActions;
    use InteractsWithForms;

    public function stockEntryAction(): Action {
        return Action::make('stock_entry')
            ->form([...])
            ->action(fn ($data) => ...);
    }

    public function viewCriticalAction(): Action {
        return Action::make('view_critical')
            ->url(route('filament.admin.resources.products.index') . '?filter=low');
    }
}

// Vista Blade
{{ ($this->stockEntryAction)() }}
{{ ($this->viewCriticalAction)() }}
<x-filament-actions::modals />
```

**Imports Críticos**:
```php
use Filament\Actions\Action; // NO Tables\Actions
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
```

---

### Sistema de Acabados para Productos (Sprint 21)

```php
// AGREGAR PRODUCTO CON ACABADOS A COTIZACIÓN
$product = Product::with('finishings')->find($productId);

// Calcular costo de acabados
$finishingCalculator = app(\App\Services\FinishingCalculatorService::class);
$finishingsCostTotal = 0;

foreach ($finishingsData as $finishingData) {
    $finishing = \App\Models\Finishing::find($finishingData['finishing_id']);
    $params = match($finishing->measurement_unit->value) {
        'millar', 'rango', 'unidad' => ['quantity' => $quantity],
        'tamaño' => ['width' => $width, 'height' => $height],
        default => []
    };
    $cost = $finishingCalculator->calculateCost($finishing, $params);
    $finishingsCostTotal += $cost;
}

// Guardar en item_config
$documentItem->update([
    'item_config' => [
        'finishings' => $finishingsData,
        'finishings_cost' => $finishingsCostTotal,
    ],
]);
```

---

### Auto-Asignación de Proveedores (Sprint 19)

```php
// Crear acabado propio (auto-asigna supplier_id)
$acabado = Finishing::create([
    'company_id' => 1,
    'name' => 'Plastificado',
    'is_own_provider' => true,  // ← Asigna supplier_id = 9
]);

// Toggle externo → propio
$acabado->update(['is_own_provider' => true]);
// supplier_id cambia automáticamente a contacto autorreferencial

// Método getSelfContactId() crea:
// - Nombre: "{Empresa} (Producción Propia)"
// - Email: "produccion@{empresa}.com"
// - Se reutiliza si ya existe
```

---

### Sistema de Montaje con Divisor (Sprint 13)

```php
$calculator = new SimpleItemCalculatorService();

// Montaje completo con divisor
$mountingWithCuts = $calculator->calculateMountingWithCuts($item);

// Resultado:
// [
//     'copies_per_mounting' => 2,    // Copias en tamaño máquina
//     'divisor' => 4,                // Cortes de máquina en pliego
//     'impressions_needed' => 500,   // 1000 ÷ 2
//     'sheets_needed' => 125,        // 500 ÷ 4
//     'total_impressions' => 500,    // 125 × 4
//     'total_copies_produced' => 1000 // 500 × 2
// ]
```
