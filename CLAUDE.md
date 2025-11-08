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
```

## Convenciones Filament v4

### Namespaces Críticos
- **Layout**: `Filament\Schemas\Components\*` (Section, Grid, Tab)
- **Forms**: `Filament\Forms\Components\*` (TextInput, Select, etc.)
- **Actions**: `Filament\Actions\*` (NO Tables\Actions)
- **Columns**: `Filament\Tables\Columns\*`
- **FileUpload**: SIEMPRE usar `->disk('public')` para archivos públicos

### Estructura Resources
```
app/Filament/Resources/[Entity]/
├── [Entity]Resource.php
├── Schemas/[Entity]Form.php
├── Tables/[Entity]sTable.php
└── Pages/
```

---

## PROGRESO RECIENTE

### ✅ Sesión Completada (08-Nov-2025)
**SPRINT 18: Sistema Completo de Imágenes para Productos + Múltiples Mejoras de UX**

#### Logros de la Sesión

1. **✅ Sistema Completo de Imágenes para Productos (1-3 imágenes)**
   - **Base de Datos**:
     - Migración: `2025_11_08_201755_add_images_to_products_table.php`
     - Campos: `image_1`, `image_2`, `image_3` (nullable)
   - **Modelo Product**:
     - Agregados a fillable: `image_1`, `image_2`, `image_3`
     - Accessor `getImagesAttribute()`: array de todas las imágenes
     - Accessor `getPrimaryImageAttribute()`: primera imagen disponible
   - **Formulario ProductForm.php**:
     - 3 FileUpload fields configurados
     - Disco: `public`, Directorio: `products`
     - Tamaño máximo: 2MB por imagen
     - Formatos: JPEG, PNG, WebP
   - **Tabla ProductsTable.php**:
     - ImageColumn circular en primera columna
     - Configurado con `->disk('public')` para correcta visualización
     - Imagen por defecto si no existe

2. **✅ Botones de Items Ocultos en Modo Vista**
   - **PurchaseOrderItemsRelationManager**: Botones solo visibles en modo edición
   - **ProductionOrderItemsRelationManager**: Botones solo visibles en modo edición
   - Implementado mismo patrón que Documents y CollectionAccounts

3. **✅ Item Personalizado para Órdenes de Producción**
   - **Archivo nuevo**: `ProductionOrders/Handlers/CustomItemQuickHandler.php` (192 líneas)
   - Formulario especializado con:
     - Descripción del trabajo
     - Cantidad a producir (default: 1000)
     - Tamaño: ancho × alto (default: 21.5 × 28 cm)
     - Tintas frente/reverso (default: 4/0)
     - Notas de producción
   - Crea CustomItem + DocumentItem + adjunta a ProductionOrder
   - Botón visible solo en modo edición

4. **✅ Fix Sistema de Clientes Dual en Múltiples Recursos**
   - **CollectionAccounts**: Agregado soporte para Contact además de Company
     - Migración: `add_contact_support_to_collection_accounts_table.php`
     - Selector dual: "Empresa Conectada" o "Cliente/Proveedor"
   - **Documents (Cotizaciones)**: Agregado soporte para client_company_id
     - Migración: `add_client_company_id_to_documents_table.php`
   - **ProductionOrders**: Agregado soporte dual para proveedores
     - Migración: `add_supplier_company_id_to_production_orders_table.php`
   - **PurchaseOrders**: Agregado soporte dual para proveedores
     - Migración: `add_supplier_id_to_purchase_orders_table.php`

5. **✅ Fix Crítico: Validación de Órdenes de Producción**
   - **Problema**: Items sin acabados/proveedores causaban error silencioso
   - **Solución**: Validación temprana en DocumentsTable.php
   - Mensaje claro: "Los items seleccionados no tienen acabados con proveedores asignados"
   - Mejor manejo de errores con notificaciones específicas

6. **✅ Fix Crítico: Error CORS en FileUpload**
   - **Problema**: CORS bloqueaba imágenes por inconsistencia localhost vs 127.0.0.1
   - **Solución**: Actualizado `.env` → `APP_URL=http://127.0.0.1:8000`
   - Caché limpiada y configuración recacheada

7. **✅ Protecciones en Collection Accounts**
   - Cuentas en estado PAID no se pueden editar
   - Redirect automático a vista con notificación
   - Botones de edición/cambio de estado ocultos si PAID
   - Botón "Descargar PDF" removido de vistas

#### Archivos Creados (Sprint 18)

**Migraciones (5)**:
1. `2025_11_08_192838_add_contact_support_to_collection_accounts_table.php`
2. `2025_11_08_193507_add_client_company_id_to_documents_table.php`
3. `2025_11_08_194018_add_supplier_company_id_to_production_orders_table.php`
4. `2025_11_08_194649_add_supplier_id_to_purchase_orders_table.php`
5. `2025_11_08_201755_add_images_to_products_table.php`

**Handlers (1)**:
6. `app/Filament/Resources/ProductionOrders/Handlers/CustomItemQuickHandler.php`

**Total**: 6 archivos nuevos

#### Archivos Modificados (Sprint 18)

**Modelos (5)**:
1. `app/Models/CollectionAccount.php` - Relación contact + accessors
2. `app/Models/Document.php` - Relación clientCompany + accessors
3. `app/Models/ProductionOrder.php` - Relación supplierCompany + accessors
4. `app/Models/PurchaseOrder.php` - Relación supplier + accessors
5. `app/Models/Product.php` - Campos de imágenes + accessors

**Formularios (6)**:
6. `app/Filament/Resources/CollectionAccounts/Schemas/CollectionAccountForm.php` - Selector dual cliente
7. `app/Filament/Resources/Documents/Schemas/DocumentForm.php` - Selector dual cliente
8. `app/Filament/Resources/ProductionOrders/Schemas/ProductionOrderForm.php` - Selector dual proveedor
9. `app/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderForm.php` - Selector dual proveedor
10. `app/Filament/Resources/Products/Schemas/ProductForm.php` - Sección de imágenes

**Tablas (2)**:
11. `app/Filament/Resources/Products/Tables/ProductsTable.php` - ImageColumn con disk
12. `app/Filament/Resources/Documents/Tables/DocumentsTable.php` - Validación producción

**RelationManagers (4)**:
13. `app/Filament/Resources/CollectionAccounts/RelationManagers/CollectionAccountItemsRelationManager.php` - Botones en edit only
14. `app/Filament/Resources/PurchaseOrders/RelationManagers/PurchaseOrderItemsRelationManager.php` - Botones en edit only
15. `app/Filament/Resources/ProductionOrders/RelationManagers/ProductionOrderItemsRelationManager.php` - Item personalizado + botones

**Páginas (2)**:
16. `app/Filament/Resources/CollectionAccounts/Pages/EditCollectionAccount.php` - Protección PAID
17. `app/Filament/Resources/CollectionAccounts/Pages/ViewCollectionAccount.php` - Protección PAID

**Configuración (2)**:
18. `.env` - APP_URL actualizado a 127.0.0.1:8000
19. `config/livewire.php` - Publicado para configuración temporal files

**Total Sprint 18**: 6 archivos nuevos, 19 archivos modificados, 5 migraciones ejecutadas

#### Problemas Resueltos

**FileUpload - Carga Infinita**:
- ❌ Problema: Imágenes se quedaban cargando infinitamente
- ✅ Solución: Error CORS por inconsistencia localhost vs 127.0.0.1
- ✅ Fix: APP_URL actualizado + simplificación de FileUpload

**FileUpload - No se guarda en BD**:
- ❌ Problema: Archivos se subían pero ruta no se guardaba
- ✅ Verificación: mutateFormDataBeforeSave() mostró que SÍ llegaban los datos
- ✅ Conclusión: El guardado funcionaba correctamente

**ImageColumn - No muestra imágenes**:
- ❌ Problema: Tabla no mostraba imágenes aunque estaban en BD
- ✅ Solución: Agregar `->disk('public')` a ImageColumn
- ✅ Resultado: Imágenes circulares visibles en tabla

**Órdenes de Producción - Error Silencioso**:
- ❌ Problema: Items sin acabados/proveedores causaban error
- ✅ Solución: Validación temprana con mensaje claro
- ✅ Mejora: Manejo de errores detallado

#### Testing Realizado

```bash
✅ Sintaxis PHP verificada en todos los archivos
✅ Migraciones ejecutadas exitosamente
✅ Imágenes se suben, guardan y muestran correctamente
✅ Botones de items ocultos en modo vista
✅ Item personalizado en órdenes de producción funcional
✅ Sistema dual cliente/proveedor funcionando
✅ Protecciones de estado PAID operativas
✅ CORS resuelto, sin errores de carga
```

---

### ✅ Sesión Completada (07-Nov-2025 - Parte 3)
**SPRINT 17: Actualización de Nomenclatura - Papelería → Papelería y Productos**

#### Logros de la Sesión

1. **✅ Actualizado CompanyType Enum**
   - **Archivo**: `app/Enums/CompanyType.php`
   - **Cambios**:
     - Label: "Papelería" → "Papelería y Productos"
     - Descripción: "Empresa dedicada a la venta de papeles y productos de oficina" → "Empresa dedicada a la venta de papeles, productos y suministros de oficina"

2. **✅ Formularios Actualizados** (2 archivos)
   - `app/Filament/Pages/Auth/Register.php` - Select del tipo de empresa
   - Opciones ahora muestra "Papelería y Productos" en lugar de "Papelería"

3. **✅ Labels de Interfaz Actualizados** (8 archivos)
   - **Filtros de tablas**: "Papelería" → "Proveedor" (más genérico y preciso)
     - `ProductsTable.php` - Filtro por proveedor
     - `PapersTable.php` - Filtro por proveedor
   - **Relaciones con proveedores**: "Papelería" → "Proveedor"
     - `SupplierRelationshipsTable.php` - Columna y select de proveedor
     - `SupplierRelationshipForm.php` - Select de proveedor
     - `SupplierRequestsTable.php` - Columna de proveedor
     - `SupplierRequestForm.php` - "Papelería Proveedora" → "Empresa Proveedora"
     - `SuppliersRelationManager.php` - Columna y select de proveedor (2 lugares)

#### Razón del Cambio

El nombre "Papelería" limitaba conceptualmente el alcance del tipo de empresa. El nuevo nombre "Papelería y Productos" refleja mejor que estas empresas no solo venden papel, sino también:
- Productos de oficina
- Suministros generales
- Artículos para litografías

#### Archivos Modificados (10)

**Enum (1)**:
1. `app/Enums/CompanyType.php` - label() y description()

**Formularios (2)**:
2. `app/Filament/Pages/Auth/Register.php` - Opciones del select

**Tablas y Formularios de UI (7)**:
3. `app/Filament/Resources/Products/Tables/ProductsTable.php`
4. `app/Filament/Resources/Papers/Tables/PapersTable.php`
5. `app/Filament/Resources/SupplierRelationships/Tables/SupplierRelationshipsTable.php` (2 cambios)
6. `app/Filament/Resources/SupplierRelationships/Schemas/SupplierRelationshipForm.php`
7. `app/Filament/Resources/SupplierRequests/Tables/SupplierRequestsTable.php`
8. `app/Filament/Resources/SupplierRequests/Schemas/SupplierRequestForm.php`
9. `app/Filament/Resources/Contacts/RelationManagers/SuppliersRelationManager.php` (2 cambios)

**Total**: 10 archivos modificados, 15 cambios de texto

#### Validación

```bash
✅ Sintaxis verificada en todos los archivos
✅ 0 errores de sintaxis
✅ Lógica de negocio intacta (solo cambios de labels)
```

---

### ✅ Sesión Completada (07-Nov-2025 - Parte 2)
**SPRINT 16.2: Finalización Completa Sistema de Permisos - 12 Recursos con 3 Capas**

#### Logros de la Sesión

1. **✅ Creadas 4 Nuevas Policies Completas**
   - **PaperPolicy** (105 líneas) - app/Policies/PaperPolicy.php
     - Métodos: viewAny, view, create, update, delete, restore, forceDelete, adjustStock, toggleActive
     - Verificación de proveedores aprobados para litografías
   - **PrintingMachinePolicy** (86 líneas) - app/Policies/PrintingMachinePolicy.php
     - Métodos: viewAny, view, create, update, delete, restore, forceDelete, toggleActive
   - **FinishingPolicy** (95 líneas) - app/Policies/FinishingPolicy.php
     - Métodos: viewAny, view, create, update, delete, restore, forceDelete, toggleActive, manageRanges
   - **CollectionAccountPolicy** (128 líneas) - app/Policies/CollectionAccountPolicy.php
     - Métodos: viewAny, view, create, update, delete, restore, forceDelete, send, approve, markAsPaid, changeStatus
     - Vista dual: empresa creadora O empresa cliente

2. **✅ AuthServiceProvider Actualizado**
   - **Archivo**: app/Providers/AuthServiceProvider.php
   - **Agregadas 4 Policies**: Paper, PrintingMachine, Finishing, CollectionAccount
   - **Imports ordenados**: 12 modelos + 12 policies
   - **Categorización mejorada**: User & Role / Core Business / Orders & Accounting / Configuration & Resources

3. **✅ Recursos Actualizados para Usar Policies**
   - **PaperResource**: Ahora usa `can('viewAny', Paper::class)` en lugar de verificación directa de roles
   - **PrintingMachineResource**: Ahora usa `can('viewAny', PrintingMachine::class)`
   - **FinishingResource**: Ahora usa `can('viewAny', Finishing::class)`
   - **CollectionAccountResource**: Ahora usa `can('viewAny', CollectionAccount::class)`

#### Estado Final Completo de Verificación de Permisos

| Recurso | canViewAny() | Policy | Estado |
|---------|--------------|--------|--------|
| Users | ✅ | ✅ | **Completo (3 capas)** |
| Roles | ✅ | ✅ | **Completo (3 capas)** |
| Posts (Widget) | ✅ | ✅ | **Completo (3 capas)** |
| Documents | ✅ | ✅ | **Completo (3 capas)** |
| Contacts | ✅ | ✅ | **Completo (3 capas)** |
| Products | ✅ | ✅ | **Completo (3 capas)** |
| SimpleItems | ✅ | ✅ | **Completo (3 capas)** |
| PurchaseOrders | ✅ | ✅ | **Completo (3 capas)** |
| ProductionOrders | ✅ | ✅ | **Completo (3 capas)** |
| Papers | ✅ | ✅ | **Completo (3 capas)** ⭐ |
| PrintingMachines | ✅ | ✅ | **Completo (3 capas)** ⭐ |
| Finishings | ✅ | ✅ | **Completo (3 capas)** ⭐ |
| CollectionAccounts | ✅ | ✅ | **Completo (3 capas)** ⭐ |

**Resultado FINAL**: 🎉 **12 de 12 recursos con verificación completa de 3 capas** (100%)

#### Archivos Creados/Modificados (Sprint 16.2)

**Nuevos archivos (4 Policies)**:
1. `app/Policies/PaperPolicy.php` (105 líneas)
2. `app/Policies/PrintingMachinePolicy.php` (86 líneas)
3. `app/Policies/FinishingPolicy.php` (95 líneas)
4. `app/Policies/CollectionAccountPolicy.php` (128 líneas)

**Archivos modificados (5)**:
1. `app/Providers/AuthServiceProvider.php` (+12 imports, +3 policies en array)
2. `app/Filament/Resources/Papers/PaperResource.php` (simplificado canViewAny)
3. `app/Filament/Resources/PrintingMachines/PrintingMachineResource.php` (simplificado canViewAny)
4. `app/Filament/Resources/Finishings/FinishingResource.php` (simplificado canViewAny)
5. `app/Filament/Resources/CollectionAccounts/CollectionAccountResource.php` (simplificado canViewAny)

**Total Sprint 16.2**: 4 archivos nuevos (414 líneas), 5 archivos modificados

#### Características Clave de las Policies

**PaperPolicy**:
- ✅ Proveedores aprobados: Litografías pueden ver papeles de proveedores activos
- ✅ Stock protection: No permite eliminar si tiene movimientos de stock
- ✅ Solo Admin/Manager pueden gestionar papeles

**PrintingMachinePolicy**:
- ✅ Aislamiento estricto por empresa
- ✅ No permite eliminar si tiene items asociados
- ✅ Solo Admin/Manager pueden gestionar máquinas

**FinishingPolicy**:
- ✅ Verificación de items asociados (SimpleItems + DigitalItems)
- ✅ Gestión de rangos de precios (manageRanges)
- ✅ Solo Admin/Manager pueden gestionar acabados

**CollectionAccountPolicy**:
- ✅ Vista dual completa: empresa creadora O cliente
- ✅ Estado-dependent operations (draft/pending/sent)
- ✅ Cliente puede marcar como pagado
- ✅ Solo Admin puede aprobar cuentas

---

### ✅ Sesión Completada (07-Nov-2025 - Parte 1)
**SPRINT 16.1: Completar Sistema de Permisos - Arquitectura de 3 Capas**

#### Logros de la Sesión

1. **✅ Generación de Sitemap Completo (145 KB)**
   - **Archivo**: `LITOPRO_SITEMAP.md`
   - **Contenido**: 9 secciones + 4 anexos técnicos
   - **Documentación de**:
     - 19 Recursos CRUD completos
     - 11 Páginas personalizadas
     - 29 Widgets de dashboard
     - 40+ Rutas web
     - 9 API Endpoints
     - 67 Modelos con relaciones
     - 8 Roles y 56 Permisos
     - 10 Flujos principales de negocio

2. **✅ Sistema de Permisos Completado (3 Capas)**
   - **Agregado `canViewAny()` a 5 recursos**:
     - `DocumentResource` (app/Filament/Resources/Documents/DocumentResource.php:39-42)
     - `ContactResource` (app/Filament/Resources/Contacts/ContactResource.php:38-41)
     - `ProductResource` (app/Filament/Resources/Products/ProductResource.php:34-37)
     - `SimpleItemResource` (app/Filament/Resources/SimpleItems/SimpleItemResource.php:40-43)
     - `PurchaseOrderResource` (app/Filament/Resources/PurchaseOrders/PurchaseOrderResource.php:33-36)

3. **✅ Creada ProductionOrderPolicy (110 líneas)**
   - **Archivo**: `app/Policies/ProductionOrderPolicy.php`
   - **Métodos implementados**:
     - `viewAny()` - Usuarios con empresa pueden ver órdenes
     - `view()` - Solo empresa propietaria O operador asignado
     - `create()` - Usuarios con empresa pueden crear
     - `update()` - Empresa propietaria O operador asignado
     - `delete()` - Solo empresa propietaria y estado pending/draft
     - `restore()` / `forceDelete()` - Solo empresa propietaria
     - `assignOperator()` - Solo usuarios de la empresa
     - `qualityCheck()` - Solo Admin/Manager de la empresa
     - `changeStatus()` - Operador O Admin/Manager
   - **Agregado `canViewAny()` a ProductionOrderResource** (línea 35-38)
   - **Registrada en AuthServiceProvider** (línea 45)

#### Estado Final de Verificación de Permisos

| Recurso | canViewAny() | Policy | Estado |
|---------|--------------|--------|--------|
| Users | ✅ | ✅ | **Completo (3 capas)** |
| Roles | ✅ | ✅ | **Completo (3 capas)** |
| Papers | ✅ | ❌ | Parcial |
| PrintingMachines | ✅ | ❌ | Parcial |
| Finishings | ✅ | ❌ | Parcial |
| CollectionAccounts | ✅ | ❌ | Parcial |
| Posts (Widget) | ✅ | ✅ | **Completo (3 capas)** |
| Documents | ✅ | ✅ | **Completo (3 capas)** ⭐ |
| Contacts | ✅ | ✅ | **Completo (3 capas)** ⭐ |
| Products | ✅ | ✅ | **Completo (3 capas)** ⭐ |
| SimpleItems | ✅ | ✅ | **Completo (3 capas)** ⭐ |
| PurchaseOrders | ✅ | ✅ | **Completo (3 capas)** ⭐ |
| ProductionOrders | ✅ | ✅ | **Completo (3 capas)** ⭐ |

**Resultado**: 8 recursos con verificación completa de 3 capas (Sprint 16 ⭐)

#### Archivos Modificados

1. `app/Filament/Resources/Documents/DocumentResource.php` (+5 líneas)
2. `app/Filament/Resources/Contacts/ContactResource.php` (+5 líneas)
3. `app/Filament/Resources/Products/ProductResource.php` (+5 líneas)
4. `app/Filament/Resources/SimpleItems/SimpleItemResource.php` (+5 líneas)
5. `app/Filament/Resources/PurchaseOrders/PurchaseOrderResource.php` (+5 líneas)
6. `app/Filament/Resources/ProductionOrders/ProductionOrderResource.php` (+5 líneas)
7. `app/Policies/ProductionOrderPolicy.php` (nuevo, 110 líneas)
8. `app/Providers/AuthServiceProvider.php` (+2 líneas)
9. `LITOPRO_SITEMAP.md` (nuevo, 145 KB)

**Total**: 1 archivo nuevo (Policy), 7 archivos modificados, 1 sitemap generado

---

### ✅ Sesión Completada (06-Nov-2025 - Parte 6)
**SPRINT 15: Documentación Sistema de Notificaciones**

#### Logros de la Sesión

1. **✅ Análisis Completo del Sistema de Notificaciones**
   - **Alcance**: Exploración exhaustiva de 27 archivos (2600+ líneas de código)
   - **4 tipos de notificaciones identificados**:
     - Notificaciones Sociales (SocialNotification) - Red social interna
     - Alertas de Inventario (StockAlert + StockMovement) - Stock crítico
     - Sistema Avanzado (NotificationChannel + Rule + Log) - Canales configurables
     - Sistema Laravel Base (Notifications) - Notificaciones estándar

2. **✅ Documentación Técnica Generada (66 KB)**
   - `NOTIFICATION_SYSTEM_ANALYSIS.md` (40 KB) - Análisis técnico completo
   - `NOTIFICATION_SYSTEM_SUMMARY.md` (15 KB) - Resumen ejecutivo
   - `NOTIFICATION_FILE_REFERENCES.md` (11 KB) - Índice de archivos con líneas exactas
   - `README_NOTIFICATIONS.md` - Guía de navegación

3. **✅ Arquitectura Multi-Tenant Verificada**
   - Aislamiento automático por `company_id` en todos los modelos
   - 7 tablas de notificaciones documentadas con DDL completo
   - 2 servicios principales (NotificationService + StockNotificationService)
   - 5 canales de comunicación (email, database, SMS, push, custom)

#### Componentes Documentados

**Modelos (7)**:
- `SocialNotification` (11 campos) - Posts y actividad social
- `StockAlert` (27 campos) - Alertas de inventario crítico
- `StockMovement` (21 campos) - Movimientos de stock
- `NotificationChannel` (34 campos) - Canales configurables
- `NotificationRule` (49 campos) - Reglas de envío
- `NotificationLog` (40 campos) - Auditoría completa
- `Notification` (Laravel) - Sistema base

**Servicios (2)**:
- `NotificationService` (219 líneas, 7 métodos) - Servicio principal
- `StockNotificationService` (290 líneas, 8 métodos) - Alertas de stock

**Características Clave**:
- ✅ Multi-tenant con aislamiento automático
- ✅ Procesamiento asíncrono (Laravel Queue)
- ✅ Deduplicación de notificaciones
- ✅ Filtrado por rol y severidad
- ✅ Auditoría completa (notification_logs)
- ✅ Configuración flexible (canales + reglas)

#### Archivos de Documentación Creados

```
/home/dasiva/Descargas/litopro825/
├── NOTIFICATION_SYSTEM_ANALYSIS.md      # 40 KB - Análisis técnico
├── NOTIFICATION_SYSTEM_SUMMARY.md       # 15 KB - Guía rápida
├── NOTIFICATION_FILE_REFERENCES.md      # 11 KB - Índice de archivos
└── README_NOTIFICATIONS.md              # Navegación
```

---

### ✅ Sesión Completada (06-Nov-2025 - Parte 5)
**SPRINT 14.4: Fix de Verificación de Permisos en Acciones**

#### Logros de la Sesión

1. **✅ Problema Identificado: Permisos no se verificaban en acciones**
   - **Caso**: Usuario Salesperson sin permiso `create-posts` podía crear posts
   - **Causa raíz**: CreatePostWidget NO verificaba permisos antes de permitir la acción
   - **Alcance**: Problema encontrado en widgets y algunos recursos

2. **✅ Solución Implementada: Policy + Widget Protection**
   - **Creada Policy**: `SocialPostPolicy` con verificación completa
   - **Widget protegido**: `CreatePostWidget` ahora verifica permisos
   - **Métodos agregados**:
     - `canView()` - Solo muestra widget si puede crear posts
     - Verificación en `createPost()` antes de ejecutar acción

3. **✅ Arquitectura de Permisos Explicada**
   - **Spatie Permission**: Base del sistema (roles, permisos, BD)
   - **Laravel Policies**: Capa de lógica de negocio
   - **Filament Resources**: Capa de interfaz (canViewAny, canCreate, etc.)
   - **Combinación**: Máxima seguridad con 3 capas de verificación

#### Archivos Creados/Modificados

1. **Creado**: `app/Policies/SocialPostPolicy.php`
   - `viewAny()`: Requiere `view-posts`
   - `create()`: Requiere `create-posts`
   - `update()`: Requiere `edit-posts` O ser autor
   - `delete()`: Requiere `delete-posts` O ser autor
   - Todas las acciones verifican `company_id`

2. **Modificado**: `app/Filament/Widgets/CreatePostWidget.php`
   - Agregado `canView()`: Oculta widget si no puede crear
   - Agregada verificación en `createPost()`: Previene acción si no tiene permiso

#### Estado de Verificación de Permisos por Recurso

| Recurso | Estado | Protección |
|---------|--------|------------|
| Users | ✅ Completo | Policy + canViewAny() |
| Roles | ✅ Completo | Policy + canViewAny() |
| Papers | ✅ Completo | canViewAny() |
| PrintingMachines | ✅ Completo | canViewAny() |
| Finishings | ✅ Completo | canViewAny() |
| CollectionAccounts | ✅ Completo | canViewAny() |
| Posts (Widget) | ✅ Completo | Policy + canView() |
| Documents | ⚠️ Parcial | Solo Policy |
| Contacts | ⚠️ Parcial | Solo Policy |
| Products | ⚠️ Parcial | Solo Policy |
| SimpleItems | ⚠️ Parcial | Solo Policy |
| PurchaseOrders | ⚠️ Parcial | Solo Policy |
| ProductionOrders | ❌ Sin verificación | Ninguna |

#### Métodos de Verificación de Permisos

**Usando Spatie Permission (Base):**
```php
// Verificar permiso directo
$user->hasPermissionTo('create-posts')

// Verificar rol
$user->hasRole('Manager')

// Verificar cualquier rol
$user->hasAnyRole(['Manager', 'Admin'])
```

**Usando Policies (Recomendado):**
```php
// En código
$user->can('create', SocialPost::class)
$user->can('update', $post)

// En Filament Resources
public static function canViewAny(): bool {
    return auth()->user()->can('viewAny', Model::class);
}
```

**Arquitectura (3 Capas):**
```
Interfaz (Resource/Widget)
    ↓ can('create', Model)
Policy (Lógica de Negocio)
    ↓ hasPermissionTo('create-posts')
Spatie (Base de Datos)
    ↓ role_has_permissions
✅ Acceso Permitido
```

#### Testing Realizado

✅ **Caso 1: Salesperson sin create-posts**
- Widget "Crear Post" NO aparece en dashboard
- Si intenta acceder por URL: Error 403

✅ **Caso 2: Manager con create-posts**
- Widget visible
- Puede crear posts exitosamente

---

### ✅ Sesión Completada (06-Nov-2025 - Parte 4)
**SPRINT 14.3: Fix de Interfaz de Gestión de Roles**

#### Logros de la Sesión

1. **✅ Problema Identificado: Formulario de roles incompleto**
   - **Causa raíz**: Solo mostraba 43 permisos de 56 existentes en BD
   - **Permisos faltantes**:
     - Gestión de Empresas (view/create/edit/delete-companies)
     - Inventario (manage-inventory, manage-paper-catalog, manage-printing-machines)
   - **Resultado**: No se podían asignar todos los permisos disponibles

2. **✅ Solución Implementada: Categorías Completas**
   - **Nueva sección agregada**: "Gestión de Empresas" (solo Super Admin)
   - **Nueva sección agregada**: "Inventario"
   - **Formulario actualizado**: Ahora muestra TODOS los 56 permisos del sistema
   - **Categorización mejorada**: Separación clara entre inventario y sistema

3. **✅ Archivos Actualizados**
   - `RoleForm.php`: Agregadas secciones de Companies e Inventory
   - `EditRole.php`: Actualizado para cargar/guardar nuevas categorías
   - Sincronización correcta entre formulario y BD

#### Archivos Modificados

1. `app/Filament/Resources/Roles/Schemas/RoleForm.php`
   - Agregada sección "Gestión de Empresas" (línea 93-102)
   - Agregada sección "Inventario" (línea 104-111)
   - Actualizado `getPermissionsByCategory()` con nuevas categorías (línea 152-153)

2. `app/Filament/Resources/Roles/Pages/EditRole.php`
   - Agregado `company_permissions` e `inventory_permissions` en carga (línea 28-29)
   - Agregado `company_permissions` e `inventory_permissions` en guardado (línea 59-60)

#### Permisos por Categoría Actualizados

```
Gestión de Usuarios: 4 permisos
Gestión de Contactos: 4 permisos
Cotizaciones: 6 permisos
Documentos: 5 permisos
Órdenes de Producción: 5 permisos
Órdenes de Papel: 4 permisos
Productos: 4 permisos
Equipos: 4 permisos
Empresas: 4 permisos (solo Super Admin)
Inventario: 3 permisos
Sistema: 6 permisos
Reportes: 2 permisos
Red Social: 5 permisos
---
TOTAL: 56 permisos ✅
```

---

### ✅ Sesión Completada (06-Nov-2025 - Parte 3)
**SPRINT 14.2: Fix Crítico de Permisos por Rol**

#### Logros de la Sesión

1. **✅ Problema Identificado: Salesperson tenía acceso a TODO**
   - **Causa raíz**: Recursos críticos NO tenían método `canViewAny()` configurado
   - **Afectados**: Papers, PrintingMachines, Finishings, CollectionAccounts
   - **Resultado**: Cualquier usuario autenticado podía acceder a estos recursos

2. **✅ Solución Implementada: Restricciones por Rol**
   - **Método agregado**: `canViewAny()` a recursos críticos
   - **Roles permitidos**: Solo `Super Admin`, `Company Admin`, `Manager`
   - **Salesperson bloqueado** de:
     - Papers (gestión de papeles)
     - PrintingMachines (máquinas de impresión)
     - Finishings (acabados)
     - CollectionAccounts (cuentas de cobro)

3. **✅ Sistema de Roles Verificado**
   - 8 roles en el sistema: Super Admin, Company Admin, Manager, Salesperson, Operator, Customer, Employee, Client
   - Salesperson tiene 15 permisos específicos (contactos, cotizaciones, órdenes de producción)
   - UserResource ya tenía restricciones correctas (solo Admin)
   - RoleResource ya tenía restricciones correctas (solo Admin)

#### Archivos Modificados

1. `app/Filament/Resources/Papers/PaperResource.php`
   - Agregado `canViewAny()` - Solo Admin/Manager (línea 42-46)

2. `app/Filament/Resources/PrintingMachines/PrintingMachineResource.php`
   - Agregado `canViewAny()` - Solo Admin/Manager (línea 45-49)

3. `app/Filament/Resources/Finishings/FinishingResource.php`
   - Agregado `canViewAny()` - Solo Admin/Manager (línea 44-48)

4. `app/Filament/Resources/CollectionAccounts/CollectionAccountResource.php`
   - Agregado `canViewAny()` - Solo Admin/Manager (línea 38-42)

#### Testing Sugerido

```bash
# Crear usuario Salesperson y verificar:
# ✅ Puede ver: Documents, Contacts, ProductionOrders
# ❌ NO puede ver: Papers, PrintingMachines, Finishings, CollectionAccounts, Users, Roles
```

---

### ✅ Sesión Completada (06-Nov-2025 - Parte 2)
**SPRINT 14.1: UI de Acabados + Fix de Billing**

#### Logros de la Sesión

1. **✅ Interfaz de Acabados en SimpleItem**
   - **Archivo**: `app/Filament/Resources/SimpleItems/Schemas/SimpleItemForm.php`
   - **Nueva sección**: "🎨 Acabados Sugeridos" (collapsed por defecto)
   - **Características**:
     - Repeater con relación `finishings` (tabla pivot)
     - Auto-población de parámetros según tipo de acabado
     - Campos dinámicos (cantidad para MILLAR/RANGO/UNIDAD, ancho/alto para TAMAÑO)
     - Cálculo de costo en tiempo real
     - Total de acabados al final de la sección
     - Toggle `is_default` para marcar sugerencias automáticas

2. **✅ Ocultada Opción "Tiro y Retiro en Misma Plancha"**
   - **Cambio**: Removido Toggle `front_back_plate` de la interfaz
   - **Grid cambiado**: De 4 columnas a 3 columnas
   - **Backend intacto**: Campo sigue existiendo en BD pero no es visible

3. **✅ Fix Crítico: Redirección a /admin/billing**
   - **Problema**: Usuarios quedaban atrapados en página de billing
   - **Causa raíz 1**: Método `getCurrentPlan()` retornaba `null` para plan "free"
   - **Causa raíz 2**: Método buscaba por `name` en lugar de `slug`
   - **Causa raíz 3**: Company tenía `status = 'incomplete'` en lugar de `'active'`
   - **Solución**:
     - `app/Models/Company.php:313-321` - Corregido `getCurrentPlan()` para buscar por slug
     - Removida condición que excluía plan "free"
     - Actualizado status de empresa a 'active'

#### Testing Realizado

✅ **getCurrentPlan() corregido**:
```php
$company->subscription_plan = 'free';
$plan = $company->getCurrentPlan(); // Ahora retorna Plan Gratuito ✅
```

✅ **Interfaz de acabados**:
- Repeater funcional con relación pivot
- Auto-población de campos según tipo
- Cálculo en tiempo real funciona

#### Archivos Modificados

1. `app/Filament/Resources/SimpleItems/Schemas/SimpleItemForm.php`
   - Agregada sección de acabados (líneas 679-858)
   - Removido toggle `front_back_plate` (línea 169-199)

2. `app/Models/Company.php`
   - `getCurrentPlan()` ahora busca por `slug` en lugar de `name`
   - Removida exclusión de plan "free"

---

### ✅ Sprint 13 (05-Nov-2025)
**Nuevo Sistema de Montaje con Divisor de Cortes**
- Método `calculateMountingWithCuts()`: Integración MountingCalculatorService + CuttingCalculatorService
- Millares calculados sobre **impresiones** (no pliegos)
- Fórmula: `pliegos = ceil(impresiones ÷ divisor)`
- Ver sección "Notas Técnicas" para detalles de implementación

---

### ✅ Sprint 14 (06-Nov-2025)
**Sistema de Acabados para SimpleItem**
- Sistema híbrido: SimpleItem (sugerencias) + DocumentItem (aplicados)
- Tabla pivot `simple_item_finishing` con parámetros dinámicos
- Métodos: `addFinishing()`, `calculateFinishingsCost()`, `getFinishingsBreakdown()`
- Integración completa con SimpleItemCalculatorService
- Ver sección "Notas Técnicas" para ejemplos de uso

---

## 🎯 PRÓXIMA TAREA PRIORITARIA

**✅✅ Sistema de Permisos 100% Completado (Sprint 16.2)**

**Estado FINAL**: 🎉 **12 de 12 recursos con verificación completa de 3 capas**
- ✅ Users, Roles, Posts (Widget)
- ✅ Documents, Contacts, Products, SimpleItems
- ✅ PurchaseOrders, ProductionOrders, CollectionAccounts
- ✅ Papers, PrintingMachines, Finishings

**Arquitectura de Seguridad Completa**:
```
Interfaz (Resource/Widget)
    ↓ can('action', Model)
Policy (Lógica de Negocio)
    ↓ hasPermissionTo('permission')
Spatie (Base de Datos)
    ↓ role_has_permissions
✅ Acceso Permitido/Denegado
```

**Próximas tareas sugeridas**:
1. ✅ ~~Crear todas las Policies~~ (COMPLETADO)
2. Implementar testing automatizado de permisos
3. Documentar matriz de permisos por rol
4. Crear seeders para testing de permisos

---

## COMANDO PARA EMPEZAR MAÑANA

```bash
# Iniciar LitoPro 3.0 - SPRINT 18 COMPLETADO (Sistema de Imágenes + UX)
cd /home/dasiva/Descargas/litopro825 && php artisan serve --port=8000

# Estado del Proyecto
echo "✅ SPRINT 18 COMPLETADO (08-Nov-2025) - Sistema de Imágenes + Múltiples Mejoras UX"
echo ""
echo "📍 URLs de Testing:"
echo "   🏠 Dashboard: http://127.0.0.1:8000/admin"
echo "   📦 Productos: http://127.0.0.1:8000/admin/products"
echo "   📋 Cotizaciones: http://127.0.0.1:8000/admin/documents"
echo "   🏭 Órdenes de Producción: http://127.0.0.1:8000/admin/production-orders"
echo "   📄 Órdenes de Pedido: http://127.0.0.1:8000/admin/purchase-orders"
echo "   💰 Cuentas de Cobro: http://127.0.0.1:8000/admin/collection-accounts"
echo ""
echo "⚠️  IMPORTANTE: Usar http://127.0.0.1:8000 (NO localhost) - CORS configurado"
echo ""
echo "📚 DOCUMENTACIÓN:"
echo "   • LITOPRO_SITEMAP.md (145 KB) - Sitemap completo del SaaS"
echo ""
echo "🎉 SPRINT 18 - LOGROS PRINCIPALES:"
echo "   • ✅ Sistema de Imágenes para Productos (1-3 imágenes)"
echo "   • ✅ Item Personalizado en Órdenes de Producción"
echo "   • ✅ Sistema Dual Cliente/Proveedor en 4 recursos"
echo "   • ✅ Protecciones UX en RelationManagers"
echo "   • ✅ Fix CORS (APP_URL → 127.0.0.1:8000)"
echo ""
echo "🎉 SISTEMA DE PERMISOS 100% COMPLETADO (Sprint 16):"
echo "   • 12 de 12 recursos con verificación de 3 capas"
echo "   • Arquitectura: Interfaz → Policy → Spatie"
echo "   • Sprint 16.1: ProductionOrderPolicy + 6 recursos"
echo "   • Sprint 16.2: 4 Policies nuevas (414 líneas)"
echo "   • Policies: Paper, PrintingMachine, Finishing, CollectionAccount"
echo ""
echo "📋 RESUMEN SPRINT 16 COMPLETO:"
echo "   • 5 Policies nuevas creadas (624 líneas)"
echo "   • 12 recursos con canViewAny() actualizado"
echo "   • AuthServiceProvider: 12 policies registradas"
echo "   • Sitemap completo: 145 KB de documentación"
echo ""
echo "🎯 PRÓXIMAS TAREAS:"
echo "   1. Implementar testing automatizado de permisos"
echo "   2. Documentar matriz de permisos por rol"
echo "   3. Crear seeders para testing completo"
```

---

## Notas Técnicas Importantes

### Sistema de Notificaciones Multi-Tenant (Sprint 15)

**4 Tipos de Notificaciones**:

```php
// 1. NOTIFICACIONES SOCIALES (Red Social Interna)
use App\Models\SocialPost;

SocialPost::create([
    'company_id' => auth()->user()->company_id,
    'content' => 'Actualización importante...',
    'visibility' => 'company' // company, department, role
]);
// Genera notificaciones automáticamente en social_notifications

// 2. ALERTAS DE INVENTARIO (Stock Crítico)
use App\Services\StockNotificationService;

$service = app(StockNotificationService::class);
// Verifica automáticamente niveles críticos
// Tabla: stock_alerts (min_stock, current_stock, alert_level)

// 3. SISTEMA AVANZADO (Canales Configurables)
use App\Services\NotificationService;

$notificationService = app(NotificationService::class);
$notificationService->send(
    type: 'order_completed',
    userId: $user->id,
    data: ['order_id' => 123],
    priority: 'high' // low, medium, high, urgent
);
// Canales: email, database, SMS, push, custom
// Tablas: notification_channels, notification_rules, notification_logs

// 4. LARAVEL NOTIFICATIONS (Sistema Base)
$user->notify(new DocumentCreatedNotification($document));
```

**Aislamiento Multi-Tenant**:
- Todos los modelos tienen `company_id` scope global
- Usuario de Empresa A solo ve notificaciones de Empresa A
- Verificación automática en queries

**Documentación Completa**: Ver `NOTIFICATION_SYSTEM_SUMMARY.md` para guía de uso completa.

---

### Sistema de Acabados para SimpleItem (Sprint 14)

```php
use App\Models\SimpleItem;
use App\Models\Finishing;

// 1. AGREGAR ACABADOS A UN SIMPLEITEM
$item = SimpleItem::first();

// Opción A: Parámetros automáticos (usa dimensiones/cantidad del item)
$plastificado = Finishing::where('measurement_unit', 'millar')->first();
$item->addFinishing($plastificado);
// Construye automáticamente: ['quantity' => $item->quantity]

// Opción B: Parámetros manuales
$barnizUV = Finishing::where('measurement_unit', 'tamaño')->first();
$item->addFinishing($barnizUV, ['width' => 20, 'height' => 13], isDefault: true);

// 2. OBTENER DESGLOSE DETALLADO
$breakdown = $item->getFinishingsBreakdown();
// Retorna array con: finishing_id, finishing_name, measurement_unit, params, cost, is_default

// 3. CALCULAR COSTO TOTAL
$item->load('finishings'); // Cargar relación
$totalCost = $item->calculateFinishingsCost();

// 4. VERIFICAR SI TIENE ACABADOS
if ($item->hasFinishings()) {
    // Procesar acabados
}

// 5. PRICING COMPLETO CON ACABADOS
$pricing = $item->calculateAll();
// $pricing->costBreakdown['finishings'] incluye el costo de acabados
```

**Parámetros Auto-construidos por Tipo**:
- `MILLAR/RANGO/UNIDAD` → `['quantity' => $item->quantity]`
- `TAMAÑO` → `['width' => $item->horizontal_size, 'height' => $item->vertical_size]`
- Otros tipos → `[]` (parámetros vacíos)

**Integración con SimpleItemCalculatorService**:
```php
// Método privado que calcula acabados
private function calculateFinishingsCost(SimpleItem $item): float
{
    if (!$item->relationLoaded('finishings') || $item->finishings->isEmpty()) {
        return 0; // Opcional: no afecta si no hay acabados
    }

    $total = 0;
    $finishingCalculator = new FinishingCalculatorService();

    foreach ($item->finishings as $finishing) {
        $params = $this->buildFinishingParams($item, $finishing);
        $cost = $finishingCalculator->calculateCost($finishing, $params);
        $total += $cost;
    }

    return $total;
}
```

---

### Nuevo Sistema de Montaje con Divisor (Sprint 13)

```php
use App\Services\SimpleItemCalculatorService;

$calculator = new SimpleItemCalculatorService();

// PASO 1: Obtener montaje completo con divisor
$mountingWithCuts = $calculator->calculateMountingWithCuts($item);

// Resultado:
// [
//     'mounting' => [...],                  // Info del MountingCalculatorService
//     'copies_per_mounting' => 2,           // Copias en tamaño máquina
//     'divisor' => 4,                       // Cortes de máquina en pliego
//     'divisor_layout' => [
//         'horizontal_cuts' => 2,
//         'vertical_cuts' => 2
//     ],
//     'impressions_needed' => 500,          // 1000 ÷ 2
//     'sheets_needed' => 125,               // 500 ÷ 4
//     'total_impressions' => 500,           // 125 × 4
//     'total_copies_produced' => 1000,      // 500 × 2
//     'waste_copies' => 0,
//     'paper_cost' => 62500.0
// ]

// PASO 2: Calcular millares sobre IMPRESIONES
$printingCalc = $calculator->calculatePrintingMillaresNew($item, $mountingWithCuts);

// Resultado:
// PrintingCalculation {
//     totalColors: 4,
//     millaresRaw: 0.5,                     // 500 ÷ 1000
//     millaresFinal: 4,                     // ceil(0.5) × 4 colores
//     printingCost: 1400.0,
//     setupCost: 15000.0,
//     totalCost: 16400.0
// }

// PASO 3: Pricing completo
$pricingResult = $calculator->calculateFinalPricingNew($item);

// Usar en SimpleItem directamente:
$item = SimpleItem::first();
$details = $item->getMountingWithCuts();
// Retorna el mismo array que calculateMountingWithCuts()
```

### Diferencia: Sistema Anterior vs Nuevo

```php
// ❌ SISTEMA ANTERIOR (sin divisor)
// Trabajo 22×28 en pliego 100×70
// Montaje: 9 copias (3×3) directamente en pliego
// Pliegos: 1000 ÷ 9 = 112 pliegos
// Millares: 112 ÷ 1000 = 0.112 → 1 millar

// ✅ SISTEMA NUEVO (con divisor)
// Trabajo 22×28 en máquina 50×35 → Montaje: 2 copias
// Divisor: 50×35 en pliego 100×70 → 4 cortes
// Impresiones: 1000 ÷ 2 = 500
// Pliegos: 500 ÷ 4 = 125 pliegos
// Impresiones totales: 125 × 4 = 500
// Millares: 500 ÷ 1000 = 0.5 → 1 millar
```

### MountingCalculatorService - Cálculo Puro
```php
use App\Services\MountingCalculatorService;

$calc = new MountingCalculatorService();

// Calcular montaje (3 orientaciones)
$result = $calc->calculateMounting(
    workWidth: 22.0,       // Ancho del trabajo en cm
    workHeight: 28.0,      // Alto del trabajo en cm
    machineWidth: 50.0,    // Ancho máximo máquina en cm
    machineHeight: 35.0,   // Alto máximo máquina en cm
    marginPerSide: 1.0     // Margen por lado en cm
);

// Resultado:
// [
//     'horizontal' => ['copies_per_sheet' => 2, 'layout' => '1 × 2', ...],
//     'vertical' => ['copies_per_sheet' => 2, 'layout' => '2 × 1', ...],
//     'maximum' => ['copies_per_sheet' => 2, ...] // La mejor opción
// ]

// Calcular pliegos necesarios
$sheets = $calc->calculateRequiredSheets(500, 2);
// ['sheets_needed' => 250, 'total_copies_produced' => 500, 'waste_copies' => 0]
```

### Integración con SimpleItem
```php
$item = SimpleItem::first();

// Obtener montaje completo
$mounting = $item->getPureMounting();
// Retorna: ['horizontal', 'vertical', 'maximum', 'sheets_info', 'efficiency']

// Solo la mejor opción
$best = $item->getBestMounting();
// Retorna: ['copies_per_sheet' => 2, 'layout' => '2 × 1', ...]
```

### Calculadora de Cortes - SVG Boundary Validation
```php
// app/Filament/Widgets/CalculadoraCorteWidget.php

// Validación antes de dibujar cada pieza
$pieceEndX = $x + $pieceWidth;
$pieceEndY = $y + $pieceHeight;

if ($pieceEndX <= $svgWidth && $pieceEndY <= $svgHeight) {
    // Dibujar pieza
    $svg .= '<rect x="' . $x . '" y="' . $y . '" ...>';
}
```

### Purchase Orders - Multi-Paper Support
```php
// PurchaseOrderItem (pivot como entity)
// Permite múltiples rows por DocumentItem (revistas con varios papeles)

// Relación en PurchaseOrder:
public function purchaseOrderItems(): HasMany {
    return $this->hasMany(PurchaseOrderItem::class);
}

// Accessor con carga dinámica:
public function getPaperNameAttribute(): string {
    if ($this->paper_description) return $this->paper_description;
    if ($this->paper_id && $this->paper) return $this->paper->name;

    // Carga itemable dinámicamente si no está cargado
    if (!$this->documentItem->relationLoaded('itemable')) {
        $this->documentItem->load('itemable');
    }
}
```

### Filament Pages - Slug Pattern
```php
// ✅ CORRECTO: Slug dinámico con parámetro Panel
public static function getSlug(?\Filament\Panel $panel = null): string {
    return 'empresa/{slug}';
}
```

### Document Relationships
```php
// ✅ CORRECTO: Relación definida como items()
$document->items()->create([...]);

// ❌ INCORRECTO: documentItems() no existe
public function items(): HasMany {
    return $this->hasMany(DocumentItem::class);
}
```
