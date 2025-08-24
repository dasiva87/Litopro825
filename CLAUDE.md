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
- [ ] Implementar validaciones específicas del negocio
- [ ] Crear más seeders con datos realistas
- [ ] Implementar cálculos automáticos en cotizaciones
- [ ] Agregar exportación de documentos PDF

## Documentación Especializada
- Migración Filament v4: Ver `FILAMENT_V4_MIGRATION.md`
- Arquitectura del proyecto: Multi-tenant con scopes automáticos por company_id