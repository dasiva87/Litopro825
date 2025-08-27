# Configuración Claude Code - LitoPro

## Proyecto
- **Nombre**: LitoPro 3.0 - SaaS para empresas de litografía
- **Stack**: Laravel 10 + Filament 4 + MySQL
- **Arquitectura**: Multi-tenant por company_id

## Comandos Importantes
```bash
# Testing
php artisan test

# Linting y formato
php artisan pint
composer analyse

# Migraciones
php artisan migrate
php artisan db:seed
```

## Convenciones del Proyecto

### Filament v4 Namespaces
- Layout Components: `Filament\Schemas\Components\*`
- Form Components: `Filament\Forms\Components\*`  
- Table Actions: `Filament\Actions\*`
- Table Columns: `Filament\Tables\Columns\*`

### Estructura de Resources
- Resource principal en `app/Filament/Resources/[Entity]/[Entity]Resource.php`
- Formularios en `app/Filament/Resources/[Entity]/Schemas/[Entity]Form.php`
- Tablas en `app/Filament/Resources/[Entity]/Tables/[Entity]sTable.php`
- Páginas en `app/Filament/Resources/[Entity]/Pages/`

### Models
- User: Incluye company_id, roles con Spatie Permission
- Company: Multi-tenant principal
- Document: Cotizaciones y documentos
- Contact: Clientes y proveedores
- Paper: Tipos de papel para cotizaciones
- PrintingMachine: Máquinas de impresión

## Historial de Desarrollo

### Sesión: Migración Completa Filament v3 → v4 (Agosto 2024)

**Contexto**: Proyecto LitoPro 3.0 con errores de compatibilidad Filament v4

**Problemas Resueltos:**

1. **NavigationGroup Type Error**
   ```php
   // ❌ Error: Type must be UnitEnum|string|null
   // ✅ Solución: Crear UnitEnum en app/Enums/NavigationGroup.php
   enum NavigationGroup: implements UnitEnum {
       case Cotizaciones;
       case Configuracion;
       // ...
   }
   ```

2. **Form API → Schema API Migration**
   ```php
   // ❌ Filament v3: Form $form
   // ✅ Filament v4: Schema $schema con ->components([])
   ```

3. **Actions Namespace Changes**
   ```php
   // ❌ v3: use Filament\Tables\Actions\*
   // ✅ v4: use Filament\Actions\*
   ```

4. **Components Namespace Restructure**
   - Layouts: `Filament\Schemas\Components\*` (Section, Grid, Tab)
   - Fields: `Filament\Forms\Components\*` (Select, TextInput, etc.)

5. **BadgeColumn → TextColumn Migration**
   ```php
   // ❌ v3: BadgeColumn::make()->colors([])
   // ✅ v4: TextColumn::make()->badge()->color()
   ```

**Archivos Migrados Exitosamente:**
- ✅ ContactResource + ContactForm + ContactsTable
- ✅ DocumentResource + DocumentForm + DocumentsTable  
- ✅ PaperResource + PaperForm + PapersTable
- ✅ PrintingMachineResource + PrintingMachineForm + PrintingMachinesTable
- ✅ UserResource (ya estaba correcto)
- ✅ CreateQuotation (convertido de Page a CreateRecord)
- ✅ ListDocuments (Tab import corregido)

**Patrón CreateRecord Implementado:**
```php
// Patrón correcto para páginas de creación
class CreateQuotation extends CreateRecord {
    protected static string $resource = DocumentResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['company_id'] = auth()->user()->company_id;
        $data['user_id'] = auth()->id();
        return $data;
    }
    
    protected function afterCreate(): void {
        // Lógica post-creación
    }
}
```

**Estado Final:**
- ✅ Todos los recursos migrados a Filament v4
- ✅ Navigation funcionando con UnitEnum
- ✅ Formularios usando Schema API
- ✅ Actions con namespaces correctos
- ✅ CreateQuotation siguiendo patrón CreateRecord
- ✅ Clientes demo creados en todas las empresas

**Comandos Ejecutados:**
```bash
# Creación de clientes demo
php artisan tinker --execute="
foreach (App\Models\Company::all() as \$company) {
    App\Models\Contact::create([...]);
}
"
```

### Lecciones Aprendidas

1. **Filament v4 Structure**: Separación clara entre Layout Components (Schemas) y Field Components (Forms)
2. **Resource Pattern**: Delegación a clases especializadas (Form/Table) es obligatoria
3. **CreateRecord Pattern**: Hooks son más poderosos que métodos personalizados
4. **Multi-tenant**: Scopes automáticos funcionan correctamente con company_id

### Próximos Pasos Sugeridos
- [x] Implementar cálculos automáticos en cotizaciones ✅
- [x] Crear arquitectura polimórfica para DocumentItems ✅
- [ ] Implementar tipos de items adicionales (Talonario, Revista, Digital)
- [ ] Crear más seeders con datos realistas
- [ ] Implementar validaciones específicas del negocio
- [ ] Agregar exportación de documentos PDF mejorada

---

### Sesión: Implementación SimpleItems + Integración Cotizaciones (Agosto 2024)

**Contexto**: Implementación completa del sistema polimórfico de items para cotizaciones, comenzando con SimpleItem como primer tipo de item.

**Arquitectura Implementada:**

1. **Sistema Polimórfico DocumentItems**
   ```php
   // DocumentItem apunta polimórficamente a diferentes tipos de items
   class DocumentItem {
       public function itemable(): MorphTo {
           return $this->morphTo();
       }
   }
   
   // SimpleItem como primer tipo de item implementado
   class SimpleItem {
       // Cálculos automáticos usando CuttingCalculatorService
       public function calculateAll(): void { ... }
   }
   ```

2. **SimpleItem - Campos y Cálculos**
   - **Campos básicos**: description, quantity, horizontal_size, vertical_size
   - **Relaciones**: paper_id, printing_machine_id  
   - **Tintas**: ink_front_count, ink_back_count, front_back_plate
   - **Costos adicionales**: design_value, transport_value, rifle_value
   - **Cálculo automático**: profit_percentage → final_price
   - **Integración**: CuttingCalculatorService para optimización de cortes

3. **DocumentItemsRelationManager Completo**
   - **Wizard de creación**: Tipo de item → Detalles específicos
   - **"Item Sencillo Rápido"**: Creación directa optimizada
   - **Gestión completa**: Crear, editar, eliminar items
   - **Recálculo automático**: Totales del documento se actualizan

**Problemas Críticos Resueltos:**

1. **Namespaces Filament v4 RelationManagers**
   ```php
   // ❌ Incorrecto
   use Filament\Tables\Actions\CreateAction;
   use Filament\Forms\Components\Wizard;
   
   // ✅ Correcto
   use Filament\Actions\CreateAction;          // Para RelationManagers
   use Filament\Actions\BulkActionGroup;       // Para acciones en lote
   use Filament\Schemas\Components\Wizard;     // Para componentes de layout
   ```

2. **DocumentItem Creation - Campos Requeridos**
   ```php
   // ❌ Fallaba: Campos incompletos
   $data = ['itemable_type' => ..., 'itemable_id' => ...];
   
   // ✅ Correcto: Todos los campos requeridos
   $data = [
       'itemable_type' => 'App\\Models\\SimpleItem',
       'itemable_id' => $simpleItem->id,
       'description' => 'SimpleItem: ' . $simpleItem->description,
       'quantity' => $simpleItem->quantity,
       'unit_price' => $simpleItem->final_price / $simpleItem->quantity,
       'total_price' => $simpleItem->final_price
   ];
   ```

3. **Icons Heroicons v2**
   ```php
   // ❌ No existe
   ->icon('heroicon-o-lightning-bolt')
   
   // ✅ Correcto
   ->icon('heroicon-o-bolt')
   ```

**Archivos Clave Creados/Modificados:**

- ✅ `database/migrations/..._create_simple_items_table.php` - Tabla SimpleItems
- ✅ `app/Models/SimpleItem.php` - Modelo con cálculos automáticos
- ✅ `app/Models/DocumentItem.php` - Actualizado para polimorfismo
- ✅ `app/Filament/Resources/SimpleItems/Schemas/SimpleItemForm.php` - Formulario completo
- ✅ `app/Filament/Resources/Documents/RelationManagers/DocumentItemsRelationManager.php` - Gestor completo
- ✅ `app/Models/Document.php` - Método `recalculateTotals()` actualizado

**Datos de Prueba Creados:**

```bash
# Cotización funcional con 4 SimpleItems
COT-2025-004 - Total: $705,670 (incluye IVA 19%)
- Tarjetas de presentación ejecutivas: $162,000
- Folletos promocionales formato carta: $245,000  
- Test item from relation manager: $78,000
- Volantes publicitarios A5: $108,000
```

**Funcionalidades Operativas:**

1. **Creación de Cotizaciones** ✅
   - DocumentResource funcionando completamente
   - Estados: draft → sent → approved → in_production → completed
   - Numeración automática (COT-2025-XXX)

2. **Gestión de SimpleItems** ✅
   - Formulario completo con 6 secciones organizadas
   - Cálculos automáticos de costos y precio final
   - Integración con papers y printing machines

3. **RelationManager Avanzado** ✅
   - **"Agregar Item"**: Wizard paso a paso con tipos de item
   - **"Item Sencillo Rápido"**: Modal optimizado para SimpleItems
   - **Editar items**: Solo disponible para SimpleItems implementados
   - **Eliminar**: Individual y en lote con limpieza de items relacionados
   - **Recálculo automático**: Totales del documento actualizados en tiempo real

4. **Vista de Cotizaciones** ✅
   - Tabla completa con información de items polimórficos
   - Columnas: Tipo, Descripción, Cantidad, Dimensiones, Precio
   - Filtros por tipo de item
   - Acciones contextuales según el tipo

**Estado Actual del Sistema:**

- ✅ **SimpleItem**: Completamente funcional con cálculos automáticos
- 🔄 **TalonarioItem**: Pendiente de implementación
- 🔄 **MagazineItem**: Pendiente de implementación  
- 🔄 **DigitalItem**: Pendiente de implementación
- 🔄 **CustomItem**: Pendiente de implementación
- 🔄 **ProductItem**: Pendiente de implementación

**Integración CuttingCalculatorService:**
- SimpleItems usan el servicio existente para cálculos de cortes optimizados
- Automáticamente calcula: paper_cuts_h, paper_cuts_v, mounting_quantity
- Costos de papel, impresión y montaje calculados automáticamente

**Próximos Pasos Identificados:**
1. Implementar TalonarioItem con campos específicos (numeración, copias, papel carbón)
2. Implementar MagazineItem con encuadernación y páginas múltiplos de 4  
3. Implementar DigitalItem para impresión gran formato
4. Crear sistema de templates para items frecuentes
5. Mejorar validaciones de negocio según el tipo de item

## Documentación Especializada
- Migración Filament v4: Ver `FILAMENT_V4_MIGRATION.md`
- Arquitectura del proyecto: Multi-tenant con scopes automáticos por company_id