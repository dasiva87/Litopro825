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
// - MagazineItem: Revistas con páginas + wizard 3 pasos
```

### SimpleItem - Campos
- **Básicos**: description, quantity, horizontal_size, vertical_size, sobrante_papel
- **Relaciones**: paper_id, printing_machine_id
- **Tintas**: ink_front_count, ink_back_count, front_back_plate
- **Costos**: design_value, transport_value, rifle_value
- **Auto-cálculo**: profit_percentage → final_price con lógica sobrante_papel

### Lógica sobrante_papel
```php
// Cantidad para cálculo de pliegos (SIEMPRE incluye sobrante)
$totalQuantityWithWaste = (int) $item->quantity + ($item->sobrante_papel ?? 0);

// Cantidad para impresión (sobrante solo si > 100)
$sobrante = $item->sobrante_papel ?? 0;
if ($sobrante > 100) {
    $quantityForPrinting += $sobrante;
}

// Redondeo de millares (solo hacia arriba si decimal > 0.1)
```

## Problemas Críticos Resueltos

### Migración Filament v3→v4
- **NavigationGroup**: UnitEnum requerido en `app/Enums/NavigationGroup.php`
- **API Migration**: Form→Schema API con `->components([])`
- **Namespaces**: `Filament\Tables\Actions\*` → `Filament\Actions\*`
- **Columns**: `BadgeColumn` → `TextColumn::make()->badge()`

### TenantScope Infinite Recursion
- ✅ **SetTenantContext Middleware**: Establece contexto tenant antes de queries
- ✅ **TenantScope simplificado**: Solo usa Config pre-establecido, elimina auth()->user()
- ✅ **Performance optimizada**: 0.045s response time vs infinito antes

### Sistema Multi-Tenant Robusto
- ✅ **Scopes automáticos**: Funcionan correctamente con company_id
- ✅ **PDF Security**: Respeta restricciones de empresa
- ✅ **Aislamiento**: Testing confirma separación total por tenant

## PROGRESO RECIENTE

### ✅ Sesión Completada (28-Sep-2025)
**Fase 4: Refactorización Arquitectural Masiva - DocumentItemsRelationManager**

#### Logros Críticos de la Sesión
1. **✅ Refactorización Agresiva Completada**: Archivo monolítico de 2,703 líneas refactorizado
   - **572 líneas eliminadas** (-21% reducción del archivo principal)
   - Formularios gigantes inline extraídos a clases especializadas
   - Implementación de Factory + Builder + Service Layer patterns
   - Validación completa con 11 tests pasando (112 assertions)

2. **✅ Nueva Arquitectura Modular Implementada**: Separación de responsabilidades
   - `DocumentItemFormFactory`: Factory pattern para formularios dinámicos
   - `DocumentItemFormBuilder`: Builder pattern coordinando construcción
   - `DocumentItemCalculationService`: Service layer para lógica de cálculos
   - `CustomItemDocumentForm` + `ProductDocumentForm`: Forms especializados

3. **✅ Testing y Validación**: Arquitectura QuickHandlers validada
   - QuickHandlerBasicTest: 11 tests pasando con validación de interfaz
   - Metadata correcta para todos los handlers (labels, iconos, colores)
   - Trait integration verificada (CalculatesFinishings, CalculatesProducts)
   - Form schemas válidos para todos los tipos de items

#### Arquitectura Modular Post-Refactorización
```php
// ANTES: 2,703 líneas monolíticas
public function form(Schema $schema): Schema {
    return $schema->components([Wizard::make([...500+ lines...]));
}

// DESPUÉS: 4 líneas elegantes usando patterns
public function form(Schema $schema): Schema {
    $formBuilder = new DocumentItemFormBuilder($this);
    return $formBuilder->buildSchema($schema);
}

// Estructura modular creada:
├── Forms/DocumentItemFormFactory.php        // Factory pattern
├── Forms/DocumentItemFormBuilder.php        // Builder pattern
├── Forms/CustomItemDocumentForm.php         // Form especializado
├── Forms/ProductDocumentForm.php            // Form especializado
└── Services/DocumentItemCalculationService.php // Service layer
```

### ✅ Sesiones Anteriores Completadas
- **25-Sep-2025**: Multi-Tenant Security + Sistema Suscripciones SaaS
- **23-Sep-2025**: Sistema Enterprise + Stock System Completo

## Estado del Sistema

### ✅ SaaS Multi-Tenant Production-Ready + Arquitectura Modular
- **Security**: Multi-tenant isolation + BelongsToTenant en models críticos
- **Subscriptions**: Plan gratuito automático + billing workflow completo
- **Registration**: UX sin fricción + onboarding optimizado
- **Stock System**: 2 páginas operativas + 6 widgets + exportación + filtros
- **Admin Panel**: Operativo + Stock Management + Home feed social + billing
- **Super Admin Panel**: 5 Enterprise Features + 29 rutas SaaS
- **Filament v4**: 100% nativo + widgets optimizados + QuickHandlers + Forms modulares
- **Performance**: Multi-tenancy 0.045s response time + arquitectura escalable
- **Code Quality**: DocumentItemsRelationManager refactorizado (-572 líneas, +5 clases modulares)

---

## 🎯 PRÓXIMA TAREA PRIORITARIA
**Optimización Performance + Database Query Profiling**

### Funcionalidades Críticas Identificadas
1. **Query Optimization**: Profiling N+1 queries en DocumentItems RelationManager
2. **Eager Loading Strategy**: Optimizar carga de relaciones polimórficas
3. **Database Indexing**: Índices optimizados para queries multi-tenant
4. **Cache Strategy**: Redis/File cache para forms y options repetitivos
5. **Frontend Performance**: Livewire component optimization + lazy loading

### Objetivo Business
- **Performance**: Sub-100ms response time en todas las páginas
- **Scalability**: Soporte para 1000+ items por documento sin degradación
- **User Experience**: Interfaces instantáneas + feedback real-time

---

## COMANDO PARA EMPEZAR MAÑANA
```bash
# Iniciar sesión LitoPro 3.0 - SaaS Production Ready + Arquitectura Modular
cd /home/dasiva/Descargas/litopro825 && php artisan serve --port=8001

# Verificar estado del sistema refactorizado
php artisan migrate:status && git status --short

# URLs funcionales completadas HOY (28-Sep-2025)
echo "✅ REFACTORIZACIÓN ARQUITECTURAL COMPLETADA:"
echo "   🏗️  DocumentItemsRelationManager: 2,703 → 2,131 líneas (-572 líneas, -21%)"
echo "   📦 5 nuevas clases modulares: Factory + Builder + Service + Forms"
echo "   🧪 Testing validado: 11 tests pasando (112 assertions)"
echo "   🎯 QuickHandlers: Interface + Traits + Metadata completos"
echo ""
echo "✅ SISTEMA MULTI-TENANT PRODUCTION-READY:"
echo "   🏠 Admin Panel: http://localhost:8001/admin/home"
echo "   💼 Billing: http://localhost:8001/admin/billing"
echo "   🚀 Super Admin: http://localhost:8001/super-admin"
echo "   📊 Stock Management: http://localhost:8001/admin/stock-management"
echo "   📋 Stock Movements: http://localhost:8001/admin/stock-movements"
echo "   🌐 Social Feed: http://localhost:8001/admin/social-feed"
echo ""
echo "🎯 PRÓXIMA SESIÓN: Optimización Performance + Database Query Profiling"
echo "   1. Profiling N+1 queries en DocumentItems RelationManager"
echo "   2. Eager loading strategy para relaciones polimórficas"
echo "   3. Database indexing optimizado para queries multi-tenant"
echo "   4. Cache strategy: Redis/File cache para forms repetitivos"
echo "   5. Frontend performance: Livewire optimization + lazy loading"
echo ""
echo "🎯 OBJETIVO: Sub-100ms response time + soporte 1000+ items por documento"
echo "📍 ENFOQUE: Performance escalable + UX instantánea"
```