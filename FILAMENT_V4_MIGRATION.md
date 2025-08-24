# Guía de Migración Filament v3 a v4 - LitoPro

## Resumen de Cambios Principales

Esta guía documenta todos los cambios necesarios para migrar de Filament v3 a v4 basados en la experiencia de migración del proyecto LitoPro.

## 1. Namespaces de Componentes

### ✅ Layout Components
```php
// Filament v4
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
```

### ✅ Form Field Components
```php
// Filament v4
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Wizard;
```

### ✅ Table Components
```php
// Filament v4
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
```

### ✅ Actions
```php
// Filament v4 - Todas las actions van a Filament\Actions
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
```

### ✅ Tab Component
```php
// Filament v4
use Filament\Schemas\Components\Tabs\Tab;
```

## 2. Cambios en NavigationGroup

### ✅ Crear UnitEnum en lugar de BackedEnum
```php
<?php

namespace App\Enums;

use UnitEnum;

enum NavigationGroup: implements UnitEnum
{
    case Cotizaciones;
    case Configuracion;
    case Sistema;
    case Usuarios;

    public function getLabel(): string
    {
        return match($this) {
            self::Cotizaciones => 'Cotizaciones',
            self::Configuracion => 'Configuración',
            self::Sistema => 'Sistema',
            self::Usuarios => 'Usuarios',
        };
    }
}
```

### ✅ Uso en Resources
```php
protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Cotizaciones;
protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
```

## 3. Migración de API Form a Schema

### ❌ Filament v3
```php
public static function form(Form $form): Form
{
    return $form->schema([
        // components
    ]);
}
```

### ✅ Filament v4
```php
public static function form(Schema $schema): Schema
{
    return $schema->components([
        // components
    ]);
}
```

## 4. Migración de BadgeColumn

### ❌ Filament v3
```php
use Filament\Tables\Columns\BadgeColumn;

BadgeColumn::make('status')
    ->colors([
        'secondary' => 'draft',
        'primary' => 'sent',
    ])
```

### ✅ Filament v4
```php
TextColumn::make('status')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'draft' => 'secondary',
        'sent' => 'primary',
        default => 'secondary',
    })
```

## 5. Estructura de Resources

### ✅ Separación en clases especializadas
```php
// Resource principal
class ContactResource extends Resource
{
    public static function form(Schema $schema): Schema
    {
        return ContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactsTable::configure($table);
    }
}

// Clase de formulario separada
class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // componentes del formulario
        ]);
    }
}

// Clase de tabla separada
class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([])
            ->actions([]);
    }
}
```

## 6. Páginas CreateRecord

### ✅ Patrón correcto para páginas de creación
```php
<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    public function mount(): void
    {
        parent::mount();
        
        $this->form->fill([
            'document_type_id' => DocumentType::where('code', 'QUOTE')->first()?->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'draft',
        ]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        $data['user_id'] = auth()->id();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Lógica post-creación como crear items relacionados
    }
}
```

## 7. Errores Comunes y Soluciones

### Error: "Type of navigationGroup must be UnitEnum|string|null"
**Solución**: Usar UnitEnum en lugar de BackedEnum para NavigationGroup

### Error: "Form class not available" 
**Solución**: Migrar de Form API a Schema API

### Error: "ActionGroup not found"
**Solución**: Cambiar imports de `Filament\Tables\Actions\*` a `Filament\Actions\*`

### Error: "BadgeColumn not found"
**Solución**: Usar `TextColumn` con `->badge()`

### Error: "Tab not found"
**Solución**: Usar `Filament\Schemas\Components\Tabs\Tab`

### Error: "Grid not found"
**Solución**: Usar `Filament\Schemas\Components\Grid` para layouts

## 8. Checklist de Migración

- [ ] Actualizar NavigationGroup a UnitEnum
- [ ] Cambiar navigationIcon type a BackedEnum|string|null  
- [ ] Migrar form() methods de Form a Schema API
- [ ] Actualizar todos los imports de Actions
- [ ] Cambiar BadgeColumn por TextColumn->badge()
- [ ] Actualizar Tab imports
- [ ] Separar Resources en Form/Table classes
- [ ] Verificar CreateRecord pages
- [ ] Actualizar Grid imports a Schemas namespace

## 9. Comandos Útiles

```bash
# Crear cliente de muestra en todas las empresas
php artisan tinker --execute="
\$companies = App\Models\Company::all();
foreach (\$companies as \$company) {
    App\Models\Contact::create([
        'company_id' => \$company->id,
        'name' => 'Cliente Demo ' . \$company->name,
        'type' => 'customer',
        'email' => 'cliente@demo.com',
        'phone' => '555-0001',
        'is_active' => true,
    ]);
}
"

# Verificar errores después de migración
php artisan route:clear
php artisan config:clear
php artisan view:clear
```

## 10. Archivos Clave Modificados

1. `app/Enums/NavigationGroup.php` - UnitEnum
2. `app/Filament/Resources/*/Schemas/*Form.php` - Formularios
3. `app/Filament/Resources/*/Tables/*Table.php` - Tablas  
4. `app/Filament/Resources/*/Pages/List*.php` - Tabs
5. `app/Filament/Resources/*/Pages/Create*.php` - CreateRecord

---
*Documentación generada durante la migración de LitoPro a Filament v4*