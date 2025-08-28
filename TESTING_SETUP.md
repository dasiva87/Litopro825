# 🧪 Configuración de Testing - LitoPro 3.0

Este documento describe cómo configurar el entorno de testing y desarrollo con datos de demostración para LitoPro 3.0.

## 🚀 Setup Rápido

### Comando Todo-en-Uno

```bash
# Configuración completa con datos demo
php artisan litopro:setup-demo

# Instalación limpia (⚠️ elimina todos los datos)
php artisan litopro:setup-demo --fresh
```

## 👥 Usuarios de Prueba Creados

| Rol | Email | Contraseña | Permisos |
|-----|-------|------------|----------|
| **Company Admin** | `admin@litopro.test` | `password` | Acceso completo al sistema |
| **Manager** | `manager@litopro.test` | `password` | Gestión de ventas y reportes |
| **Employee** | `employee@litopro.test` | `password` | Operaciones básicas |

## 🏢 Empresa de Demostración

**Datos de la empresa creada:**
- **Nombre**: LitoPro Demo
- **Plan**: Premium (válido por 1 año)
- **Email**: info@litopro-demo.com  
- **Teléfono**: +57 300 123 4567
- **Límite usuarios**: 50

## 📊 Datos Base Incluidos

### 🎭 Sistema de Roles y Permisos
- ✅ **Super Admin**: Acceso total al sistema
- ✅ **Company Admin**: Administración completa de empresa
- ✅ **Manager**: Gestión de ventas y reportes  
- ✅ **Employee**: Operaciones básicas
- ✅ **Client**: Solo visualización de cotizaciones

**Permisos implementados:**
- Gestión de usuarios, documentos, productos
- Administración de contactos e inventario
- Configuración de empresa y catálogos
- Acceso a reportes y panel admin

### 📋 Tipos de Documento
- ✅ **Cotización** (QUOTE)
- ✅ **Orden de Producción** (ORDER)
- ✅ **Factura** (INVOICE)
- ✅ **Nota de Crédito** (CREDIT)

### 👥 Contactos de Prueba
- **Clientes**: Grupo Empresarial ABC, Fundación Educativa XYZ
- **Proveedores**: Distribuidora de Papel Colombia

### 📰 Catálogo de Papeles
| Código | Nombre | Peso | Dimensiones | Stock |
|--------|---------|------|-------------|--------|
| BOND-75 | Bond | 75g | 70x100cm | 500 |
| PROP-115 | Propalcote | 115g | 70x100cm | 300 |
| CART-250 | Cartulina | 250g | 70x100cm | 200 |
| OPAL-180 | Opalina | 180g | 70x100cm | 150 |

### 🖨️ Máquinas de Impresión
1. **Heidelberg Speedmaster SM 52-4** (Offset, 4 colores)
2. **Xerox Versant 180** (Digital, 4 colores)
3. **Komori Lithrone G40** (Offset, 8 colores)

### 📦 Productos de Inventario
- Tarjetas de Presentación Premium
- Folletos Publicitarios A4
- Volantes Medio Pliego
- Carpetas Corporativas

### 🧾 Cotización de Demostración
**Documento**: `COT-2025-DEMO-001`
- **Cliente**: Grupo Empresarial ABC
- **Items**: 4 (SimpleItems y Products mixtos)
- **Total**: ~$740,000 COP (incluye IVA)
- **Estado**: Borrador

**Items incluidos:**
1. **SimpleItem**: Tarjetas ejecutivas (1000 uds) - Con cálculos automáticos
2. **SimpleItem**: Folletos A4 (2500 uds) - Con diseño y transporte
3. **Product**: Tarjetas Premium (cantidad variable)
4. **Product**: Folletos A4 (cantidad variable)

## 🛠️ Comandos de Testing

### Seeders Individuales
```bash
# Solo crear roles y datos base
php artisan db:seed --class=TestDataSeeder

# Solo crear cotización demo
php artisan db:seed --class=DemoQuotationSeeder
```

### Testing Suite
```bash
# Ejecutar toda la suite de tests
php artisan test

# Solo tests unitarios
php artisan test tests/Unit/

# Solo tests de funcionalidad
php artisan test tests/Feature/

# Test específico
php artisan test --filter="CuttingCalculatorServiceTest"
```

## 📱 Acceso al Sistema

**URL**: `/admin`

**Funcionalidades disponibles para testing:**

### 🎯 Como Admin (`admin@litopro.test`)
- ✅ Crear/editar usuarios y roles
- ✅ Gestionar empresa y configuración  
- ✅ CRUD completo de contactos
- ✅ Administrar catálogo de papeles
- ✅ Configurar máquinas de impresión
- ✅ Gestión de inventario/productos
- ✅ Crear y gestionar cotizaciones
- ✅ Agregar SimpleItems con cálculos automáticos
- ✅ Agregar Products de inventario
- ✅ Ver reportes y estadísticas

### 📊 Como Manager (`manager@litopro.test`)
- ✅ Gestionar cotizaciones y clientes
- ✅ Ver y crear productos
- ✅ Acceder a reportes de ventas
- ✅ Aprobar documentos

### 👤 Como Employee (`employee@litopro.test`)  
- ✅ Crear cotizaciones básicas
- ✅ Gestionar contactos
- ✅ Ver productos disponibles
- ✅ Operaciones de día a día

## 🔍 Testing de Funcionalidades

### Calculadora de Cortes
- **Servicio**: `CuttingCalculatorService`
- **Test**: `tests/Unit/CuttingCalculatorServiceTest.php`
- **Orientaciones**: Horizontal, Vertical, Máxima
- **Validaciones**: Límites de papel, optimización automática

### Calculadora SimpleItems
- **Servicio**: `SimpleItemCalculatorService`  
- **Test**: `tests/Unit/SimpleItemCalculatorServiceTest.php`
- **Features**: Cálculo de millares, costos, precios finales
- **Validaciones**: Viabilidad técnica, stock de papel

### Workflow de Cotizaciones
- **Test**: `tests/Feature/QuotationWorkflowTest.php`
- **Flujos**: Creación, cálculos, estados, multi-tenant
- **Validaciones**: Totales, impuestos, items polimórficos

## 🚨 Notas Importantes

### ⚠️ Para Desarrollo
- Los datos son **solo para testing** - no usar en producción
- La contraseña `password` es **insegura** para prod
- El seeder `--fresh` **elimina todos los datos**

### 🔐 Seguridad  
- Todos los usuarios tienen contraseña simple para testing
- Los permisos están configurados pero pueden ajustarse
- Multi-tenancy está implementado por `company_id`

### 🐛 Troubleshooting
```bash
# Si hay problemas con migraciones
php artisan migrate:status
php artisan migrate

# Si hay problemas con permisos
php artisan permission:cache-reset

# Limpiar todo
php artisan optimize:clear
```

## 📈 Próximos Pasos de Desarrollo

1. **Completar items faltantes**: TalonarioItem, MagazineItem, etc.
2. **Mejorar reportes**: Dashboard con gráficos
3. **Workflow de producción**: Estados avanzados
4. **Integración financiera**: Facturación automática  
5. **Optimizaciones**: Cache, queues, performance

---

**¡El sistema está listo para testing! 🎉**

Para cualquier duda consulta la documentación en `CLAUDE.md` o ejecuta `php artisan litopro:setup-demo --fresh` para resetear.