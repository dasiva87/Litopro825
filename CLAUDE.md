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
php artisan litopro:fix-prices --dry-run   # Verificar precios
```

## Convenciones Filament v4

### Namespaces Críticos
- **Layout**: `Filament\Schemas\Components\*` (Section, Grid, Tab)
- **Forms**: `Filament\Forms\Components\*` (TextInput, Select, etc.)
- **Actions**: `Filament\Actions\*` (NO Tables\Actions)
- **Columns**: `Filament\Tables\Columns\*`

### Estructura Resources
```
app/Filament/Resources/[Entity]/
├── [Entity]Resource.php
├── Schemas/[Entity]Form.php
├── Tables/[Entity]sTable.php
└── Pages/
```

### Models Core
- **User**: company_id + Spatie Permission
- **Document**: Cotizaciones polimórficas
- **SimpleItem/Product/DigitalItem**: Items polimórficos
- **Contact**: Clientes/proveedores
- **Paper/PrintingMachine**: Catálogos

## Problemas Críticos Resueltos

### Migración Filament v3→v4
- **NavigationGroup**: UnitEnum requerido en `app/Enums/NavigationGroup.php`
- **API Migration**: Form→Schema API con `->components([])`
- **Namespaces**: `Filament\Tables\Actions\*` → `Filament\Actions\*`
- **Columns**: `BadgeColumn` → `TextColumn::make()->badge()`

### Patrón CreateRecord
```php
class CreateQuotation extends CreateRecord {
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['company_id'] = auth()->user()->company_id;
        $data['user_id'] = auth()->id();
        return $data;
    }
}
```

### Errores Comunes Corregidos
- **PDF Fields**: `$document->number` → `$document->document_number`
- **Price Calculation**: Cast explícito `(float)` en precios
- **Context Awareness**: Detectar contexto DocumentItem vs SimpleItem
- **MySQL Strict**: `groupByRaw()` para funciones DATE()
- **Auto-generation**: Códigos únicos en `boot()` method
- **Icon Names**: `heroicon-o-lightning-bolt` → `heroicon-o-bolt`

## Sistema Polimórfico Items

### Arquitectura Core
```php
class DocumentItem {
    public function itemable(): MorphTo { return $this->morphTo(); }
}

// Items implementados:
// - SimpleItem: Cálculos automáticos con CuttingCalculatorService
// - Product: Inventario con stock y precios
// - DigitalItem: Servicios digitales (unit/size pricing)
// - TalonarioItem: Numeración secuencial + hojas como SimpleItems
```

### SimpleItem - Campos
- **Básicos**: description, quantity, horizontal_size, vertical_size
- **Relaciones**: paper_id, printing_machine_id
- **Tintas**: ink_front_count, ink_back_count, front_back_plate
- **Costos**: design_value, transport_value, rifle_value
- **Auto-cálculo**: profit_percentage → final_price

### DigitalItem - Tipos de Valoración
```php
// Tipo 'unit': Precio fijo por cantidad
Total = unit_value × quantity

// Tipo 'size': Precio por metro cuadrado  
Total = (width/100 × height/100) × unit_value × quantity
```

### TalonarioItem - Sistema Completo
```php
// Numeración: Prefijo + rango (001-1000)
// Hojas: Cada hoja es SimpleItem independiente
// Acabados: POR_NUMERO / POR_TALONARIO (numeración, perforación, engomado)
// Cálculo: suma hojas + acabados + costos adicionales
```

## DocumentItems RelationManager

### Funciones Principales
- **"Agregar Item"**: Wizard por tipos (SimpleItem, Product, DigitalItem, TalonarioItem)
- **"Item Sencillo/Producto/Digital Rápido"**: Modals optimizados
- **Tabla simplificada**: 5 columnas (Tipo, Cantidad, Descripción, Precio Unit, Total)
- **Acciones**: Editar, Ver Detalles, Duplicar, Borrar
- **Recálculo automático**: Totales actualizados en tiempo real

### Estado Items Polimórficos
- ✅ **SimpleItem**: CuttingCalculatorService + 6 secciones formulario
- ✅ **Product**: Inventario completo + gestión stock + alertas
- ✅ **DigitalItem**: Dual pricing (unit/size) + auto-generación códigos
- ✅ **TalonarioItem**: Numeración secuencial + hojas múltiples + acabados específicos
- 🔄 **MagazineItem**: Pendiente

## Herramientas de Mantenimiento

### Comandos Disponibles
```bash
php artisan litopro:setup-demo --fresh       # Demo completo
php artisan litopro:fix-prices --dry-run     # Verificar precios 0
php artisan litopro:fix-prices               # Corregir automático
```

### Métodos Helper DocumentItem
```php
public function calculateAndUpdatePrices(): bool  // Auto-cálculo por tipo
public static function fixZeroPrices(): int       // Corrección masiva
```

## Dashboard Sistema

### Widgets Implementados
- **DashboardStatsWidget**: 6 métricas con tendencias (7 días)
- **QuickActionsWidget**: 4 categorías (Documentos, Contactos, Producción, Inventario)
- **ActiveDocumentsWidget**: Tabla documentos activos con filtros
- **StockAlertsWidget**: Alertas críticas con costos de reposición
- **DeadlinesWidget**: Próximos vencimientos integrados
- **PaperCalculatorWidget**: Canvas HTML5 con visualización de cortes

### Calculadora de Papel Avanzada
- **Tamaños predefinidos**: Carta, Legal, A4, A3, Tabloide, Custom
- **Integración inventario**: Selección directa de papeles existentes
- **Cálculos duales**: Orientación horizontal/vertical automática
- **Métricas**: Eficiencia, desperdicio, aprovechamiento

### Acceso Demo
```bash
URL: /admin
Usuario: demo@litopro.test / admin@litopro.test
Password: password
```

## Testing & Demo Setup

### Testing Suite (60 tests)
- **Unit Tests**: CuttingCalculatorService (14), SimpleItemCalculatorService (15)
- **Feature Tests**: QuotationWorkflowTest (10), MultiTenantIsolationTest (11)
- **Coverage**: Polimorfismo, multi-tenancy, cálculos automáticos

### Datos Demo
- **Roles**: Super Admin, Company Admin, Manager, Employee, Client
- **Contenido**: 4 papeles, 3 máquinas, 4 productos, 3 contactos
- **Cotización**: COT-2025-DEMO-001 con items mixtos funcional

## PDF System

### Template Mejorado
```php
// Soporte completo polimórfico
@if($item->itemable_type === 'App\\Models\\SimpleItem')
    {{ $item->itemable->horizontal_size }}×{{ $item->itemable->vertical_size }}cm
@elseif($item->itemable_type === 'App\\Models\\DigitalItem')
    Servicio: {{ $item->itemable->pricing_type === 'unit' ? 'Por unidad' : 'Por m²' }}
@endif
```

### DocumentPdfController
- **Relaciones optimizadas**: `items.itemable` precargado
- **Multi-tenancy**: Validación automática por company_id

## Lecciones Críticas

### Filament v4 Key Points
1. **Resource Pattern**: Delegación a clases Form/Table es obligatoria
2. **CreateRecord Pattern**: Hooks más poderosos que métodos custom
3. **Widget Properties**: `$view` es de instancia, no static
4. **Context Awareness**: Formularios deben detectar si se llaman desde diferentes contextos

### Multi-tenancy
- **Scopes automáticos**: Funcionan correctamente con company_id
- **PDF Security**: Debe respetar restricciones de empresa
- **Aislamiento**: Testing confirma separación total por tenant

### Performance & Debugging
- **Tipo Casting**: Precios deben castearse explícito a `(float)`
- **Error Boundaries**: Try-catch en consultas complejas previene crashes
- **Dry-run Commands**: Esenciales para verificación antes de operaciones masivas
- **Canvas HTML5**: Visualizaciones interactivas mejoran significativamente UX


## Estado del Sistema
- **Multi-tenancy**: Scopes automáticos por company_id
- **PDF Generation**: Template polimórfico con precios correctos  
- **Dashboard**: 6 widgets + calculadora Canvas HTML5 + alertas stock
- **Testing**: 60 tests (Unit + Feature) + polimorfismo coverage
- **DocumentItems**: RelationManager con wizard + 4 tipos items + recálculo automático
- **Price Calculation**: Auto-cálculo por tipo + corrección masiva + comandos dry-run
- **Roles & Permissions**: Spatie + 5 roles + 28 permisos específicos

## PROGRESO RECIENTE

### ✅ Sistema Wizard Multi-Step para MagazineItem - Completado (17-Sep-2025)
**Implementación completa del wizard de 3 pasos para creación de revistas con páginas:**

#### Wizard Multi-Step Implementado
- ✅ **Paso 1**: Información Básica (descripción, cantidad, margen ganancia)
- ✅ **Paso 2**: Configuración Revista (dimensiones, encuadernación, costos)
- ✅ **Paso 3**: Configuración Páginas (repeater con 6 tipos de página)
- ✅ **Integración completa**: Misma experiencia en wizard principal y "Revista Rápida"

#### Funcionalidades Técnicas
- ✅ **MagazineItemHandler**: Método `getWizardSteps()` con 3 pasos
- ✅ **DocumentItemsRelationManager**: Casos específicos para `magazine` y `talonario`
- ✅ **Wizard Unificado**: Mismo flujo en ambos puntos de acceso
- ✅ **Cálculos automáticos**: Precios por página y totales finales
- ✅ **Manejo de errores**: Try-catch con notificaciones específicas

#### Arquitectura Implementada
```
app/Filament/Resources/Documents/RelationManagers/Handlers/
├── MagazineItemHandler.php (Wizard 3 pasos + setRecord + handleCreate)
├── DocumentItemsRelationManager.php (Integración wizard en casos magazine/talonario)
└── SimpleItemCalculatorService.php (Fix frontBackPlate casting)
```

#### Experiencia de Usuario Mejorada
- ✅ **Sin duplicar trabajo**: Crear revista + páginas en un solo wizard
- ✅ **Consistencia total**: Misma UX en wizard principal y acción rápida
- ✅ **Modal ampliado**: 7xl para acomodar wizard completo
- ✅ **Validaciones integradas**: Campos requeridos y valores por defecto

#### Errores Corregidos
- ✅ **EmptyAction class**: Eliminado referencias inexistentes
- ✅ **frontBackPlate null**: Cast a boolean con valor por defecto `false`
- ✅ **Integración handlers**: Método `setRecord()` agregado para compatibilidad

### 🎯 PRÓXIMA PRIORIDAD: Sistema Feed Social Completo
**Funcionalidades pendientes identificadas:**
- Feed centralizado con filtros avanzados (tipo post, ciudad, fechas)
- Sistema de reacciones (Me gusta, Interesa) con contadores
- Comentarios anidados en publicaciones con notificaciones
- Hashtags y sistema de búsqueda en publicaciones
- Notificaciones en tiempo real para interacciones sociales

---

## Documentación Especializada
- **Testing**: Ver `TESTING_SETUP.md`
- **Architecture**: Multi-tenant con scopes automáticos por company_id
- **Social System**: CompanyFollower + API endpoints + widgets responsive

## COMANDO PARA CONTINUAR MAÑANA
```bash
# Iniciar sesión de trabajo
cd /home/dasiva/Descargas/litopro825

# Verificar estado actual del sistema
php artisan migrate:status
git status --short

# Servidor desarrollo (puerto 8001)
php artisan serve --port=8001

# Verificar funcionalidades completadas
echo "✅ Dashboard LitoPro: http://localhost:8001/admin/dashboard"
echo "✅ Sistema Seguimiento Empresas: Widget funcional + perfiles completos"
echo "✅ MagazineItem Wizard: Crear revistas con páginas en un solo flujo"
echo "✅ DocumentItems: 4 tipos items + wizard multi-step + cálculos automáticos"
echo ""
echo "🎯 PRÓXIMA TAREA: Sistema Feed Social Completo"
echo "   - Feed centralizado con filtros avanzados"
echo "   - Reacciones (Me gusta, Interesa) con contadores"
echo "   - Comentarios anidados + notificaciones"
echo "   - Sistema hashtags y búsqueda avanzada"
echo "   - Notificaciones en tiempo real"
echo ""
echo "📝 PRUEBAS PENDIENTES:"
echo "   - Crear revista con páginas desde wizard principal"
echo "   - Crear revista desde botón 'Crear Revista Completa'"
echo "   - Verificar cálculos automáticos de precios"
```

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.21
- filament/filament (FILAMENT) - v4
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v3
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- tailwindcss (TAILWINDCSS) - v4


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- That being said, keys in an Enum should follow existing application Enum conventions.


=== filament/core rules ===

## Filament
- Filament is used by this application, check how and where to follow existing application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.
- Utilize static `make()` methods for consistent component initialization.

### Artisan
- You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features
- Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships
- Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>


## Testing
- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>


=== filament/v4 rules ===

## Filament 4

### Important Version 4 Changes
- File visibility is now `private` by default.
- The `deferFilters` method from Filament v3 is now the default behavior in Filament v4, so users must click a button before the filters are applied to the table. To disable this behavior, you can use the `deferFilters(false)` method.
- The `Grid`, `Section`, and `Fieldset` layout components no longer span all columns by default.
- The `all` pagination page method is not available for tables by default.
- All action classes extend `Filament\Actions\Action`. No action classes exist in `Filament\Tables\Actions`.
- The `Form` & `Infolist` layout components have been moved to `Filament\Schemas\Components`, for example `Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.
- A new `Repeater` component for Forms has been added.
- Icons now use the `Filament\Support\Icons\Heroicon` Enum by default. Other options are available and documented.

### Organize Component Classes Structure
- Schema components: `Schemas/Components/`
- Table columns: `Tables/Columns/`
- Table filters: `Tables/Filters/`
- Actions: `Actions/`


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()`) for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit <name>` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff"
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>