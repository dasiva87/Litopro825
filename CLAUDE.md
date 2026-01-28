# GrafiRed 3.0 - SaaS para Litografías

## Stack & Arquitectura
- **Laravel 12.25.0 + PHP 8.3.21 + Filament 4.0.3 + MySQL**
- **Multi-tenant**: Scopes automáticos por `company_id`
- **Frontend**: Livewire 3.6.4 + TailwindCSS 4.1.12
- **Email**: Resend (Production) + Mailtrap (Testing)

## Comandos Core
```bash
php artisan serve --port=8000                 # Servidor local
php artisan test                              # Testing completo
php artisan pint && composer analyse          # Lint + análisis
php artisan migrate && php artisan db:seed    # Setup BD
php artisan grafired:setup-demo --fresh       # Demo completo

# Caché
php artisan config:clear && php artisan view:clear && php artisan cache:clear
php artisan filament:cache-components
```

---

## SPRINT 38 (27-Ene-2026) - Módulo de Proyectos

### Cambios Realizados

**1. Nueva Tabla `projects`**
- Campos: name, code (auto-generado), description, status, contact_id
- Fechas: start_date, estimated_end_date, actual_end_date
- Presupuesto: budget (decimal)
- Multi-tenant: company_id + BelongsToTenant trait

**2. Enum ProjectStatus**
- Estados: DRAFT, ACTIVE, IN_PROGRESS, COMPLETED, CANCELLED, ON_HOLD
- Implementa: HasColor, HasIcon, HasLabel

**3. Relación project_id en Documentos**
- Agregado a: documents, purchase_orders, production_orders, collection_accounts
- Herencia automática: al crear órdenes derivadas desde cotización, heredan project_id

**4. Resource de Filament Completo**
```
app/Filament/Resources/Projects/
├── ProjectResource.php
├── Pages/ (List, Create, Edit, View)
├── Schemas/ (Form, Infolist)
├── Tables/ (ProjectsTable)
└── RelationManagers/ (Documents, PurchaseOrders, ProductionOrders, CollectionAccounts)
```

**5. Flujo de Trabajo**
```
Proyecto → Cotización(es) → Órdenes de Pedido → Órdenes de Producción → Cuentas de Cobro
    ↑           ↓               ↓                   ↓                      ↓
    └─────── Todos heredan project_id automáticamente ──────────────────────┘
```

### Archivos Creados (16)

**Migraciones (2):**
- `2026_01_27_000001_create_projects_table.php`
- `2026_01_27_000002_add_project_id_to_documents_tables.php`

**Enums (1):**
- `app/Enums/ProjectStatus.php`

**Modelos (1):**
- `app/Models/Project.php` (reemplaza modelo virtual)

**Policies (1):**
- `app/Policies/ProjectPolicy.php`

**Resource Filament (11):**
- `ProjectResource.php`
- `Pages/ListProjects.php`, `CreateProject.php`, `EditProject.php`, `ViewProject.php`
- `Schemas/ProjectForm.php`, `ProjectInfolist.php`
- `Tables/ProjectsTable.php`
- `RelationManagers/DocumentsRelationManager.php`, `PurchaseOrdersRelationManager.php`, `ProductionOrdersRelationManager.php`, `CollectionAccountsRelationManager.php`

### Archivos Modificados (7)

**Modelos (4):**
- `Document.php` - project_id + relación project()
- `PurchaseOrder.php` - project_id + relación project()
- `ProductionOrder.php` - project_id + relación project()
- `CollectionAccount.php` - project_id + relación project()

**Formularios (1):**
- `DocumentForm.php` - Selector de proyecto

**Tablas (1):**
- `DocumentsTable.php` - Herencia de project_id en create_purchase_orders, create_production_order, create_collection_account

**Providers (1):**
- `AuthServiceProvider.php` - Registro de ProjectPolicy

---

## SPRINT 37 (26-Ene-2026) - Cálculos y Terminología

### Cambios Realizados

**1. Precio de Venta del Papel en Cálculos**
- Ahora usa `paper->price` (precio de venta) en lugar de `cost_per_sheet` (costo)
- Aplica a: cotizaciones, órdenes de pedido, cálculo de items
- Fallback: si no hay `price`, usa `cost_per_sheet`

**2. Agrupación por Proveedor en Órdenes de Pedido**
- Al crear orden desde cotización, agrupa items por proveedor
- Usa `paper->supplier_id` para papeles (Contact)
- Usa `product->supplier_contact_id` para productos (Contact)
- Crea múltiples órdenes si hay diferentes proveedores

**3. Cálculo de Montaje Respeta mounting_type**
- `automatic`: usa dimensiones de la máquina
- `custom`: usa dimensiones personalizadas del usuario
- El usuario puede optimizar el corte ingresando tamaño de hoja personalizado

**4. Normalización de Terminología**
- Documentación actualizada en todos los servicios de cálculo
- Labels del formulario actualizados
- Comentarios del modelo SimpleItem actualizados

### Archivos Modificados (12)

**Servicios de Cálculo (3):**
- `SimpleItemCalculatorService.php` - Terminología + mounting_type
- `MountingCalculatorService.php` - Documentación terminología
- `CuttingCalculatorService.php` - Documentación terminología

**Modelos (3):**
- `SimpleItem.php` - Documentación campos + precio venta
- `MagazineItem.php` - getMainPaperSupplier() usa supplier_id
- `TalonarioItem.php` - getMainPaperSupplier() usa supplier_id

**Controladores/Tablas (4):**
- `DocumentsTable.php` - Agrupación por proveedor + precio venta
- `PurchaseOrderItemsRelationManager.php` - Precio venta
- `PurchaseOrderController.php` - Precio venta
- `DocumentItemController.php` - Precio venta

**Formularios (1):**
- `SimpleItemForm.php` - Labels actualizados (Hoja vs Papel)

---

## Convenciones Filament v4

### Namespaces Críticos
```php
// Layout
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tab;

// Forms
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

// Actions (SIEMPRE desde Filament\Actions)
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;

// Columns
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
```

### Estructura Resources
```
app/Filament/Resources/[Entity]/
├── [Entity]Resource.php
├── Schemas/[Entity]Form.php
├── Schemas/[Entity]Infolist.php
├── Tables/[Entity]sTable.php
└── Pages/
```

---

## Terminología de Impresión (NORMALIZADA)

```
PLIEGO (100×70cm) - Papel del proveedor (tamaño original)
    ↓ [forms_per_paper_sheet = divisor]
HOJA (ej: 50×35cm) - Corte del pliego que va a la máquina
    ↓ [copies_per_form = montaje]
TRABAJO (ej: 10×15cm) - Producto final (volante, tarjeta, etc.)
```

**Flujo:** `PLIEGO → [divisor] → HOJAS → [montaje] → TRABAJOS`

### Mapeo de Campos en SimpleItem

| Campo | Descripción |
|-------|-------------|
| `paper->width/height` | Dimensiones del PLIEGO |
| `printingMachine->max_width/height` | Dimensiones de la HOJA (automático) |
| `custom_paper_width/height` | Dimensiones de la HOJA (manual) |
| `horizontal_size/vertical_size` | Dimensiones del TRABAJO |
| `copies_per_form` | TRABAJOS por HOJA (montaje) |
| `forms_per_paper_sheet` | HOJAS por PLIEGO (divisor) |
| `paper_sheets_needed` | PLIEGOS necesarios |
| `printing_forms_needed` | HOJAS a imprimir |
| `margin_per_side` | Margen configurable (0-5cm, default 1cm) |
| `mounting_type` | 'automatic' o 'custom' |

### Tipo de Montaje

```php
// automatic: usa dimensiones de la máquina
$formWidth = $item->printingMachine->max_width;
$formHeight = $item->printingMachine->max_height;

// custom: usa dimensiones personalizadas
$formWidth = $item->custom_paper_width;
$formHeight = $item->custom_paper_height;
```

---

## Patrones de Código Reutilizables

### 1. Eager Loading en Tablas Filament
```php
use Illuminate\Database\Eloquent\Builder;

public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->with([
            'contact',
            'documentType',
            'clientCompany',
            'items.itemable',
        ]))
        ->columns([...]);
}
```

### 2. ActionGroup (Menú 3 puntos)
```php
use Filament\Actions\ActionGroup;

->actions([
    ActionGroup::make([
        ViewAction::make(),
        EditAction::make(),
        Action::make('send_email')
            ->label('Enviar Email')
            ->icon('heroicon-o-envelope')
            ->action(fn ($record) => $this->sendEmail($record)),
        DeleteAction::make(),
    ]),
])
```

### 3. Infolist 2 Columnas (Vista Limpia)
```php
return $schema
    ->columns(2)
    ->components([
        Section::make() // Sin título
            ->columnSpan(2)
            ->columns(4)
            ->schema([...]),

        Section::make()
            ->columnSpan(1)
            ->columns(2)
            ->schema([...]),
    ]);
```

### 4. Notificaciones (Solo Email Manual)
```php
// En la notificación - via() retorna ['mail']
public function via(object $notifiable): array
{
    return ['mail'];
}

// Para enviar manualmente:
\Illuminate\Support\Facades\Notification::route('mail', $clientEmail)
    ->notify(new YourNotification($recordId));
```

### 5. Enums con Interfaces Filament
```php
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Borrador',
            self::SENT => 'Enviada',
            self::IN_PROGRESS => 'En Proceso',
            self::COMPLETED => 'Finalizada',
            self::CANCELLED => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
```

### 6. Visibilidad de Acciones por Empresa
```php
->visible(function ($record) {
    $userCompanyId = auth()->user()->company_id;

    // Solo órdenes de MI empresa
    if ($record->company_id !== $userCompanyId) {
        return false;
    }

    // Verificar estado
    return in_array($record->status, [Status::DRAFT, Status::SENT]);
})
```

### 7. Precio de Venta del Papel (con fallback)
```php
// Usar precio de venta, con fallback a costo
$unitPrice = $paper->price ?? $paper->cost_per_sheet ?? 0;
$totalPrice = $sheets * $unitPrice;
```

---

## Estructura de Archivos Clave

```
app/
├── Filament/
│   ├── Pages/                    # Páginas personalizadas
│   ├── Resources/                # Resources CRUD
│   │   └── [Entity]/
│   │       ├── Schemas/          # Forms + Infolists
│   │       ├── Tables/           # Tablas
│   │       └── Pages/            # Create/Edit/View/List
│   └── Widgets/                  # Widgets dashboard
├── Models/                       # Eloquent models
├── Notifications/                # Email + Database
├── Services/                     # Lógica de negocio
│   ├── SimpleItemCalculatorService.php
│   ├── MountingCalculatorService.php
│   └── CuttingCalculatorService.php
└── Enums/                        # Estados y tipos

resources/
├── css/filament/admin/theme.css  # Estilos personalizados
└── views/
    ├── filament/                 # Vistas Filament
    └── emails/                   # Templates email
```

---

## Sistema de Estados (Workflow Unificado)

**Cotizaciones/Órdenes de Pedido/Órdenes de Producción:**
```
Draft → Sent → In Progress → Completed | Cancelled
```

**Cuentas de Cobro:**
```
Draft → Sent → Approved → Paid | Cancelled
```

**Colores:**
- `gray` = Borrador
- `info` = Enviada
- `warning` = En Proceso / Aprobada
- `success` = Finalizada / Pagada
- `danger` = Cancelada

---

## CSS Personalizado

**Fondo azul en RelationManager:**
```css
/* resources/css/filament/admin/theme.css */
.fi-resource-relation-manager {
    background-color: #e9f3ff !important;
    border-radius: 0.75rem !important;
}
```

---

## Configuración de Email (Resend)

```bash
# .env
MAIL_MAILER=resend
RESEND_API_KEY=your_api_key
MAIL_FROM_ADDRESS="noreply@grafired.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Test de email:**
```bash
php artisan resend:test tu@email.com
```

---

## Próximas Tareas Prioritarias

1. **Instalar Redis** (cache/queue):
   ```bash
   sudo apt install redis-server php8.3-redis
   # .env: CACHE_STORE=redis, SESSION_DRIVER=redis, QUEUE_CONNECTION=redis
   ```

2. **Optimización de Assets**:
   - Convertir logo a SVG
   - Configurar headers de caché

3. **Dashboard de Producción**:
   - Widgets de resumen
   - Alertas de órdenes atrasadas

---

## Historial de Sprints

| Sprint | Fecha | Descripción |
|--------|-------|-------------|
| 38 | 27-Ene | Módulo de Proyectos para agrupar documentos relacionados |
| 37 | 26-Ene | Precio venta papel + Agrupación proveedor + Terminología normalizada |
| 36 | 22-Ene | Optimización rendimiento + Fix "Iniciar Producción" |
| 35 | 10-Ene | Resend + Password Reset + Fix Emails |
| 34 | 06-Ene | Margen configurable + Fix Railway billing |
| 33 | 06-Ene | Terminología PLIEGO vs HOJA |
| 32 | 04-Ene | Estados unificados + Activity Logs |
| 31 | 31-Dic | UX mejorada + Fix notificaciones |
| 30 | 30-Dic | Stock consolidado + Solicitudes comerciales |
| 27 | 29-Dic | Magazine Pages + Menú reorganizado |
| 26 | 17-Dic | Envío manual emails - Cotizaciones |

---

## URLs de Testing

```
Dashboard:           http://127.0.0.1:8000/admin
Cotizaciones:        http://127.0.0.1:8000/admin/documents
Órdenes Pedido:      http://127.0.0.1:8000/admin/purchase-orders
Cuentas Cobro:       http://127.0.0.1:8000/admin/collection-accounts
Órdenes Producción:  http://127.0.0.1:8000/admin/production-orders
Super Admin:         http://127.0.0.1:8000/super-admin
```

**IMPORTANTE**: Usar `http://127.0.0.1:8000` (NO localhost)

---

**Versión**: 3.0.38
**Última Actualización**: 27 de Enero 2026
