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

### ✅ TalonarioItem - Sistema Completado (04-Sep-2025)
### ✅ DocumentItemsRelationManager - Optimizado (11-Sep-2025)
**Refactorización completa con patrón Strategy:**

#### Optimización Masiva
- **Reducción código**: 4,020 → 403 líneas (**90% menos**)
- **Patrón Strategy**: Handlers especializados por tipo de item
- **Factory Pattern**: ItemHandlerFactory para gestión centralizada
- **Separación responsabilidades**: AbstractItemHandler base + handlers específicos

#### Arquitectura Mejorada
```
DocumentItemsRelationManager
├── Handlers/
│   ├── AbstractItemHandler.php (Base abstracta)
│   ├── ItemHandlerFactory.php (Factory pattern)
│   ├── MagazineItemHandler.php ✨
│   ├── SimpleItemHandler.php
│   ├── TalonarioItemHandler.php
│   ├── DigitalItemHandler.php
│   └── ProductHandler.php
└── DocumentItemsRelationManager.php (Orchestrator)
```

#### Problemas Resueltos
- ✅ **MagazineItem edición**: Funcionando correctamente
- ✅ **Código duplicado**: Eliminado 90% duplicación
- ✅ **Mantenibilidad**: Extensibilidad mejorada para nuevos tipos
- ✅ **SOLID Principles**: Aplicados correctamente

---

### ✅ TalonarioItem - Sistema Completado (04-Sep-2025)
**Implementación completa del sistema de talonarios:**

#### Arquitectura Implementada
- **Modelo TalonarioItem**: 270 líneas con BelongsToTenant + polymorphic relations
- **TalonarioSheet**: Pivot model conectando a SimpleItems
- **TalonarioCalculatorService**: 340+ líneas con lógica de negocio completa
- **FinishingMeasurementUnit**: Enum extendido (POR_NUMERO, POR_TALONARIO)

#### Características Funcionales
- **Numeración secuencial**: Prefijo + rango (001-1000) + números por talonario
- **Hojas múltiples**: Cada hoja = SimpleItem con cálculos independientes  
- **Acabados específicos**: Numeración ($15 por número), Perforación ($500 por talonario)
- **Auto-cálculos**: Suma hojas + acabados + costos + margen automático
- **Modal "Agregar Hoja"**: Form completo con materiales + tintas + dimensiones

#### Problemas Resueltos
- ✅ **Página en blanco**: Encoding UTF-8 corregido en TalonarioItemForm
- ✅ **Error enum match**: Agregados casos POR_NUMERO/POR_TALONARIO en FinishingsTable
- ✅ **Botón faltante**: Modal "Agregar Hoja" restaurado con Actions completas  
- ✅ **PrintingCalculation error**: front_back_plate boolean requerido solucionado

#### Archivos Clave Creados/Modificados
```
/database/migrations/2025_09_04_*_talonario_*.php (4 migraciones)
/app/Models/TalonarioItem.php (270 líneas)
/app/Models/TalonarioSheet.php (122 líneas)  
/app/Services/TalonarioCalculatorService.php (340+ líneas)
/app/Filament/Resources/TalonarioItems/* (Resource completo)
/database/seeders/TalonarioFinishingsSeeder.php
```

### 🎯 PRÓXIMA PRIORIDAD: Sistema de Inventario Avanzado
**Mejorar gestión de stock y reportes:**
- Alertas automáticas de stock bajo
- Reportes de movimientos de inventario
- Integración con proveedores para reposición automática
- Dashboard de inventario con métricas avanzadas
- Sistema de códigos de barras/QR para productos

**MagazineItem**: ✅ **COMPLETADO** - Funciona correctamente con handlers optimizados

---

## Documentación Especializada
- **Testing**: Ver `TESTING_SETUP.md`  
- **Architecture**: Multi-tenant con scopes automáticos por company_id

## COMANDO PARA CONTINUAR MAÑANA
```bash
# Iniciar sesión de trabajo
cd /home/dasiva/Descargas/litopro825

# Verificar estado actual
php artisan migrate:status
git status --short
php artisan litopro:setup-demo --fresh  # Si necesitas datos demo

# Servidor desarrollo
php artisan serve

# Próxima tarea: Sistema de Inventario Avanzado
echo "✅ MagazineItem completado | 🎯 Próximo: Sistema Inventario Avanzado"
```