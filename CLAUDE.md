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

### 7. Queue Workers para Notificaciones
```php
use Illuminate\Contracts\Queue\ShouldQueue;

class QuoteSent extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $documentId)
    {
        $this->onQueue('emails');
    }
}
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

## Terminología de Impresión

```
PLIEGO (70×100cm) - Papel del proveedor
    ↓ [forms_per_paper_sheet = divisor]
HOJA (50×70cm) - Tamaño máquina donde se imprime
    ↓ [copies_per_form = montaje]
COPIAS (10×15cm) - Producto final
```

**Campos en SimpleItem:**
- `copies_per_form` - Copias que caben en una hoja
- `forms_per_paper_sheet` - Hojas por pliego (divisor)
- `paper_sheets_needed` - Pliegos necesarios
- `printing_forms_needed` - Hojas a imprimir
- `margin_per_side` - Margen configurable (0-5cm, default 1cm)

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

**Versión**: 3.0.36
**Última Actualización**: 26 de Enero 2026
