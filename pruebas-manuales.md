# 📋 Pruebas Manuales - LitoPro 3.0

## 🎯 Objetivo
Verificar todas las funcionalidades del sistema de forma manual y sistemática.

---

## 📌 Información de Acceso

### URL Base
```
http://127.0.0.1:8000/admin
```

### Credenciales de Prueba
- **Email**: (Tu usuario administrador)
- **Password**: (Tu contraseña)

---

## 1️⃣ AUTENTICACIÓN Y PERFIL

### 1.1 Login
- [ ] Acceder a `http://127.0.0.1:8000/admin`
- [ ] Ingresar credenciales correctas
- [ ] Verificar que redirige al Dashboard
- [ ] Verificar que muestra nombre de usuario en esquina superior derecha

### 1.2 Configuración de Empresa
- [ ] Ir a **Configuración de Empresa** desde el menú
- [ ] Verificar tabs: Perfil Social, Redes Sociales, Privacidad
- [ ] Cambiar algún dato (nombre, bio, etc.)
- [ ] Guardar cambios
- [ ] Verificar que se guardó correctamente
- [ ] Verificar que `company_type` muestra el tipo correcto (Litografía o Papelería)

### 1.3 Perfil de Usuario
- [ ] Click en nombre de usuario (esquina superior derecha)
- [ ] Seleccionar "Perfil"
- [ ] Verificar datos personales
- [ ] Cambiar avatar o datos
- [ ] Guardar cambios

---

## 2️⃣ GESTIÓN DE CONTACTOS

### 2.1 Clientes y Proveedores
- [ ] Ir a **Contactos → Clientes y Proveedores**
- [ ] Verificar tabla con todos los contactos
- [ ] Verificar filtros por tipo (Cliente, Proveedor, Ambos)
- [ ] **Crear nuevo contacto:**
  - [ ] Click en "Nuevo Contacto"
  - [ ] Llenar formulario (nombre, email, teléfono, NIT, dirección)
  - [ ] Seleccionar tipo: Cliente/Proveedor/Ambos
  - [ ] Guardar
  - [ ] Verificar que aparece en la tabla

### 2.2 Solo Clientes
- [ ] Ir a **Contactos → Clientes**
- [ ] Verificar que solo muestra contactos tipo "Cliente" y "Ambos"
- [ ] Crear nuevo cliente rápido
- [ ] Verificar que aparece en la lista

### 2.3 Solo Proveedores
- [ ] Ir a **Contactos → Proveedores**
- [ ] Verificar que solo muestra contactos tipo "Proveedor" y "Ambos"
- [ ] Crear nuevo proveedor rápido
- [ ] Verificar que aparece en la lista

### 2.4 Solicitudes Comerciales
- [ ] Ir a **Contactos → Solicitudes Comerciales**
- [ ] Verificar tabs: Pendientes, Aprobadas, Rechazadas
- [ ] **Crear nueva solicitud:**
  - [ ] Click en "Nueva Solicitud"
  - [ ] Llenar datos de la empresa solicitante
  - [ ] Guardar
  - [ ] Verificar que aparece en tab "Pendientes"
- [ ] **Aprobar solicitud:**
  - [ ] Abrir una solicitud pendiente
  - [ ] Click en "Aprobar Solicitud"
  - [ ] Verificar que cambia a tab "Aprobadas"
  - [ ] Verificar que se creó el contacto en Clientes y Proveedores
- [ ] **Rechazar solicitud:**
  - [ ] Crear otra solicitud
  - [ ] Click en "Rechazar Solicitud"
  - [ ] Verificar que cambia a tab "Rechazadas"

---

## 3️⃣ DOCUMENTOS - COTIZACIONES

### 3.1 Crear Cotización
- [ ] Ir a **Documentos → Cotizaciones**
- [ ] Click en "Nueva Cotización"
- [ ] **Paso 1: Datos Generales**
  - [ ] Seleccionar cliente (sin necesidad de elegir tipo)
  - [ ] Verificar que número se genera automáticamente
  - [ ] Seleccionar fecha de emisión
  - [ ] Guardar
- [ ] **Paso 2: Agregar Items**
  - [ ] Click en tab "Items"
  - [ ] Click en "Agregar Item"
  - [ ] Seleccionar tipo de item (Simple, Digital, Talonario, Magazine)
  - [ ] Llenar datos del item
  - [ ] Guardar item
  - [ ] Agregar más items si es necesario
- [ ] Verificar que el total se calcula automáticamente

### 3.2 Ver Cotización
- [ ] Abrir cotización creada
- [ ] Verificar layout de 2 columnas (Información General a la izquierda, Fechas/Cliente a la derecha)
- [ ] Verificar que NO hay títulos de sección
- [ ] Verificar que tabla de items tiene fondo azul (#e9f3ff)
- [ ] Verificar datos completos

### 3.3 Generar PDF de Cotización
- [ ] En vista de cotización, click en "Ver PDF"
- [ ] Verificar que se abre en nueva pestaña
- [ ] Verificar logo de empresa
- [ ] Verificar datos de cliente
- [ ] Verificar items y totales
- [ ] Verificar que estado se muestra en español

### 3.4 Enviar Cotización por Email
- [ ] En vista de cotización, click en "Enviar Email al Cliente"
- [ ] Verificar modal de confirmación
- [ ] Confirmar envío
- [ ] **Verificaciones importantes:**
  - [ ] Verificar que muestra notificación de éxito
  - [ ] Verificar que dice "Estado cambiado a 'Enviada'"
  - [ ] Verificar que estado cambió a "Enviada" (badge azul)
  - [ ] Verificar que botón cambia a "Reenviar Email" (color verde)
  - [ ] Verificar que muestra badge "Enviado"
- [ ] Revisar bandeja de entrada del cliente (Mailtrap)
- [ ] Verificar que llegó el email con PDF adjunto

### 3.5 Convertir Cotización a Orden de Pedido
- [ ] En vista de cotización, click en "Convertir a Orden de Pedido"
- [ ] Verificar que se crea la orden con los mismos items
- [ ] Verificar que se redirige a la orden creada

---

## 4️⃣ DOCUMENTOS - ÓRDENES DE PEDIDO

### 4.1 Crear Orden de Pedido
- [ ] Ir a **Documentos → Órdenes de Pedido**
- [ ] Click en "Nueva Orden de Pedido"
- [ ] Seleccionar proveedor (sin necesidad de elegir tipo)
- [ ] Verificar que número se genera automáticamente
- [ ] Agregar items
- [ ] Guardar
- [ ] Verificar que estado es "Borrador"

### 4.2 Ver Orden de Pedido
- [ ] Abrir orden creada
- [ ] Verificar layout similar a cotizaciones
- [ ] Verificar fondo azul en tabla de items
- [ ] Verificar todos los datos

### 4.3 Generar PDF de Orden de Pedido
- [ ] Click en "Ver PDF"
- [ ] Verificar contenido completo
- [ ] Verificar que `company_type` se muestra correctamente

### 4.4 Enviar Orden por Email
- [ ] Click en "Enviar Email al Proveedor"
- [ ] Confirmar envío
- [ ] **Verificaciones importantes:**
  - [ ] Verificar notificación de éxito con cambio de estado
  - [ ] Verificar que estado cambió a "Enviada" (badge azul)
  - [ ] Verificar que botón cambia a "Reenviar Email"
  - [ ] Verificar email en Mailtrap

### 4.5 Cambiar Estados Manualmente
- [ ] **Cambiar a "En Proceso":**
  - [ ] Click en acción "Cambiar Estado" o similar
  - [ ] Seleccionar "En Proceso"
  - [ ] Confirmar
  - [ ] **Verificar que NO se envía email** (revisar Mailtrap)
  - [ ] Verificar que estado cambió a "En Proceso" (badge amarillo)
- [ ] **Cambiar a "Finalizada":**
  - [ ] Click en acción "Cambiar Estado"
  - [ ] Seleccionar "Finalizada"
  - [ ] Confirmar
  - [ ] **Verificar que NO se envía email**
  - [ ] Verificar que estado cambió a "Finalizada" (badge verde)

### 4.6 Tabs de Estados en Lista
- [ ] Ir a lista de Órdenes de Pedido
- [ ] Verificar tabs: Todas, Borrador, Enviadas, En Proceso, Finalizadas, Canceladas
- [ ] Click en cada tab
- [ ] Verificar que filtra correctamente

---

## 5️⃣ DOCUMENTOS - ÓRDENES DE PRODUCCIÓN

### 5.1 Crear Orden de Producción
- [ ] Ir a **Documentos → Órdenes de Producción**
- [ ] Click en "Nueva Orden de Producción"
- [ ] Seleccionar proveedor
- [ ] Seleccionar operador asignado
- [ ] Programar fecha
- [ ] Agregar notas
- [ ] Guardar
- [ ] Verificar que estado es "Borrador"
- [ ] **IMPORTANTE:** Verificar que queda en modo edición (no redirige a vista)

### 5.2 Agregar Items a Orden de Producción
- [ ] En modo edición, ir a tab "Items"
- [ ] Agregar items de producción
- [ ] Guardar
- [ ] Verificar que totales se calculan (items, millares, horas)

### 5.3 Ver Orden de Producción
- [ ] Abrir orden creada
- [ ] Verificar información completa
- [ ] Verificar sección de proveedor
- [ ] Verificar sección de operador
- [ ] Verificar métricas (total items, millares)

### 5.4 Enviar Orden de Producción por Email
- [ ] Click en "Enviar Email al Operador"
- [ ] Confirmar envío
- [ ] **Verificaciones importantes:**
  - [ ] Verificar notificación con cambio de estado
  - [ ] Verificar que estado cambió a "Enviada"
  - [ ] Verificar email en Mailtrap
  - [ ] Verificar que NO se envía notificación de base de datos

### 5.5 Workflow de Producción
- [ ] **Iniciar Producción:**
  - [ ] Con estado "Enviada", click en "Iniciar Producción"
  - [ ] Verificar que pide confirmar proveedor y operador
  - [ ] Confirmar
  - [ ] Verificar que estado cambió a "En Proceso"
  - [ ] Verificar que se registra fecha de inicio
  - [ ] **Verificar que NO se envía email**
- [ ] **Completar Producción:**
  - [ ] Con estado "En Proceso", click en "Completar"
  - [ ] Confirmar
  - [ ] Verificar que estado cambió a "Finalizada"
  - [ ] Verificar que se registra fecha de finalización
  - [ ] **Verificar que NO se envía email**

### 5.6 Tabs en Lista
- [ ] Ir a lista de Órdenes de Producción
- [ ] Verificar tabs: Todas, Borrador, Enviadas, En Proceso, Finalizadas
- [ ] Verificar que NO existe tab "En Cola" (QUEUED eliminado)
- [ ] Verificar filtrado correcto

---

## 6️⃣ DOCUMENTOS - CUENTAS DE COBRO

### 6.1 Crear Cuenta de Cobro
- [ ] Ir a **Documentos → Cuentas de Cobro**
- [ ] Click en "Nueva Cuenta de Cobro"
- [ ] Seleccionar cliente (sin elegir tipo)
- [ ] Seleccionar fecha de emisión
- [ ] Seleccionar fecha de vencimiento
- [ ] Agregar items
- [ ] Guardar
- [ ] Verificar que número se genera automáticamente

### 6.2 Ver Cuenta de Cobro
- [ ] Abrir cuenta creada
- [ ] Verificar layout de 2 columnas
- [ ] Verificar fondo azul en items
- [ ] Verificar fechas y totales

### 6.3 Enviar Cuenta por Email (Vista)
- [ ] En vista de cuenta, click en "Enviar Email al Cliente"
- [ ] Confirmar envío
- [ ] **Verificaciones importantes:**
  - [ ] Verificar notificación con cambio de estado
  - [ ] Verificar que estado cambió a "Enviada"
  - [ ] Verificar email en Mailtrap
  - [ ] Verificar PDF adjunto

### 6.4 Enviar Cuenta por Email (Edición)
- [ ] Ir a editar cuenta
- [ ] Click en "Enviar por Email" desde edición
- [ ] Ingresar email del cliente (si es necesario)
- [ ] Confirmar envío
- [ ] **Verificar que estado cambió a "Enviada"**
- [ ] Verificar email

### 6.5 Enviar Cuenta por Email (Tabla)
- [ ] Ir a lista de Cuentas de Cobro
- [ ] En acciones de una cuenta (menú 3 puntos)
- [ ] Click en "Enviar email"
- [ ] Confirmar
- [ ] **Verificar que estado cambió a "Enviada"**
- [ ] Verificar email

### 6.6 Workflow de Aprobación
- [ ] **Cambiar a "Aprobada":**
  - [ ] Abrir cuenta con estado "Enviada"
  - [ ] Click en "Cambiar Estado"
  - [ ] Seleccionar "Aprobada"
  - [ ] Agregar notas (opcional)
  - [ ] Confirmar
  - [ ] Verificar que estado cambió a "Aprobada" (badge amarillo)
- [ ] **Marcar como Pagada:**
  - [ ] Con estado "Aprobada", click en "Marcar como Pagada"
  - [ ] Confirmar
  - [ ] Verificar que estado cambió a "Pagada" (badge verde)
  - [ ] Verificar que se registró fecha de pago
  - [ ] Verificar que ya NO se puede editar

### 6.7 Estados y Permisos
- [ ] Verificar que cuenta "Pagada" NO se puede editar
- [ ] Verificar que cuenta "Pagada" NO muestra botón "Editar"
- [ ] Verificar que cuenta "Cancelada" NO se puede cambiar de estado

### 6.8 Filtros en Tabla
- [ ] Probar filtro por estado
- [ ] Probar filtro por cliente
- [ ] Probar filtro "Vencidas"
- [ ] Probar filtro "Por Vencer (7 días)"

---

## 7️⃣ INVENTARIO - PAPELES

### 7.1 Gestión de Papeles
- [ ] Ir a **Inventario → Papeles**
- [ ] Click en "Nuevo Papel"
- [ ] Llenar datos (nombre, gramaje, medidas, proveedor)
- [ ] Guardar
- [ ] Verificar que aparece en tabla

### 7.2 Ver Papel
- [ ] Abrir papel creado
- [ ] Verificar stock actual
- [ ] Verificar movimientos de stock (si hay)

---

## 8️⃣ INVENTARIO - MÁQUINAS

### 8.1 Gestión de Máquinas
- [ ] Ir a **Inventario → Máquinas**
- [ ] Click en "Nueva Máquina"
- [ ] Llenar datos (nombre, tipo, marca, modelo)
- [ ] Guardar
- [ ] Verificar en tabla

---

## 9️⃣ INVENTARIO - ITEMS DIGITALES

### 9.1 Gestión de Items Digitales
- [ ] Ir a **Inventario → Items Digitales**
- [ ] Click en "Nuevo Item Digital"
- [ ] Llenar formulario completo
- [ ] Guardar
- [ ] Verificar en tabla

---

## 🔟 STOCK

### 10.1 Dashboard de Stock (Página Consolidada)
- [ ] Ir a **Stock** (página principal)
- [ ] Verificar 3 widgets en header:
  - [ ] Total Papeles
  - [ ] Total Productos
  - [ ] Stock Bajo
- [ ] **Tab "Resumen":**
  - [ ] Verificar widgets de totales
  - [ ] Verificar gráficos (si hay)
- [ ] **Tab "Movimientos":**
  - [ ] Verificar tabla de últimos movimientos
  - [ ] Verificar columnas (fecha, tipo, item, cantidad)
- [ ] **Tab "Alertas":**
  - [ ] Verificar items con stock bajo
  - [ ] Verificar alertas de reabastecimiento

### 10.2 Movimientos de Stock
- [ ] Ir a tab "Movimientos" o página específica
- [ ] Click en "Nuevo Movimiento"
- [ ] Seleccionar tipo (Entrada/Salida)
- [ ] Seleccionar item
- [ ] Ingresar cantidad
- [ ] Guardar
- [ ] Verificar que se actualiza stock del item

---

## 1️⃣1️⃣ SOLICITUDES COMERCIALES (Gestión Completa)

### 11.1 Solicitud como Cliente Externo
- [ ] **Simulación:** Imaginar que eres una empresa externa
- [ ] Crear solicitud comercial (formulario público o desde admin)
- [ ] Verificar que queda en estado "Pendiente"

### 11.2 Badge en Menú
- [ ] Ir al menú lateral
- [ ] Verificar que "Solicitudes Comerciales" tiene badge con número
- [ ] Verificar que el número corresponde a solicitudes pendientes

### 11.3 Gestión de Solicitudes
- [ ] Ir a **Contactos → Solicitudes Comerciales**
- [ ] **Visualizar solicitud:**
  - [ ] Click en una solicitud pendiente
  - [ ] Verificar página de visualización completa
  - [ ] Verificar datos de la empresa solicitante
- [ ] **Aprobar desde vista:**
  - [ ] En vista de solicitud, click en "Aprobar Solicitud"
  - [ ] Confirmar
  - [ ] Verificar que estado cambió a "Aprobada"
  - [ ] Verificar que se creó contacto en Clientes y Proveedores
  - [ ] Verificar que badge del menú disminuyó
- [ ] **Rechazar desde vista:**
  - [ ] Abrir otra solicitud
  - [ ] Click en "Rechazar Solicitud"
  - [ ] Confirmar
  - [ ] Verificar que estado cambió a "Rechazada"
  - [ ] Verificar que badge del menú disminuyó

---

## 1️⃣2️⃣ SISTEMA DE ACABADOS

### 12.1 Gestión de Acabados
- [ ] Ir a **Acabados** (si está visible según company_type)
- [ ] Click en "Nuevo Acabado"
- [ ] Llenar datos (nombre, descripción, proveedor)
- [ ] Guardar
- [ ] Verificar en tabla

### 12.2 Usar Acabados en Productos
- [ ] Crear producto con acabados
- [ ] Agregar acabado al producto
- [ ] Verificar cálculo de costos

---

## 1️⃣3️⃣ NOTIFICACIONES Y EMAILS

### 13.1 Verificar NO hay Notificaciones Automáticas
- [ ] **Crear Orden de Pedido:**
  - [ ] Crear nueva orden
  - [ ] Guardar
  - [ ] **Verificar que NO aparece notificación en campana de Filament**
  - [ ] **Verificar que NO se envía email automático**
- [ ] **Cambiar estado de Orden de Pedido:**
  - [ ] Cambiar de "Borrador" a "En Proceso"
  - [ ] **Verificar que NO aparece notificación en campana**
  - [ ] **Verificar que NO se envía email**
- [ ] **Crear Cuenta de Cobro:**
  - [ ] Crear nueva cuenta
  - [ ] Guardar
  - [ ] **Verificar que NO aparece notificación en campana**
  - [ ] **Verificar que NO se envía email**

### 13.2 Verificar Emails Manuales Funcionan
- [ ] **Enviar Cotización:**
  - [ ] Enviar email manualmente
  - [ ] Verificar que llega a Mailtrap
  - [ ] Verificar PDF adjunto
- [ ] **Enviar Orden de Pedido:**
  - [ ] Enviar email manualmente
  - [ ] Verificar en Mailtrap
- [ ] **Enviar Orden de Producción:**
  - [ ] Enviar email manualmente
  - [ ] Verificar en Mailtrap
- [ ] **Enviar Cuenta de Cobro:**
  - [ ] Enviar email manualmente desde 3 lugares (Vista, Edición, Tabla)
  - [ ] Verificar que todos llegan a Mailtrap

---

## 1️⃣4️⃣ PERMISOS Y ROLES (Si aplica)

### 14.1 Gestión de Roles
- [ ] Ir a **Roles** (si está disponible)
- [ ] Crear nuevo rol
- [ ] Asignar permisos
- [ ] Guardar

### 14.2 Gestión de Usuarios
- [ ] Ir a **Usuarios**
- [ ] Crear nuevo usuario
- [ ] Asignar rol
- [ ] Guardar
- [ ] Verificar que el usuario tiene los permisos correctos

---

## 1️⃣5️⃣ BÚSQUEDA Y FILTROS

### 15.1 Búsqueda Global
- [ ] Usar barra de búsqueda global (si existe)
- [ ] Buscar por número de cotización
- [ ] Buscar por nombre de cliente
- [ ] Buscar por producto
- [ ] Verificar resultados

### 15.2 Filtros en Tablas
- [ ] **En Cotizaciones:**
  - [ ] Filtrar por estado
  - [ ] Filtrar por cliente
  - [ ] Filtrar por fecha
- [ ] **En Órdenes de Pedido:**
  - [ ] Filtrar por estado
  - [ ] Filtrar por proveedor
- [ ] **En Cuentas de Cobro:**
  - [ ] Filtrar por estado
  - [ ] Filtrar por cliente
  - [ ] Filtrar por "Vencidas"
  - [ ] Filtrar por "Por Vencer"

---

## 1️⃣6️⃣ EXPORTACIÓN Y REPORTES

### 16.1 Exportar Datos
- [ ] En cualquier tabla, click en "Exportar"
- [ ] Seleccionar formato (Excel/CSV)
- [ ] Descargar archivo
- [ ] Verificar contenido

### 16.2 Ver PDFs
- [ ] Generar PDF de cotización
- [ ] Generar PDF de orden de pedido
- [ ] Generar PDF de orden de producción
- [ ] Generar PDF de cuenta de cobro
- [ ] Verificar que todos tienen logo
- [ ] Verificar que datos son correctos
- [ ] Verificar que estados están en español

---

## 1️⃣7️⃣ RESPONSIVE Y UX

### 17.1 Vista Desktop
- [ ] Verificar que todas las páginas se ven bien en desktop
- [ ] Verificar que layout de 2 columnas funciona
- [ ] Verificar que fondo azul de items es visible

### 17.2 Vista Tablet
- [ ] Reducir ventana a tamaño tablet
- [ ] Verificar que menú lateral se adapta
- [ ] Verificar que tablas son navegables

### 17.3 Vista Mobile
- [ ] Reducir ventana a tamaño móvil
- [ ] Verificar que menú se vuelve hamburguesa
- [ ] Verificar que formularios son usables

---

## 1️⃣8️⃣ VALIDACIONES Y ERRORES

### 18.1 Validaciones en Formularios
- [ ] **Crear cotización sin cliente:**
  - [ ] Intentar guardar sin seleccionar cliente
  - [ ] Verificar mensaje de error
- [ ] **Crear cuenta de cobro sin items:**
  - [ ] Intentar enviar email sin items
  - [ ] Verificar mensaje: "La cuenta no tiene items"
- [ ] **Cuenta con total $0:**
  - [ ] Intentar enviar cuenta con total 0
  - [ ] Verificar mensaje: "La cuenta tiene un total de $0"
- [ ] **Email sin configurar:**
  - [ ] Intentar enviar a cliente sin email
  - [ ] Verificar mensaje: "El cliente no tiene email configurado"

### 18.2 Permisos de Edición
- [ ] **Cuenta Pagada:**
  - [ ] Intentar editar cuenta con estado "Pagada"
  - [ ] Verificar que redirige a vista
  - [ ] Verificar mensaje: "No se puede editar una cuenta pagada"

---

## 1️⃣9️⃣ INTEGRACIÓN ENTRE MÓDULOS

### 19.1 Flujo Completo: Cotización → Orden de Pedido
- [ ] Crear cotización completa
- [ ] Enviar cotización por email
- [ ] Convertir a orden de pedido
- [ ] Verificar que items se copian correctamente
- [ ] Enviar orden por email
- [ ] Cambiar estado a "En Proceso"
- [ ] Cambiar estado a "Finalizada"

### 19.2 Flujo: Solicitud → Cliente → Cotización
- [ ] Crear solicitud comercial
- [ ] Aprobar solicitud
- [ ] Verificar que se creó contacto
- [ ] Crear cotización para ese nuevo cliente
- [ ] Completar flujo

---

## 2️⃣0️⃣ LIMPIEZA Y MANTENIMIENTO

### 20.1 Eliminar Registros
- [ ] Eliminar una cotización
- [ ] Eliminar una orden de pedido
- [ ] Eliminar un contacto
- [ ] Verificar que se eliminan correctamente
- [ ] Verificar que no quedan referencias huérfanas

### 20.2 Edición de Registros
- [ ] Editar cotización
- [ ] Cambiar cliente
- [ ] Cambiar items
- [ ] Guardar cambios
- [ ] Verificar que totales se recalculan

---

## ✅ CHECKLIST FINAL

### Estados del Sistema
- [ ] Todos los estados están en español
- [ ] Todos los estados tienen colores correctos
- [ ] No existen referencias a estados obsoletos (QUEUED, ON_HOLD, CONFIRMED, RECEIVED)

### Emails
- [ ] Emails manuales funcionan correctamente
- [ ] NO se envían emails automáticos al crear registros
- [ ] NO se envían emails al cambiar estados manualmente
- [ ] Cambio automático de estado a "Enviada" al enviar email funciona en:
  - [ ] Cotizaciones
  - [ ] Órdenes de Pedido
  - [ ] Órdenes de Producción
  - [ ] Cuentas de Cobro (desde 3 lugares)

### Interfaz
- [ ] Layout de 2 columnas funciona en vistas
- [ ] Fondo azul de items es visible
- [ ] No hay títulos de sección en vistas
- [ ] ActionGroup (menú 3 puntos) funciona en tablas

### Enums
- [ ] `OrderStatus` usa `getLabel()`, `getColor()`, `getIcon()`
- [ ] `ProductionStatus` usa `getLabel()`, `getColor()`, `getIcon()`
- [ ] `CollectionAccountStatus` usa `getLabel()`, `getColor()`, `getIcon()`
- [ ] `CompanyType` usa `label()` (sin interfaces Filament)

---

## 📝 NOTAS DE PRUEBA

### Errores Encontrados
```
(Anota aquí cualquier error que encuentres durante las pruebas)
```

### Sugerencias de Mejora
```
(Anota aquí sugerencias para mejorar la UX)
```

### Funcionalidades que Faltan
```
(Anota aquí funcionalidades que consideres necesarias)
```

---

## 🎉 PRUEBAS COMPLETADAS

Fecha de inicio: _______________
Fecha de finalización: _______________
Probado por: _______________

**Total de pruebas:** ~150+
**Pruebas exitosas:** _____ / _____
**Errores encontrados:** _____
**Estado general:** ⭐⭐⭐⭐⭐ (1-5 estrellas)

---

## 📧 Verificación de Emails (Mailtrap)

### Acceso a Mailtrap
```
URL: https://mailtrap.io/inboxes
```

### Emails a Verificar
- [ ] Cotización enviada - Formato correcto
- [ ] Orden de Pedido enviada - PDF adjunto
- [ ] Orden de Producción enviada - Datos de operador
- [ ] Cuenta de Cobro enviada - Información de pago
- [ ] Password Reset - Enlace funcional

### Contenido de Emails
- [ ] Logo de empresa aparece
- [ ] Datos correctos
- [ ] PDF adjunto se puede abrir
- [ ] Enlaces funcionan (si aplica)
- [ ] Formato responsive

---

**FIN DEL DOCUMENTO DE PRUEBAS MANUALES**
