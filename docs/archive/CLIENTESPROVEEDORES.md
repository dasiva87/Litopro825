# Informe de Arquitectura: Sistema de Clientes y Proveedores en GrafiRed 3.0

## 📋 Índice
1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Modelos Principales](#modelos-principales)
3. [Arquitectura de Relaciones](#arquitectura-de-relaciones)
4. [Flujos de Negocio](#flujos-de-negocio)
5. [Relación con Documentos](#relación-con-documentos)
6. [Diagramas](#diagramas)
7. [Casos de Uso](#casos-de-uso)

---

## 1. Resumen Ejecutivo

El sistema de Clientes y Proveedores en GrafiRed utiliza **múltiples modelos interconectados** para manejar dos tipos de relaciones comerciales:

### Dos Enfoques Coexistentes:

1. **Contactos Locales** (Sistema tradicional)
   - Para empresas externas no registradas en Grafired
   - Datos manuales sin sincronización

2. **Red Grafired** (Sistema de red social empresarial)
   - Empresas registradas en la plataforma
   - Sincronización automática de datos
   - Sistema de solicitudes y aprobaciones

---

## 2. Modelos Principales

### 2.1. Company (Empresa Registrada)
**Tabla**: `companies`
**Propósito**: Empresas registradas en la plataforma Grafired

```php
class Company extends Model
{
    // Campos clave
    'name'              // Nombre de la empresa
    'tax_id'            // NIT/RUT
    'email'             // Email corporativo
    'company_type'      // ENUM: litografia, distribuidora, proveedor_insumos, papeleria, agencia
    'is_public'         // Visible en búsqueda Grafired
    'is_active'         // Empresa activa

    // Relaciones
    users()             // Usuarios de la empresa
    contacts()          // Contactos (clientes/proveedores) de esta empresa
    documents()         // Documentos generados
    productionOrders()  // Órdenes de producción
}
```

**Características**:
- Una empresa puede tener múltiples usuarios
- Puede aparecer en búsquedas de Grafired si `is_public = true`
- Es la entidad principal en el sistema multi-tenant

---

### 2.2. Contact (Cliente o Proveedor)
**Tabla**: `contacts`
**Propósito**: Representa clientes y proveedores (locales o de Grafired)

```php
class Contact extends Model
{
    use BelongsToTenant; // Filtrado automático por company_id

    // Campos de identificación
    'company_id'           // Empresa dueña de este contacto
    'type'                 // ENUM: 'customer', 'supplier', 'both'
    'name'                 // Nombre del contacto
    'tax_id'               // NIT/RUT

    // Campos Grafired
    'is_local'             // true = Local, false = Grafired
    'linked_company_id'    // ID de Company si es Grafired (NULL si es local)

    // Campos comerciales
    'credit_limit'         // Límite de crédito (solo clientes)
    'payment_terms'        // Días de plazo de pago
    'discount_percentage'  // Descuento por volumen

    // Relaciones
    company()              // Empresa dueña
    linkedCompany()        // Empresa vinculada (Grafired)
    documents()            // Cotizaciones del cliente
}
```

#### Tipos de Contact:

| type | Descripción | Uso |
|------|-------------|-----|
| `customer` | Solo cliente | Puede recibir cotizaciones |
| `supplier` | Solo proveedor | Puede recibir órdenes de producción |
| `both` | Cliente y proveedor | Ambas funcionalidades |

#### Diferencia Local vs Grafired:

| Campo | Local | Grafired |
|-------|-------|----------|
| `is_local` | `true` | `false` |
| `linked_company_id` | `NULL` | ID de Company |
| Sincronización | Manual | Automática |
| Datos | Editables | Solo lectura (sync desde Company) |

---

### 2.3. CommercialRequest (Solicitud Comercial)
**Tabla**: `commercial_requests`
**Propósito**: Solicitudes para establecer relaciones comerciales en Grafired

```php
class CommercialRequest extends Model
{
    // NO usa BelongsToTenant (relación entre dos empresas)

    'requester_company_id'   // Empresa que solicita
    'target_company_id'      // Empresa objetivo
    'relationship_type'      // ENUM: 'client', 'supplier'
    'status'                 // ENUM: 'pending', 'approved', 'rejected'
    'message'                // Mensaje de solicitud
    'response_message'       // Mensaje de respuesta
    'requested_by_user_id'   // Usuario solicitante
    'responded_by_user_id'   // Usuario que respondió
    'responded_at'           // Fecha de respuesta

    // Relaciones
    requesterCompany()       // Empresa solicitante
    targetCompany()          // Empresa objetivo
    requestedByUser()        // Usuario solicitante
    respondedByUser()        // Usuario que aprobó/rechazó
}
```

#### Estados del Workflow:

```
pending → approved  (Crea Contacts bidireccionales)
        ↓
        rejected (No crea contactos)
```

---

### 2.4. ClientRelationship (Relación Cliente-Proveedor)
**Tabla**: `client_relationships`
**Propósito**: Registro de relaciones aprobadas entre empresas (tipo cliente)

```php
class ClientRelationship extends Model
{
    // NO usa BelongsToTenant

    'supplier_company_id'  // Empresa proveedora
    'client_company_id'    // Empresa cliente
    'approved_by_user_id'  // Usuario que aprobó
    'approved_at'          // Fecha de aprobación
    'is_active'            // Relación activa
    'notes'                // Notas adicionales

    // Métodos de negocio
    createLocalContact()   // Crea Contact en empresa proveedora
    syncLinkedContact()    // Sincroniza datos del Contact
}
```

---

### 2.5. SupplierRelationship (Relación Proveedor-Cliente)
**Tabla**: `supplier_relationships`
**Propósito**: Registro de relaciones aprobadas entre empresas (tipo proveedor)

```php
class SupplierRelationship extends Model
{
    // NO usa BelongsToTenant

    'client_company_id'     // Empresa cliente
    'supplier_company_id'   // Empresa proveedora
    'approved_by_user_id'   // Usuario que aprobó
    'approved_at'           // Fecha de aprobación
    'is_active'             // Relación activa
    'notes'                 // Notas adicionales
}
```

---

## 3. Arquitectura de Relaciones

### 3.1. Diagrama de Entidad-Relación

```
┌─────────────────┐
│    Company A    │ (Litografía)
│  (Empresa A)    │
└────────┬────────┘
         │
         │ has many
         ▼
┌─────────────────────────────────────────────────────┐
│              Contacts (de Empresa A)                 │
├──────────────────────┬──────────────────────────────┤
│  LOCALES             │  GRAFIRED                     │
│  is_local = true     │  is_local = false             │
│  linked_company = ⌀  │  linked_company_id = Company B│
└──────────────────────┴──────────────────────────────┘
         │                           │
         │                           └──────────┐
         │                                      │
         │                                      ▼
         │                           ┌─────────────────┐
         │                           │   Company B     │
         │                           │ (Otra empresa   │
         │                           │  en Grafired)   │
         │                           └─────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────┐
│             Documentos / Órdenes                     │
│  - Document (Cotizaciones)                           │
│  - PurchaseOrder (Órdenes de Pedido)                │
│  - ProductionOrder (Órdenes de Producción)          │
│  - CollectionAccount (Cuentas de Cobro)            │
└─────────────────────────────────────────────────────┘
```

### 3.2. Relación Multi-Tenant

```
Company A
├── Contact 1 (type: customer, is_local: true)  ← Cliente local
├── Contact 2 (type: supplier, is_local: true)  ← Proveedor local
├── Contact 3 (type: customer, is_local: false, linked_company_id: Company B) ← Cliente Grafired
└── Contact 4 (type: supplier, is_local: false, linked_company_id: Company C) ← Proveedor Grafired
```

**Características del Multi-Tenant**:
- Cada `Contact` pertenece a una sola `Company` (`company_id`)
- El trait `BelongsToTenant` filtra automáticamente por `company_id`
- Usuarios solo ven contactos de su propia empresa

---

## 4. Flujos de Negocio

### 4.1. Flujo: Agregar Cliente Local

```
Usuario en Empresa A
    ↓
Click "Nuevo Cliente Local"
    ↓
Formulario manual
    ↓
Contact creado:
    - company_id = A
    - type = 'customer'
    - is_local = true
    - linked_company_id = NULL
    - Datos manuales
```

### 4.2. Flujo: Buscar y Solicitar Proveedor en Grafired

```
Usuario en Empresa A
    ↓
Click "Buscar en Grafired"
    ↓
Modal de búsqueda (GrafiredSupplierSearch)
    ↓
Selecciona Empresa B
    ↓
Click "Solicitar como Proveedor"
    ↓
CommercialRequest creado:
    - requester_company_id = A
    - target_company_id = B
    - relationship_type = 'supplier'
    - status = 'pending'
    ↓
Notificación enviada a Empresa B
    ↓
Usuario de Empresa B abre solicitud
    ↓
┌──────────────────────┬──────────────────────┐
│   APROBAR            │   RECHAZAR           │
└──────────────────────┴──────────────────────┘
         │                           │
         ▼                           ▼
Status = 'approved'         Status = 'rejected'
         │                           │
         ▼                           └─> FIN (No crea contactos)
SupplierRelationship creado
    - client_company_id = A
    - supplier_company_id = B
         │
         ▼
Se crean 2 Contacts:
┌─────────────────────────────────────────────────┐
│ Contact en Empresa A:                            │
│   - company_id = A                               │
│   - type = 'supplier'                            │
│   - linked_company_id = B                        │
│   - is_local = false                             │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│ Contact en Empresa B:                            │
│   - company_id = B                               │
│   - type = 'customer'                            │
│   - linked_company_id = A                        │
│   - is_local = false                             │
└─────────────────────────────────────────────────┘
```

### 4.3. Flujo: Buscar y Solicitar Cliente en Grafired

```
Usuario en Empresa A
    ↓
Click "Buscar en Grafired" (en vista Clientes)
    ↓
Selecciona Empresa C
    ↓
Click "Solicitar como Cliente"
    ↓
CommercialRequest creado:
    - requester_company_id = A
    - target_company_id = C
    - relationship_type = 'client'
    - status = 'pending'
    ↓
Empresa C aprueba
    ↓
ClientRelationship creado:
    - supplier_company_id = C
    - client_company_id = A
    ↓
Se crean 2 Contacts:
┌─────────────────────────────────────────────────┐
│ Contact en Empresa A:                            │
│   - company_id = A                               │
│   - type = 'customer'                            │
│   - linked_company_id = C                        │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│ Contact en Empresa C:                            │
│   - company_id = C                               │
│   - type = 'supplier'                            │
│   - linked_company_id = A                        │
└─────────────────────────────────────────────────┘
```

---

## 5. Relación con Documentos

### 5.1. Document (Cotizaciones)

```php
class Document extends Model
{
    'company_id'      // Empresa que genera la cotización
    'contact_id'      // Cliente (Contact) que recibe la cotización
    'document_type'   // quotation, invoice, etc.

    contact()         // BelongsTo Contact
}
```

**Uso**:
```php
// Cotización para cliente local
Document::create([
    'company_id' => 1,              // Mi empresa
    'contact_id' => 5,              // Cliente local
    'document_type' => 'quotation',
]);

// Cotización para cliente Grafired
Document::create([
    'company_id' => 1,              // Mi empresa
    'contact_id' => 8,              // Cliente Grafired (linked_company_id = 3)
    'document_type' => 'quotation',
]);

// Ambos funcionan igual - Contact abstrae el tipo
```

---

### 5.2. ProductionOrder (Órdenes de Producción)

```php
class ProductionOrder extends Model
{
    'company_id'            // Empresa que genera la orden
    'supplier_id'           // Proveedor (Contact) que ejecuta
    'supplier_company_id'   // Company si es Grafired (redundante)
    'status'                // pending, in_progress, completed

    supplier()              // BelongsTo Contact
    supplierCompany()       // BelongsTo Company (opcional)
}
```

**Uso**:
```php
// Orden para proveedor local
ProductionOrder::create([
    'company_id' => 1,
    'supplier_id' => 10,           // Proveedor local
    'supplier_company_id' => null,
]);

// Orden para proveedor Grafired
ProductionOrder::create([
    'company_id' => 1,
    'supplier_id' => 12,           // Contact Grafired
    'supplier_company_id' => 5,    // Company vinculada
]);
```

---

### 5.3. PurchaseOrder (Órdenes de Pedido)

**Similar a ProductionOrder pero para compras de insumos**

```php
class PurchaseOrder extends Model
{
    'company_id'      // Empresa compradora
    'supplier_id'     // Proveedor (Contact)

    supplier()        // BelongsTo Contact
}
```

---

### 5.4. CollectionAccount (Cuentas de Cobro)

```php
class CollectionAccount extends Model
{
    'company_id'      // Empresa que cobra
    'contact_id'      // Cliente que paga

    contact()         // BelongsTo Contact
}
```

---

## 6. Diagramas

### 6.1. Diagrama de Clases Simplificado

```
┌─────────────────────┐
│      Company        │
├─────────────────────┤
│ + id                │
│ + name              │
│ + tax_id            │
│ + is_public         │
│ + company_type      │
└──────────┬──────────┘
           │ 1
           │
           │ *
┌──────────▼──────────────────┐
│       Contact               │
├─────────────────────────────┤
│ + id                        │
│ + company_id (FK)           │
│ + type (customer/supplier)  │
│ + is_local (bool)           │
│ + linked_company_id (FK?)   │
│ + name                      │
│ + tax_id                    │
│ + credit_limit              │
└──────────┬──────────────────┘
           │ *
           │
           │ 1
┌──────────▼──────────────┐
│      Document           │
├─────────────────────────┤
│ + id                    │
│ + company_id (FK)       │
│ + contact_id (FK)       │
│ + document_type         │
│ + total                 │
└─────────────────────────┘

┌──────────▼──────────────┐
│   ProductionOrder       │
├─────────────────────────┤
│ + id                    │
│ + company_id (FK)       │
│ + supplier_id (FK)      │
│ + status                │
└─────────────────────────┘
```

### 6.2. Diagrama de Flujo: Sistema de Solicitudes

```
┌──────────────────────────────────────────────────────────┐
│                   GRAFIRED - RED SOCIAL                  │
└──────────────────────────────────────────────────────────┘
                          │
        ┌─────────────────┼─────────────────┐
        ▼                 ▼                 ▼
   Empresa A         Empresa B         Empresa C
   (Litografía)      (Papelería)       (Distribuidor)
        │
        └─> Busca proveedores en Grafired
                │
                └─> Encuentra Empresa B
                        │
                        └─> Envía CommercialRequest
                                (relationship_type: 'supplier')
                                │
                                ▼
                        Empresa B recibe notificación
                                │
                    ┌───────────┴───────────┐
                    ▼                       ▼
                APRUEBA                  RECHAZA
                    │                       │
                    ▼                       └─> FIN
        SupplierRelationship creado
                    │
        ┌───────────┴───────────┐
        ▼                       ▼
  Contact en A:            Contact en B:
  type='supplier'          type='customer'
  linked → B               linked → A
```

---

## 7. Casos de Uso

### Caso de Uso 1: Cotización a Cliente Local

**Actores**: Usuario de Empresa A, Cliente Local "XYZ S.A."

**Flujo**:
1. Usuario crea Contact local:
   ```php
   Contact::create([
       'company_id' => 1,        // Empresa A
       'type' => 'customer',
       'name' => 'XYZ S.A.',
       'tax_id' => '900123456',
       'is_local' => true,
       'email' => 'ventas@xyz.com',
   ]);
   ```

2. Usuario crea cotización:
   ```php
   Document::create([
       'company_id' => 1,
       'contact_id' => 15,       // Contact de XYZ
       'document_type' => 'quotation',
       'total' => 5000000,
   ]);
   ```

3. Sistema genera PDF con datos de XYZ

---

### Caso de Uso 2: Orden de Producción a Proveedor Grafired

**Actores**: Empresa A (Papelería), Empresa B (Litografía en Grafired)

**Flujo**:
1. Empresa A busca en Grafired → Encuentra Empresa B
2. Envía solicitud como proveedor
3. Empresa B aprueba
4. Sistema crea Contact automáticamente:
   ```php
   // En Empresa A
   Contact {
       company_id: 1,
       type: 'supplier',
       is_local: false,
       linked_company_id: 2,    // Empresa B
       name: 'Litografía B',    // Sincronizado
       email: 'info@litob.com', // Sincronizado
   }
   ```

5. Usuario A crea orden de producción:
   ```php
   ProductionOrder::create([
       'company_id' => 1,
       'supplier_id' => 20,          // Contact Grafired
       'supplier_company_id' => 2,   // Empresa B
       'status' => 'pending',
   ]);
   ```

6. Empresa B recibe notificación automática

---

### Caso de Uso 3: Sincronización de Datos Grafired

**Escenario**: Empresa B cambia su dirección en su perfil

**Flujo**:
1. Usuario de Empresa B edita Company:
   ```php
   Company::find(2)->update([
       'address' => 'Nueva dirección 123',
       'phone' => '+57 300 999 8888',
   ]);
   ```

2. Empresa A sincroniza el Contact:
   ```php
   $contact = Contact::where('linked_company_id', 2)->first();
   $contact->syncFromLinkedCompany();
   // Actualiza automáticamente address, phone, etc.
   ```

3. Próxima orden usa datos actualizados

---

## 8. Tablas de Referencia

### 8.1. Campos ENUM

| Tabla | Campo | Valores Permitidos |
|-------|-------|-------------------|
| `contacts` | `type` | `customer`, `supplier`, `both` |
| `companies` | `company_type` | `litografia`, `distribuidora`, `proveedor_insumos`, `papeleria`, `agencia` |
| `commercial_requests` | `relationship_type` | `client`, `supplier` |
| `commercial_requests` | `status` | `pending`, `approved`, `rejected` |

### 8.2. Mapeo de Tipos

| CommercialRequest.relationship_type | Contact.type (Solicitante) | Contact.type (Objetivo) |
|------------------------------------|----------------------------|-------------------------|
| `supplier` | `supplier` | `customer` |
| `client` | `customer` | `supplier` |

---

## 9. Conclusiones

### Ventajas del Sistema Actual:

✅ **Flexibilidad**: Soporta contactos locales y red Grafired
✅ **Trazabilidad**: CommercialRequests registran todo el historial
✅ **Sincronización**: Datos de Company → Contact automática
✅ **Multi-tenant**: Aislamiento perfecto entre empresas
✅ **Relaciones bidireccionales**: Ambas empresas quedan conectadas

### Complejidad:

⚠️ **Múltiples modelos**: Contact, Company, CommercialRequest, ClientRelationship, SupplierRelationship
⚠️ **Conversión de tipos**: `client` ↔ `customer`, `supplier` ↔ `supplier`
⚠️ **Redundancia**: `supplier_id` y `supplier_company_id` en ProductionOrder

### Recomendaciones:

1. **Mantener CommercialRequestService centralizado** para toda la lógica de aprobación
2. **Usar scopes de Contact** (`customers()`, `suppliers()`, `local()`, `grafired()`) para filtrado
3. **Sincronizar periódicamente** contactos Grafired con `syncFromLinkedCompany()`
4. **Validar duplicados** antes de crear CommercialRequests

---

## 10. Referencias de Código

### Archivos Clave:

```
app/
├── Models/
│   ├── Company.php                    # Empresas registradas
│   ├── Contact.php                    # Clientes/Proveedores
│   ├── CommercialRequest.php          # Solicitudes comerciales
│   ├── ClientRelationship.php         # Relaciones cliente aprobadas
│   ├── SupplierRelationship.php       # Relaciones proveedor aprobadas
│   ├── Document.php                   # Cotizaciones
│   ├── ProductionOrder.php            # Órdenes de producción
│   └── CollectionAccount.php          # Cuentas de cobro
├── Services/
│   └── CommercialRequestService.php   # Lógica de solicitudes
├── Livewire/
│   ├── GrafiredSupplierSearch.php     # Búsqueda de proveedores
│   └── GrafiredClientSearch.php       # Búsqueda de clientes
└── Filament/
    ├── Pages/
    │   ├── Suppliers/ListSuppliers.php
    │   └── Clients/ListClients.php
    └── Resources/
        ├── ContactResource.php
        └── CommercialRequestResource.php
```

---

**Fecha de Análisis**: 5 de Diciembre de 2025
**Versión de GrafiRed**: 3.0
**Autor**: Claude (Anthropic) con análisis de codebase
