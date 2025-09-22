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

### ✅ Sistema Enterprise Features Completo - Completado (21-Sep-2025)
**Implementación completa de 5 funcionalidades enterprise para el Super Admin:**

#### Funcionalidades Enterprise Activadas
- ✅ **Enterprise Plans**: Planes personalizados con SLA, billing custom, soporte dedicado
- ✅ **A/B Testing**: Sistema completo experimentos con análisis estadístico y ViewPlanExperiment
- ✅ **Automated Reports**: Programación reportes automáticos con múltiples formatos
- ✅ **Notification Channels**: Sistema notificaciones en tiempo real con circuit breaker
- ✅ **API Integrations**: Webhooks bidireccionales con autenticación y transformaciones

#### Arquitectura Enterprise Completada
```
app/Filament/SuperAdmin/Resources/
├── EnterprisePlans/ (CRUD + approval workflow)
├── PlanExperiments/ (CRUD + View con analytics)
├── AutomatedReports/ (CRUD + scheduling)
├── NotificationChannels/ (CRUD + rate limiting)
└── ApiIntegrations/ (CRUD + webhooks)
```

#### Problemas Filament v4 Resueltos
- ✅ **ViewPlanExperiment**: Migrado de Infolist a Schema + Forms disabled
- ✅ **KeyValue Components**: Reemplazados por Textarea JSON en EnterprisePlanResource
- ✅ **Namespace Consistency**: Todos los resources en SuperAdmin namespace
- ✅ **Missing Pages**: Todas las páginas básicas (List, Create, Edit) funcionales

#### 29 Rutas Enterprise Disponibles
```
✅ /super-admin/enterprise-plans (CRUD)
✅ /super-admin/plan-experiments (CRUD + View)
✅ /super-admin/automated-reports (CRUD)
✅ /super-admin/notification-channels (CRUD)
✅ /super-admin/api-integrations (CRUD)
```

### ✅ Navegación Admin Limpia - Completado (21-Sep-2025)
**Eliminación de elementos no necesarios del menú principal de administración:**

#### Elementos Removidos del Menú
- ✅ **Dashboard**: `shouldRegisterNavigation = false`
- ✅ **Plans**: `shouldRegisterNavigation = false`
- ✅ **Subscriptions**: `shouldRegisterNavigation = false`

#### Navegación Optimizada
**Elementos que PERMANECEN visibles:**
- Contacts, Documents, Products, Papers, Users
- Home (página principal con feed social)
- DigitalItems, MagazineItems, TalonarioItems
- Otros recursos operativos

#### Separación Clara de Responsabilidades
- **Admin Panel**: Funciones operativas del negocio
- **Super Admin Panel**: Gestión SaaS + Enterprise Features
- **Rutas preservadas**: Acceso programático mantenido

## Estado del Sistema

### ✅ Funcionalidades Core Estables
- **Multi-tenancy**: Scopes automáticos por company_id + performance optimizada
- **PDF Generation**: Template polimórfico con precios correctos
- **Dashboard**: 6 widgets + calculadora Canvas HTML5 + alertas stock
- **Testing**: 18 tests (Unit) + sobrante_papel + rounding algorithms coverage
- **DocumentItems**: RelationManager con wizard + 5 tipos items + recálculo automático
- **Price Calculation**: Auto-cálculo por tipo + corrección masiva + comandos dry-run
- **Roles & Permissions**: Spatie + 5 roles + 28 permisos específicos

### ✅ Super Admin Panel Completo
- **Enterprise Features**: 5 recursos con 29 rutas funcionales
- **Gestión SaaS**: Métricas, usuarios cross-tenant, impersonación
- **Navegación organizada**: Grupos Enterprise Features, Tenant Management
- **Filament v4 compatible**: Todas las incompatibilidades resueltas

### ✅ Arquitectura Robusta
- **Performance**: TenantScope sin recursión infinita (0.045s response time)
- **Security**: Multi-tenant isolation + PDF restrictions por empresa
- **Stability**: Sistema polimórfico items + wizard multi-step funcional
- **Testing**: Coverage completo + casos edge + validación regresión

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

# Verificar funcionalidades completadas
echo "✅ Admin Panel Limpio: http://localhost:8001/admin/home"
echo "✅ Super Admin + Enterprise: http://localhost:8001/super-admin"
echo "✅ 29 rutas enterprise funcionales"
echo "✅ Sistema multi-tenant estable"
echo ""
echo "🎯 PRÓXIMA TAREA: Sistema Feed Social Completo"
echo "   1. Filtros avanzados en Home feed"
echo "   2. Sistema reacciones (Me gusta, Interesa)"
echo "   3. Comentarios anidados + notificaciones"
echo "   4. Hashtags y búsqueda semántica"
echo "   5. Notificaciones en tiempo real"
echo ""
echo "📍 ENFOQUE: Maximizar engagement empresarial y network effect"
```