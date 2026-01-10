# 📚 Sistema de Documentación del Inventario de GrafiRed 3.0

## 📁 Archivos de Documentación

### 1. RESUMEN_EJECUTIVO_INVENTARIO.md (11 KB)
**Lectura rápida: 10-15 minutos**

Resumen ejecutivo con:
- Números clave del proyecto
- Módulos principales
- Arquitecturas clave
- Estado de seguridad
- Tareas pendientes prioritarias

**Ideal para:**
- Revisión rápida del proyecto
- Reuniones ejecutivas
- Onboarding de nuevos desarrolladores

---

### 2. PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md (57 KB, 1596 líneas)
**Lectura completa: 1-2 horas**

Inventario exhaustivo con:
- 62 Modelos detallados con relaciones
- 19 Recursos Filament
- 19 Servicios de negocio
- 29 Widgets de dashboard
- 10 Políticas de seguridad
- 125 Migraciones de BD
- Mapa completo de relaciones

**Ideal para:**
- Desarrollo de nuevas funcionalidades
- Debugging complejo
- Arquitectura del sistema
- Control de cambios

---

### 3. README_INVENTARIO.md (Este archivo)
**Índice de navegación**

---

## 🔍 NAVEGACIÓN RÁPIDA POR TEMA

### Quiero saber sobre...

#### Modelos y Base de Datos
→ `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Sección "MODELOS DE BASE DE DATOS"
- 62 modelos organizados en 13 categorías
- Relaciones completas
- Scopes y métodos clave

#### Servicios de Cálculo
→ `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Sección "SERVICIOS DE NEGOCIO"
- SimpleItemCalculatorService (nuevo sistema de montaje)
- MountingCalculatorService
- CuttingCalculatorService
- FinishingCalculatorService

#### Sistema de Permisos
→ `RESUMEN_EJECUTIVO_INVENTARIO.md` - Sección "SEGURIDAD"
- 3 capas de verificación
- Estado actual de recursos
- Tareas pendientes

#### Arquitectura Multi-Tenant
→ `RESUMEN_EJECUTIVO_INVENTARIO.md` - Sección "ARQUITECTURAS CLAVE"
- BelongsToTenant trait
- TenantScope automático
- Aislamiento por company_id

#### Widgets de Dashboard
→ `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Sección "WIDGETS DE DASHBOARD"
- 29 widgets organizados por categoría
- Stock, Documentos, Red Social, Calculadoras, Sistema

#### Sistema de Notificaciones
→ `NOTIFICATION_SYSTEM_SUMMARY.md` (Sprint 15)
- 4 tipos de notificaciones
- 7 tablas multi-tenant
- 2 servicios principales

---

## 📊 FLUJOS DE TRABAJO

### Flujo de Cotización → Producción
```
1. Crear Document (tipo: quote)
2. Agregar DocumentItems (polimórficos)
3. Sistema calcula costos automáticos
4. Enviar a cliente (status: sent)
5. Cliente aprueba (status: approved)
6. Crear PurchaseOrder (para proveedores)
7. Crear ProductionOrder (para producción interna)
8. Completar órdenes
9. Document finalizado (status: completed)
```

**Documentación detallada:**
- Modelos: `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Secciones 2 y 5
- Servicios: `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Sección "SERVICIOS DE NEGOCIO"

---

### Flujo de Cálculo de SimpleItem
```
1. Usuario ingresa: tamaño (22×28), cantidad (1000), tintas (4×0)
2. Sistema selecciona máquina (50×35)
3. MountingCalculatorService → 2 copias por pliego
4. CuttingCalculatorService → 4 cortes de máquina en pliego 100×70
5. Impresiones: 1000 ÷ 2 = 500
6. Pliegos: 500 ÷ 4 = 125
7. Millares: 500 ÷ 1000 = 0.5 → 1 millar
8. Costo papel: 125 × $500 = $62,500
9. Costo impresión: 1 millar × 4 tintas × $350 = $1,400
10. Total + margen → Precio final
```

**Documentación detallada:**
- Servicio: `app/Services/SimpleItemCalculatorService.php`
- Notas técnicas: `CLAUDE.md` - Sección "Notas Técnicas" → Sprint 13

---

## 🗺️ MAPA DE ARCHIVOS DE DOCUMENTACIÓN

```
/home/dasiva/Descargas/grafired825/
├── README_INVENTARIO.md                    ← Estás aquí
├── RESUMEN_EJECUTIVO_INVENTARIO.md         ← Lectura rápida (10-15 min)
├── PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md ← Documentación completa (1-2 hrs)
├── CLAUDE.md                               ← Instrucciones para Claude
├── NOTIFICATION_SYSTEM_ANALYSIS.md         ← Análisis técnico notificaciones
├── NOTIFICATION_SYSTEM_SUMMARY.md          ← Guía rápida notificaciones
├── NOTIFICATION_FILE_REFERENCES.md         ← Índice archivos notificaciones
└── README_NOTIFICATIONS.md                 ← Navegación notificaciones
```

---

## 🎯 CASOS DE USO

### Caso 1: "Necesito agregar un nuevo tipo de item"
1. Lee: `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Sección 3 (Modelos de Items Específicos)
2. Estudia: Arquitectura polimórfica de DocumentItem
3. Crea: Nuevo modelo extendiendo la estructura base
4. Implementa: Relación MorphMany con DocumentItem
5. Agrega: Handler en `app/Filament/Resources/Documents/RelationManagers/Handlers/`

### Caso 2: "Necesito modificar el cálculo de costos"
1. Lee: `RESUMEN_EJECUTIVO_INVENTARIO.md` - Sección "SERVICIOS DE CÁLCULO"
2. Identifica: Qué servicio afecta (SimpleItem, Digital, Talonario, Magazine)
3. Modifica: El servicio correspondiente
4. Actualiza: Método `calculateAll()` del modelo si es necesario
5. Prueba: Con diferentes casos de uso

### Caso 3: "Necesito agregar un nuevo widget"
1. Lee: `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Sección "WIDGETS DE DASHBOARD"
2. Crea: Nuevo widget en `app/Filament/Widgets/`
3. Extiende: `\Filament\Widgets\Widget` o subclase específica
4. Registra: En `app/Filament/Pages/Dashboard.php`
5. Prueba: Verifica permisos y multi-tenancy

### Caso 4: "Necesito agregar un nuevo permiso"
1. Lee: `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Sección "SISTEMA DE PERMISOS"
2. Agrega: Permiso en `database/seeders/PermissionsSeeder.php`
3. Asigna: A roles correspondientes en `database/seeders/RolesSeeder.php`
4. Implementa: Verificación en Policy correspondiente
5. Agrega: `canViewAny()` en Resource si aplica
6. Actualiza: Form de RoleResource con nueva categoría si es necesario

---

## 🔧 COMANDOS ÚTILES PARA EXPLORACIÓN

```bash
# Buscar todos los modelos
ls -1 app/Models/*.php | wc -l

# Buscar todos los servicios
ls -1 app/Services/*.php

# Buscar todos los widgets
ls -1 app/Filament/Widgets/*.php

# Buscar todos los recursos
find app/Filament/Resources -name "*Resource.php" -not -path "*/Pages/*" -not -path "*/Schemas/*"

# Buscar relaciones en un modelo específico
grep -n "public function.*(): HasMany\|BelongsTo\|MorphTo" app/Models/Company.php

# Contar migraciones
find database/migrations -name "*.php" | wc -l
```

---

## 📖 LECTURA RECOMENDADA POR ROL

### Desarrollador Backend
1. `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Secciones 1-5 (Modelos)
2. `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Servicios de Negocio
3. `CLAUDE.md` - Notas Técnicas (Sprints 13-15)

### Desarrollador Frontend/Filament
1. `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Recursos Filament
2. `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Widgets de Dashboard
3. `RESUMEN_EJECUTIVO_INVENTARIO.md` - Seguridad

### Arquitecto de Software
1. `RESUMEN_EJECUTIVO_INVENTARIO.md` - Completo
2. `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Mapa de Relaciones
3. `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Arquitectura Multi-Tenant

### Product Manager
1. `RESUMEN_EJECUTIVO_INVENTARIO.md` - Números Clave y Módulos
2. `RESUMEN_EJECUTIVO_INVENTARIO.md` - Flujos de Trabajo
3. `RESUMEN_EJECUTIVO_INVENTARIO.md` - Tareas Pendientes

### QA/Tester
1. `RESUMEN_EJECUTIVO_INVENTARIO.md` - Seguridad
2. `PROYECTO_GRAFIRED_INVENTARIO_COMPLETO.md` - Sistema de Permisos
3. `RESUMEN_EJECUTIVO_INVENTARIO.md` - Flujos de Trabajo

---

## ✅ CHECKLIST DE ACTUALIZACIÓN DE INVENTARIO

Actualizar estos documentos cuando:

- [ ] Se agrega un nuevo modelo
- [ ] Se crea un nuevo servicio
- [ ] Se implementa un nuevo widget
- [ ] Se modifica la arquitectura de cálculo
- [ ] Se agregan nuevos permisos
- [ ] Se cambia el flujo de trabajo principal
- [ ] Se completa una tarea pendiente

**Responsabilidad:** El desarrollador que realiza el cambio debe actualizar la documentación correspondiente.

---

## 🆘 SOPORTE Y CONTACTO

Para preguntas sobre la documentación o el proyecto:

1. Revisar esta guía de navegación
2. Leer la sección relevante en los documentos
3. Consultar `CLAUDE.md` para instrucciones específicas de Claude
4. Revisar el código fuente con las referencias proporcionadas

---

**Última actualización:** 2025-11-07  
**Versión del inventario:** 1.0  
**Próxima revisión:** Después de Sprint 16
