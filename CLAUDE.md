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

### ✅ Stock Management Dashboard - Completado (23-Sep-2025)
**Solución completa del problema de renderizado CSS en Stock Management:**

#### Problema Crítico Resuelto
- ❌ **Error Original**: Iconos Heroicon gigantescos (w-12 h-12) incompatibles con Filament v4
- ❌ **Root Cause**: Template HTML personalizado + widgets duplicados + caché de vistas
- ✅ **Solución**: Migración completa a arquitectura widgets nativa de Filament v4

#### Widgets Implementados y Optimizados
```php
app/Filament/Widgets/
├── StockKpisWidget.php          // 5 KPIs con StatsOverviewWidget
├── StockPredictionsWidget.php    // Iconos w-5 h-5 (arreglado)
├── RecentMovementsWidget.php     // Iconos w-5 h-5 (arreglado)
├── QuickActionsWidget.php        // Contenedores w-8 h-8 (arreglado)
└── StockLevelTrackingWidget.php  // Iconos w-5 h-5 (arreglado)
```

#### Arquitectura CSS Corregida
- ✅ **Template Limpio**: Eliminado HTML personalizado del blade principal
- ✅ **Widget Headers**: Todos los widgets registrados en `getHeaderWidgets()`
- ✅ **Icon Sizes**: Cambiados de `w-12 h-12` a `w-5 h-5` / `w-8 h-8`
- ✅ **Cache Clearing**: `php artisan view:clear` + `config:clear` aplicado
- ✅ **Filament v4 Native**: 100% compatible con sistema CSS nativo

#### Dashboard Funcional Completo
- 📊 **5 KPIs**: Total Items, Stock Bajo, Sin Stock, Alertas, Cobertura (funcionando)
- 🔮 **Predicciones**: Widget con iconos correctos + alerts urgentes/críticos
- 📋 **Movimientos**: Lista recientes con iconos pequeños apropiados
- ⚡ **Acciones Rápidas**: 4 botones con contenedores e iconos normales
- 📈 **Seguimiento**: Niveles stock con progress bars + iconos pequeños
- 📊 **Gráfico Tendencias**: Chart.js interactivo con datos reales

### ✅ Sistema Enterprise Features Completo - (21-Sep-2025)
- **5 Resources Enterprise**: Plans, A/B Testing, Reports, Notifications, API Integrations
- **29 Rutas Funcionales**: Super Admin panel completamente operativo
- **Filament v4 Compatible**: Migración completa sin incompatibilidades

### ✅ Navegación Admin Optimizada - (21-Sep-2025)
- **Menú Limpio**: Removidos Dashboard, Plans, Subscriptions innecesarios
- **Separación Clara**: Admin (operativo) vs Super Admin (SaaS management)

## Estado del Sistema

### ✅ Funcionalidades Core Estables
- **Multi-tenancy**: Scopes automáticos por company_id + performance 0.045s
- **Stock Management**: Dashboard completo con 9 widgets + Chart.js + predicciones
- **PDF Generation**: Template polimórfico con precios correctos
- **DocumentItems**: RelationManager con wizard + 5 tipos items + recálculo automático
- **Price Calculation**: Auto-cálculo por tipo + corrección masiva + comandos dry-run
- **Roles & Permissions**: Spatie + 5 roles + 28 permisos específicos
- **Testing**: 18 tests (Unit) + coverage algoritmos sobrante_papel

### ✅ Paneles Administrativos Completos
- **Admin Panel**: Funciones operativas + Stock Management + Home feed social
- **Super Admin Panel**: 5 Enterprise Features + 29 rutas + gestión SaaS
- **Filament v4**: 100% compatible + migración completa + widgets nativos

---

## 🎯 PRÓXIMA TAREA PRIORITARIA
**Sistema Feed Social Completo - Dashboard Social Avanzado**

### Funcionalidades Pendientes Críticas
1. **Feed Filtros Avanzados**: Tipo post, ubicación, fechas, empresa
2. **Sistema Reacciones**: Me gusta, Interesa + contadores en tiempo real
3. **Comentarios Anidados**: Threading + notificaciones automáticas
4. **Hashtags & Búsqueda**: Sistema etiquetado + búsqueda semántica
5. **Notificaciones Live**: WebSockets + push notifications

### Impacto Esperado
- **Engagement Empresarial**: Interacciones entre empresas del ecosistema
- **Network Effect**: Valor agregado por conexiones comerciales
- **Retention**: Usuarios activos por funcionalidades sociales

---

## COMANDO PARA EMPEZAR MAÑANA
```bash
# Iniciar sesión de trabajo LitoPro
cd /home/dasiva/Descargas/litopro825

# Verificar estado actual del sistema
php artisan migrate:status && git status --short

# Servidor desarrollo
php artisan serve --port=8001

# Verificar funcionalidades completadas HOY
echo "✅ Stock Management Dashboard: http://localhost:8001/admin/stock-management"
echo "   - 9 widgets funcionando con iconos tamaño correcto"
echo "   - Filament v4 nativo + Chart.js + predicciones"
echo "✅ Admin Panel Operativo: http://localhost:8001/admin/home"
echo "✅ Super Admin Enterprise: http://localhost:8001/super-admin"
echo "✅ Sistema multi-tenant estable (0.045s response time)"
echo ""
echo "🎯 PRÓXIMA TAREA: Sistema Feed Social Completo"
echo "   1. Filtros avanzados en Home feed (tipo, ubicación, fechas)"
echo "   2. Sistema reacciones (Me gusta, Interesa) + contadores"
echo "   3. Comentarios anidados + threading + notificaciones"
echo "   4. Hashtags & búsqueda semántica"
echo "   5. Notificaciones en tiempo real (WebSockets/Pusher)"
echo ""
echo "📍 ENFOQUE: Maximizar engagement empresarial y network effect"
echo "🎯 OBJETIVO: Feed social interactivo para conectar empresas del ecosistema"
```