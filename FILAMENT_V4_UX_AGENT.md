# Agente UI/UX Filament v4 - Expertise & Troubleshooting

## 🎯 Misión del Agente
Aplicar mejores prácticas de UI/UX específicas para **Filament v4.0.3** basadas en experiencias exitosas de implementación de widgets profesionales en LitoPro 3.0.

---

## ✅ EXPERIENCIA EXITOSA DOCUMENTADA

### Caso de Éxito: Stock Movements Page (23-Sep-2025)
**Problema**: Convertir KPIs hardcodeados en Blade a widgets nativos de Filament v4 profesionales
**Solución**: Implementación de 2 widgets siguiendo el patrón del `ActiveDocumentsWidget` existente

#### Widgets Implementados Exitosamente:

1. **StockMovementsKpisWidget** (`StatsOverviewWidget`)
   - 4 KPIs con datos tiempo real
   - Charts integrados con trending
   - Polling interval configurado correctamente
   - Column span responsive

2. **StockMovementsTableWidget** (`TableWidget`)
   - Filtros avanzados (tipo, fecha, item)
   - Acciones personalizadas con modales
   - Bulk actions con exportación CSV
   - Paginación optimizada

---

## 🚫 ERRORES CRÍTICOS RESUELTOS - Filament v4

### 1. **Polling Interval - Propiedad de Instancia**
```php
// ❌ ERROR COMÚN (v3 style)
protected static ?string $pollingInterval = '180s';

// ✅ CORRECTO FILAMENT V4
protected ?string $pollingInterval = '180s';
```
**Problema**: En v4 debe ser propiedad de instancia, NO estática
**Síntoma**: Widget no actualiza automáticamente

### 2. **Rendering Widgets en Pages**
```php
// ❌ ERROR COMÚN
public function renderWidgets(): array { ... } // NO EXISTE EN V4

// ✅ CORRECTO FILAMENT V4 - En Blade template
@livewire(\App\Filament\Widgets\StockMovementsKpisWidget::class)
@livewire(\App\Filament\Widgets\StockMovementsTableWidget::class)
```
**Problema**: `renderWidgets()` no existe en Filament v4 Page class
**Síntoma**: Error "Method renderWidgets does not exist"

### 3. **Actions Namespace - Crítico V4**
```php
// ❌ ERROR COMÚN (v3 namespace)
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;

// ✅ CORRECTO FILAMENT V4
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
```
**Problema**: Namespaces cambiaron de `Tables\Actions` a `Actions`
**Síntoma**: Clase no encontrada, imports incorrectos

---

## 🎨 PATRONES DE DISEÑO EXITOSOS

### StatsOverviewWidget Pattern
```php
class YourKpisWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '180s'; // ✅ Instancia, no static
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full'; // ✅ Responsive

    protected function getStats(): array
    {
        return [
            Stat::make('📈 Label', number_format($value))
                ->description($description)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($chartData), // ✅ Array de datos
        ];
    }
}
```

### TableWidget Pattern Profesional
```php
class YourTableWidget extends TableWidget
{
    protected static ?string $heading = '📋 Título Descriptivo';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full'; // ✅ Full width
    protected ?string $pollingInterval = '180s'; // ✅ Instancia

    public function table(Table $table): Table
    {
        return $table
            ->query($yourQuery)
            ->columns($this->getColumns()) // ✅ Método separado para organización
            ->filters($this->getFilters())
            ->actions($this->getActions())
            ->bulkActions($this->getBulkActions())
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->poll('30s') // ✅ Polling adicional para tabla
            ->striped()
            ->persistFiltersInSession(); // ✅ UX mejorada
    }
}
```

---

## 🎯 MEJORES PRÁCTICAS UI/UX

### 1. **Iconos & Emojis Estratégicos**
```php
// ✅ Combinación efectiva: Emoji + Heroicon
protected static ?string $heading = '📋 Documentos Activos';

// ✅ Icons con tamaño consistente (w-5 h-5 estándar)
->icon('heroicon-o-eye') // Outline para actions
->icon('heroicon-m-arrow-trending-up') // Mini para stats
```

### 2. **Column Spans Responsive**
```php
// ✅ Full width para tablas y KPIs principales
protected int | string | array $columnSpan = 'full';

// ✅ Específico para grid layouts
protected int | string | array $columnSpan = [
    'md' => 2,
    'xl' => 3,
];
```

### 3. **Polling Intervals Optimizados**
```php
// ✅ KPIs - Actualización moderada
protected ?string $pollingInterval = '180s';

// ✅ Tablas - Actualización frecuente
->poll('30s')
```

### 4. **Color Consistency**
```php
// ✅ Paleta consistente para estados
->color(fn (string $state): string => match ($state) {
    'success', 'approved', 'in' => 'success',
    'warning', 'pending', 'adjustment' => 'warning',
    'danger', 'cancelled', 'out' => 'danger',
    'info', 'sent', 'active' => 'info',
    default => 'gray',
})
```

---

## 🔧 ARQUITECTURA WIDGET PROFESIONAL

### Estructura Recomendada
```
app/Filament/Widgets/
├── [Module]KpisWidget.php          // StatsOverviewWidget
├── [Module]TableWidget.php         // TableWidget
├── [Module]ChartWidget.php         // ChartWidget
└── [Module]QuickActionsWidget.php  // Custom widget
```

### Widget Base Template
```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class YourModuleKpisWidget extends BaseWidget
{
    // ✅ Propiedades correctas V4
    protected ?string $pollingInterval = '180s';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $companyId = auth()->user()->company_id; // ✅ Multi-tenant

        // Lógica de datos...

        return [
            Stat::make('📊 KPI Name', number_format($value))
                ->description($description)
                ->descriptionIcon('heroicon-m-icon')
                ->color('success')
                ->chart($chartData),
        ];
    }
}
```

---

## 📋 CHECKLIST PRE-IMPLEMENTACIÓN

### Antes de crear widgets:
- [ ] Verificar namespaces: `use Filament\Actions\Action;` (NO Tables\Actions)
- [ ] Usar propiedades de instancia: `protected ?string $pollingInterval`
- [ ] Definir column spans responsive: `protected int | string | array $columnSpan = 'full';`
- [ ] Aplicar multi-tenancy: `->where('company_id', auth()->user()->company_id)`

### Durante implementación:
- [ ] Iconos consistentes w-5 h-5
- [ ] Colors matching business logic
- [ ] Polling intervals apropiados
- [ ] Paginación optimizada
- [ ] Filtros con persist session

### Testing final:
- [ ] Widget actualiza con polling
- [ ] Acciones funcionan correctamente
- [ ] Responsive design
- [ ] Multi-tenant isolation
- [ ] Performance aceptable (<0.5s)

---

## 🚀 COMANDOS DE VERIFICACIÓN

```bash
# Verificar widgets funcionando
php artisan filament:check

# Limpiar caché de vistas
php artisan view:clear && php artisan config:clear

# Testing de widgets
php artisan test --filter=Widget
```

---

## 📊 MÉTRICAS DE ÉXITO

**KPIs del Agente UI/UX aplicado (23-Sep-2025)**:
- ✅ **0 errores** de implementación Filament v4
- ✅ **2 widgets** creados siguiendo el patrón existente
- ✅ **100% responsive** design con column spans
- ✅ **30s polling** para updates en tiempo real
- ✅ **Multi-tenant** compatible
- ✅ **Exportación CSV** integrada en bulk actions

---

**Próxima aplicación recomendada**: Sistema Feed Social - widgets de engagement y notificaciones tiempo real

*Última actualización: 24-Sep-2025 - Documentado después del éxito en Stock Movements*