# LitoPro 3.0 - SaaS para Litografías

## Stack & Arquitectura
- **Laravel 12.25.0 + PHP 8.3.21 + Filament 4.0.3 + MySQL**
- **Multi-tenant**: Scopes automáticos por `company_id`
- **Frontend**: Livewire 3.6.4 + TailwindCSS 4.1.12

## Comandos Core
```bash
php artisan test                              # Testing completo
php artisan pint && composer analyse          # Lint + análisis
php artisan migrate && php artisan db:seed    # Setup BD
php artisan litopro:setup-demo --fresh        # Demo completo
php artisan serve --port=8000                 # Servidor local
```

## Convenciones Filament v4

### Namespaces Críticos
- **Layout**: `Filament\Schemas\Components\*` (Section, Grid, Tab)
- **Forms**: `Filament\Forms\Components\*` (TextInput, Select, etc.)
- **Actions**: `Filament\Actions\*` (NO Tables\Actions ni Pages\Actions)
- **ActionGroup**: `Filament\Actions\ActionGroup` para agrupar acciones en menú de 3 puntos
- **Columns**: `Filament\Tables\Columns\*`
- **Componentes Nativos**: `<x-filament::icon>`, `<x-filament::badge>`, `<x-filament::button>`

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

## PROGRESO RECIENTE

### ✅ Sesión Completada (31-Dic-2025)
**SPRINT 31: UX Mejorada - Vistas Limpias + Fix Notificaciones Email**

#### Logros de la Sesión

1. **✅ Vista de Cotizaciones Sin Títulos de Sección**
   - **Cambio**: Eliminados títulos de secciones (Información General, Fechas, Cliente)
   - **Archivo**: `DocumentInfolist.php`
   - **Método**: `Section::make()` sin parámetro de título
   - **Beneficio**: Vista más limpia y profesional

2. **✅ Layout 2 Columnas en Vista de Cotizaciones**
   - **Estructura**:
     - Información General: 2 columnas completas (columnSpan: 2, columns: 4)
     - Fechas Importantes: 1 columna (columnSpan: 1, columns: 2)
     - Cliente: 1 columna (columnSpan: 1, columns: 2)
   - **Beneficio**: Mejor aprovechamiento del espacio horizontal

3. **✅ Tabla de Items con Fondo Azul (#e9f3ff)**
   - **Selector CSS**: `.fi-resource-relation-manager`
   - **Archivo**: `resources/css/filament/admin/theme.css` (líneas 157-177)
   - **Aplicado a**: Todas las vistas con RelationManager de items
   - **Método**: Playwright para inspeccionar DOM y encontrar clase correcta

4. **✅ Fix Notificaciones Email - Órdenes de Pedido**
   - **Problema**: Se enviaban emails al crear órdenes de pedido desde cotizaciones
   - **Solución**: Cambiar `via()` de `['mail']` a `['database']`
   - **Archivo**: `app/Notifications/PurchaseOrderCreated.php` (línea 27)
   - **Resultado**: Solo notificaciones internas, sin emails automáticos

5. **✅ Fix Notificaciones Email - Cuentas de Cobro**
   - **Problema**: Se enviaban emails al crear cuentas de cobro
   - **Solución**:
     - `CollectionAccountSent.php`: `via()` cambiado a `['database']`
     - `CollectionAccountStatusChanged.php`: `via()` cambiado a `['database']`
   - **Excepción**: Emails de APPROVED/PAID siguen funcionando (usan `Notification::route('mail', ...)`)
   - **Resultado**: Solo notificaciones internas al crear, emails solo en eventos importantes

6. **✅ Acciones de Cuentas de Cobro en Menú de 3 Puntos**
   - **Cambio**: Todas las acciones agrupadas en `ActionGroup`
   - **Archivo**: `CollectionAccountsTable.php`
   - **Acciones agrupadas**: Ver, Editar, Ver PDF, Descargar PDF, Enviar Email, Cambiar Estado, Marcar como Pagada, Eliminar
   - **Beneficio**: UI consistente con cotizaciones, menos clutter visual

#### Archivos Modificados (Sprint 31)

**Infolists - Vista Limpia (3)**:
1. `app/Filament/Resources/Documents/Schemas/DocumentInfolist.php`
   - Eliminados títulos de secciones
   - Layout cambiado a 2 columnas
   - Sección Info General: columnSpan 2, 4 columnas internas
   - Secciones Fechas/Cliente: columnSpan 1, 2 columnas internas

2. `app/Filament/Resources/CollectionAccounts/Schemas/CollectionAccountInfolist.php`
   - Aplicado mismo patrón de 2 columnas (modificado por usuario)

3. `app/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderInfolist.php`
   - Aplicado mismo patrón de 2 columnas (creado por usuario)

**CSS - Fondo Azul Items (1)**:
4. `resources/css/filament/admin/theme.css`
   - Agregadas líneas 157-177
   - Selector: `.fi-resource-relation-manager`
   - Color: `#e9f3ff` (azul claro)
   - Aplicado a tabla, header y elementos hijos

**Notificaciones - Fix Email (3)**:
5. `app/Notifications/PurchaseOrderCreated.php`
   - Línea 27: `return ['database'];` (era `['mail']`)

6. `app/Notifications/CollectionAccountSent.php`
   - Línea 27: `return ['database'];` (era `['mail']`)

7. `app/Notifications/CollectionAccountStatusChanged.php`
   - Línea 38: `return ['database'];` (era `['mail']`)
   - Nota: `Notification::route('mail', ...)` en modelo sigue enviando emails para APPROVED/PAID

**Tablas - ActionGroup (1)**:
8. `app/Filament/Resources/CollectionAccounts/Tables/CollectionAccountsTable.php`
   - Agregado import: `use Filament\Actions\ActionGroup;` (línea 7)
   - Todas las acciones envueltas en `ActionGroup::make([...])` (líneas 170-328)

**Total Sprint 31**: 8 archivos modificados

#### Patrones Aplicados

**Patrón 1: Infolist 2 Columnas**
```php
return $schema
    ->columns(2) // DOS COLUMNAS
    ->components([
        Section::make() // Sin título
            ->columnSpan(2) // Ancho completo
            ->columns(4)    // 4 columnas internas
            ->schema([...]),

        Section::make() // Sin título
            ->columnSpan(1) // Media pantalla
            ->columns(2)    // 2 columnas internas
            ->schema([...]),

        Section::make() // Sin título
            ->columnSpan(1) // Media pantalla
            ->columns(2)    // 2 columnas internas
            ->schema([...]),
    ]);
```

**Patrón 2: ActionGroup en Tablas**
```php
use Filament\Actions\ActionGroup;

->actions([
    ActionGroup::make([
        ViewAction::make(),
        EditAction::make(),
        Action::make('custom_action')
            ->label('Acción Personalizada')
            ->icon('heroicon-o-icon')
            ->action(fn ($record) => ...),
        DeleteAction::make(),
    ]),
])
```

**Patrón 3: Notificaciones Solo Database**
```php
public function via(object $notifiable): array
{
    return ['database']; // Solo BD, NO email automático
}

// Para enviar email manualmente:
\Illuminate\Support\Facades\Notification::route('mail', $email)
    ->notify(new YourNotification($id));
```

#### Testing Realizado

```bash
✅ Vistas de cotizaciones sin títulos
✅ Layout 2 columnas funcional
✅ Fondo azul en items aplicado correctamente
✅ Selector CSS correcto (.fi-resource-relation-manager)
✅ Assets compilados (npm run build)
✅ Notificaciones PurchaseOrder sin email
✅ Notificaciones CollectionAccount sin email
✅ Emails manuales funcionan correctamente
✅ ActionGroup en cuentas de cobro funcional
✅ Sintaxis PHP sin errores
✅ Cachés limpiadas (config, views, filament)
```

#### Diferencias vs Sprint 30

**Sprint 30 (Stock Consolidado)**:
- Consolidación de 3 páginas de stock en 1
- Tabs para organizar widgets
- Badge de solicitudes pendientes
- Ocultar resources del menú

**Sprint 31 (UX + Notificaciones)**:
- Vistas más limpias (sin títulos, 2 columnas)
- Fix crítico: emails no deseados desactivados
- ActionGroup para mejor organización visual
- Patrón replicable a otros módulos

---

### ✅ Sesión Completada (30-Dic-2025)
**SPRINT 30: Consolidación de Stock + Gestión Solicitudes Comerciales**

#### Resumen Ejecutivo
- **1 página unificada**: Stock.php con 3 tabs (Resumen, Movimientos, Alertas)
- **7 archivos eliminados**: 2 páginas, 2 vistas, 3 widgets obsoletos
- **9 widgets organizados**: 3 header + 6 en tabs
- **Badge de solicitudes**: Contador dinámico en menú
- **Gestión completa**: Página de visualización con aprobar/rechazar

**Detalles**: Ver archivo de respaldo `CLAUDE_BACKUP_30DIC2025.md`

---

### ✅ Sesión Completada (29-Dic-2025)
**SPRINT 27: Magazine Pages + Menú Reorganizado + Password Reset**

#### Resumen Ejecutivo
- **Magazine Pages**: Expandido de 8 a 17+ campos (igual que SimpleItem)
- **Menú reorganizado**: Nueva sección "Contactos" + items ocultos
- **Password Reset**: 100% funcional en español
- **Sidebar personalizado**: Color #e9f3ff + scrollbar custom

**Estructura Final del Menú**:
```
📂 Contactos (sort 1) - NUEVO
   ├── Clientes y Proveedores
   ├── Clientes
   ├── Proveedores
   └── Solicitudes Comerciales

📂 Documentos (sort 2)
   ├── Cotizaciones
   ├── Órdenes de Pedido
   ├── Órdenes de Producción
   └── Cuentas de Cobro

📂 Inventario (sort 4)
   ├── Papeles
   ├── Máquinas
   └── Items Digitales
```

**Items Ocultos**: SimpleItem, MagazineItem, TalonarioItem, SupplierRelationshipResource

---

### ✅ Sesión Completada (17-Dic-2025)
**SPRINT 26: Envío Manual de Emails - Cotizaciones**

#### Resumen Ejecutivo
- **Migración**: `email_sent_at`, `email_sent_by` en tabla `documents`
- **Notificación**: `QuoteSent` con PDF adjunto
- **UI dinámica**: Label/color según estado de envío
- **Validaciones**: Items, total > 0, email del cliente

**Patrón Replicable**: Mismo flujo aplicado a Purchase Orders, Collection Accounts, Production Orders

---

## 🎯 PRÓXIMA TAREA PRIORITARIA

**Opción A - Órdenes de Producción - Envío Manual Email** (RECOMENDADO):
1. Verificar si existe `email_sent_at`, `email_sent_by` en tabla `production_orders`
2. Verificar notificación `ProductionOrderSent` (crear si no existe)
3. Agregar acción de envío manual en `ViewProductionOrder.php`
4. Agregar acción en tabla si no existe

**Opción B - Replicar Patrón de Vista Limpia**:
1. Aplicar layout 2 columnas a Production Orders
2. Eliminar títulos de secciones
3. Verificar que fondo azul de items se aplique

**Opción C - Optimizaciones**:
1. Remover placeholder de debug de `ProductQuickHandler`
2. Dashboard de producción con widgets
3. Mejoras en sistema Grafired (búsqueda, filtros)

---

## COMANDO PARA EMPEZAR

```bash
# Iniciar LitoPro 3.0 - SPRINT 31 COMPLETADO
cd /home/dasiva/Descargas/litopro825 && php artisan serve --port=8000

echo "✅ SPRINT 31 COMPLETADO (31-Dic-2025) - UX Mejorada"
echo ""
echo "📍 URLs de Testing:"
echo "   🏠 Dashboard: http://127.0.0.1:8000/admin"
echo "   📄 Cotizaciones: http://127.0.0.1:8000/admin/documents"
echo "   🛒 Órdenes Pedido: http://127.0.0.1:8000/admin/purchase-orders"
echo "   💰 Cuentas Cobro: http://127.0.0.1:8000/admin/collection-accounts"
echo "   🏭 Órdenes Producción: http://127.0.0.1:8000/admin/production-orders"
echo ""
echo "⚠️  IMPORTANTE: Usar http://127.0.0.1:8000 (NO localhost)"
echo ""
echo "🎉 SPRINT 31 - MEJORAS COMPLETADAS:"
echo "   • ✅ Vistas sin títulos (DocumentInfolist)"
echo "   • ✅ Layout 2 columnas (mejor uso del espacio)"
echo "   • ✅ Fondo azul #e9f3ff en tabla de items"
echo "   • ✅ Fix notificaciones email (PurchaseOrder + CollectionAccount)"
echo "   • ✅ ActionGroup en cuentas de cobro (menú 3 puntos)"
echo ""
echo "📋 PATRONES APLICADOS:"
echo "   1. Infolist 2 columnas: Info General (2 cols) + Fechas/Cliente (1 col c/u)"
echo "   2. Notificaciones: via() = ['database'] para evitar emails automáticos"
echo "   3. ActionGroup: Todas las acciones en menú desplegable"
echo ""
echo "🎯 PRÓXIMA TAREA:"
echo "   Opción A: Implementar envío manual en Production Orders"
echo "   Opción B: Replicar patrón de vista limpia a otros módulos"
echo "   Opción C: Dashboard de producción con widgets"
```

---

## Notas Técnicas Importantes

### Sistema de Notificaciones - Canales

**Database vs Mail**:
```php
// ❌ INCORRECTO: Envía emails automáticos
public function via(object $notifiable): array {
    return ['mail'];
}

// ✅ CORRECTO: Solo notificaciones internas
public function via(object $notifiable): array {
    return ['database'];
}

// ✅ CORRECTO: Envío manual cuando se necesita
\Illuminate\Support\Facades\Notification::route('mail', $clientEmail)
    ->notify(new YourNotification($recordId));
```

**Notificaciones Actualizadas (Sprint 31)**:
- `PurchaseOrderCreated`: `['database']` - No envía email al crear
- `CollectionAccountSent`: `['database']` - No envía email al crear
- `CollectionAccountStatusChanged`: `['database']` - Excepto APPROVED/PAID que usan `route('mail', ...)`

### CSS en Filament - RelationManager

**Problema**: Necesitas aplicar estilos a tabla de items en vista
**Solución**: Usar clase específica `.fi-resource-relation-manager`

```css
/* Fondo del RelationManager (Items) */
.fi-resource-relation-manager {
    background-color: #e9f3ff !important;
    border-radius: 0.75rem !important;
}

/* Asegurar que elementos hijos mantengan el fondo */
.fi-resource-relation-manager > * {
    background-color: #e9f3ff !important;
}

/* Header y tabla específicamente */
.fi-resource-relation-manager .fi-ta,
.fi-resource-relation-manager header,
.fi-resource-relation-manager table {
    background-color: #e9f3ff !important;
}
```

**Método para encontrar clase correcta**:
1. Usar Playwright: `mcp__playwright__browser_evaluate`
2. Inspeccionar elemento con XPath o query selector
3. Obtener `className` del contenedor correcto
4. Aplicar estilos con especificidad alta (`!important`)

### Filament v4 - ActionGroup

**Uso Correcto**:
```php
use Filament\Actions\ActionGroup; // Import correcto

->actions([
    ActionGroup::make([
        ViewAction::make(),
        EditAction::make(),
        Action::make('custom')
            ->label('Mi Acción')
            ->icon('heroicon-o-icon')
            ->action(fn ($record) => ...),
        DeleteAction::make(),
    ]),
])
```

**Resultado**: Botón de 3 puntos verticales (⋮) que muestra menú desplegable

---

## Historial de Sprints (Resumen)

- **SPRINT 31** (31-Dic): UX Mejorada - Vistas Limpias + Fix Notificaciones
- **SPRINT 30** (30-Dic): Consolidación Stock + Gestión Solicitudes
- **SPRINT 29** (30-Dic): Sistema Notificaciones + Logos PDFs
- **SPRINT 28** (30-Dic): Auto-Marcado Notificaciones + Limpieza Automática
- **SPRINT 27** (29-Dic): Magazine Pages + Menú Reorganizado + Password Reset
- **SPRINT 26** (17-Dic): Envío Manual Emails - Cotizaciones
- **SPRINT 25** (05-Dic): Búsqueda Grafired Clientes + Livewire
- **SPRINT 24** (04-Dic): Solicitudes Comerciales Completas
- **SPRINT 23** (22-Nov): Dashboard Stock + 4 Widgets + QuickActions
- **SPRINT 22** (21-Nov): Limpieza Stock Management (387 → 52 líneas)
- **SPRINT 21** (19-Nov): Acabados para Productos
- **SPRINT 20** (16-Nov): Órdenes Producción con Impresión + Acabados
- **SPRINT 19** (15-Nov): Auto-Asignación Proveedores en Acabados
- **SPRINT 18** (08-Nov): Imágenes para Productos + Cliente Dual
- **SPRINT 17** (07-Nov): "Papelería → Papelería y Productos"
- **SPRINT 16** (07-Nov): Sistema Permisos 100% + Policies
- **SPRINT 15** (06-Nov): Documentación Notificaciones
- **SPRINT 14** (06-Nov): Sistema base de Acabados + UI
- **SPRINT 13** (05-Nov): Sistema de Montaje con Divisor

---

## Recursos Útiles

### Comandos Frecuentes
```bash
# Desarrollo
php artisan serve --port=8000
npm run dev                        # Vite dev server
npm run build                      # Compilar assets

# Caché
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan filament:cache-components

# Testing
php artisan test
php artisan pint                   # Format code
composer analyse                   # PHPStan

# Base de Datos
php artisan migrate:fresh --seed
php artisan litopro:setup-demo --fresh
```

### Estructura de Archivos Clave
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
└── Services/                     # Lógica de negocio

resources/
├── css/filament/admin/theme.css # Estilos personalizados
└── views/
    ├── filament/                 # Vistas Filament
    └── emails/                   # Templates email
```

---

## Contacto y Soporte

- **GitHub Issues**: Para reportar bugs o solicitar features
- **Documentación Filament**: https://filamentphp.com/docs
- **Laravel Docs**: https://laravel.com/docs

---

**Última Actualización**: 31 de Diciembre 2025, 20:00 COT
**Versión**: 3.0.31
**Estado**: ✅ Producción
