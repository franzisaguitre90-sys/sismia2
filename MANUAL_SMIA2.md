# SMIA2 — Manual de Usuario y Requisitos del Sistema
**Sistema de Monitoreo e Información Ambiental v2.0**  
Dirección de Saneamiento Básico, Recursos Hídricos y Control Ambiental  
Aplicación Ley 1333 — República de Bolivia

---

## PARTE I — REQUISITOS DEL SISTEMA

### 1.1 Requisitos de Hardware (Servidor)

| Componente | Mínimo | Recomendado |
|------------|--------|-------------|
| Procesador | Intel Core i3 / AMD equivalente | Intel Core i5 o superior |
| Memoria RAM | 4 GB | 8 GB |
| Almacenamiento | 20 GB libres | 50 GB SSD |
| Red | 10 Mbps LAN | 100 Mbps LAN |

### 1.2 Requisitos de Software (Servidor)

| Software | Versión mínima | Notas |
|----------|---------------|-------|
| XAMPP | 8.2+ | Incluye Apache, MySQL/MariaDB y PHP |
| PHP | 8.1+ | Extensiones: PDO, PDO_MySQL, mbstring, fileinfo |
| MySQL / MariaDB | 10.4+ | Base de datos: smiabasedata |
| Apache | 2.4+ | mod_rewrite activado |
| Navegador cliente | Chrome 90+, Firefox 88+, Edge 90+ | No compatible con IE |

### 1.3 Requisitos del Cliente (Usuarios)

- Navegador web moderno con JavaScript habilitado
- Resolución mínima de pantalla: 1024 × 768 px
- Conexión a la red local donde está el servidor
- No requiere instalación de software adicional

### 1.4 Puertos utilizados

| Puerto | Servicio |
|--------|---------|
| 80 | Apache HTTP |
| 3306 | MySQL / MariaDB |

---

## PARTE II — INSTALACIÓN Y PUESTA EN MARCHA

### Paso 1 — Instalar XAMPP

1. Descargar XAMPP desde https://www.apachefriends.org
2. Instalar con los componentes: **Apache**, **MySQL**, **PHP**
3. Abrir el **Panel de Control de XAMPP**
4. Iniciar los módulos **Apache** y **MySQL**

### Paso 2 — Copiar el sistema

1. Copiar la carpeta `SMIA2` dentro de `C:\xampp\htdocs\`
2. Verificar que la ruta quede: `C:\xampp\htdocs\SMIA2\`

### Paso 3 — Crear la base de datos

Abrir el navegador y acceder a:
```
http://localhost/SMIA2/tool/install_direct.php
```
Este script:
- Crea la base de datos `smiabasedata`
- Genera todas las tablas y datos iniciales
- Crea el usuario administrador

### Paso 4 — Ingresar al sistema

```
http://localhost/SMIA2/login.php
```

**Credenciales iniciales del administrador:**
| Campo | Valor |
|-------|-------|
| Usuario | `admin` |
| Contraseña | `admin` |

> **Importante:** Cambiar la contraseña del administrador en el primer ingreso.

### Paso 5 — Crear usuarios del personal

Una vez dentro como administrador, ir a:
**Menú → Administrar Usuarios** y crear las cuentas para:
- Directores
- Técnicos
- Secretarias

Los consultores se auto-registran desde el portal público.

---

## PARTE III — ACCESOS AL SISTEMA

| Página | URL |
|--------|-----|
| Portal informativo público | `http://localhost/SMIA2/` |
| Inicio de sesión | `http://localhost/SMIA2/login.php` |
| Auto-registro consultor | `http://localhost/SMIA2/registro.php` |
| Recuperar clave única | `http://localhost/SMIA2/recuperar_clave.php` |

---

## PARTE IV — MANUAL POR ROL

---

## ROL: ADMINISTRADOR

**Color de interfaz:** Azul oscuro  
**Acceso:** Solo para el personal técnico del sistema

### Funciones disponibles

#### Gestionar usuarios
- Ir a **Administrar Usuarios**
- Hacer clic en **Nuevo Usuario**
- Completar: nombre, apellido, email, username, contraseña, rol
- Marcar como **activo** para que pueda ingresar
- Para desactivar un usuario: usar el botón **Activar/Desactivar**
- Para cambiar contraseña: botón **Cambiar Contraseña** en la lista

#### Dashboard administrativo
Muestra en tiempo real:
- Total de usuarios activos por rol
- Hojas de ruta en el sistema
- Habilitaciones registradas
- Sesiones activas en este momento
- Últimas 20 acciones del registro de auditoría
- Últimas 10 hojas de ruta ingresadas

---

## ROL: DIRECTOR

**Color de interfaz:** Azul  
**Responsabilidad:** Supervisión general, asignación a técnicos, evaluación de KPIs

### 4.1 Dashboard

Al ingresar verá:
- **Tarjetas de estadísticas:** total de HDR, pendientes de asignación, en revisión, aprobadas, finalizadas
- **Gráfico mensual:** trámites ingresados vs finalizados (últimos 6 meses)
- **Panel de asignaciones pendientes:** HDR esperando ser asignadas
- **Tabla de KPIs:** eficiencia de cada técnico en el mes actual

### 4.2 Ver Hojas de Ruta

1. Ir a **Hojas de Ruta** en el menú lateral
2. Usar el buscador por código HDR, empresa o estado
3. Hacer clic en el ícono 👁 para ver el detalle completo e historial de estados

### 4.3 Asignar trámite a técnico

1. Ir a **Hojas de Ruta** → filtrar por estado **Ingresado**
2. Hacer clic en **Asignar** en el trámite deseado
3. Seleccionar el técnico (se muestra la carga actual de cada uno)
4. Configurar: prioridad, días límite, observaciones para el técnico
5. Hacer clic en **Asignar Trámite**
6. El sistema notifica automáticamente al técnico y al consultor

### 4.4 KPIs de Técnicos

1. Ir a **KPIs Técnicos** en el menú
2. Ver tarjetas individuales con: eficiencia %, trámites completados, pendientes, rechazados
3. Seleccionar un técnico para ver su gráfico histórico de 6 meses

### 4.5 Crear cuentas de personal

1. Ir a **Administrar Usuarios**
2. Crear cuentas para técnicos, directores y secretarias
3. El sistema genera un username sugerido automáticamente

---

## ROL: TÉCNICO

**Color de interfaz:** Verde  
**Responsabilidad:** Revisar documentos, evaluar formularios RAI, aprobar o rechazar trámites

### 5.1 Dashboard

Muestra al ingresar:
- KPI personal del mes: eficiencia, completados, pendientes
- Próximos turnos programados con capacidad disponible
- Recordatorios del día
- Lista de trámites activos con días restantes (en rojo si vencen pronto)

### 5.2 Revisar mis trámites

1. Ir a **Mis Trámites** en el menú
2. Ver todos los trámites asignados con su estado actual
3. Hacer clic en el código HDR para ver el detalle completo
4. En el panel de detalle:
   - Ver datos de la empresa y el consultor
   - Ver el formulario RAI enviado
   - Revisar documentos cargados
   - Cambiar el estado del trámite

#### Estados que puede asignar el técnico:
| Estado | Significado |
|--------|-------------|
| En Revisión | Inició la revisión del expediente |
| Observado | Devuelve al consultor para que corrija o agregue información |
| Aprobado | El trámite está listo para que secretaría emita la habilitación |
| Rechazado | El trámite no cumple los requisitos y es denegado |

> Al aprobar un trámite, secretaría recibe una notificación automática.

### 5.3 Revisar documentos

1. Ir a **Revisar Documentos**
2. En el panel izquierdo: lista de trámites con documentos pendientes
3. Seleccionar un trámite para ver sus documentos
4. Por cada documento: **Aprobar**, **Observar** (con comentario) o **Rechazar**
5. El consultor recibe notificación de cada acción

### 5.4 Sistema de Turnos

1. Ir a **Turnos** en el menú
2. Programar un turno: fecha, hora inicio, hora fin, capacidad de trámites
3. La barra de progreso muestra cuántos trámites están asignados vs la capacidad
4. Cambiar estado del turno: Programado → Activo → Completado

### 5.5 Recordatorios

1. Ir a **Recordatorios** en el menú
2. Crear un recordatorio: título, fecha/hora, repetición (diario/semanal/mensual)
3. Puede asociarlo a una Hoja de Ruta específica
4. Los recordatorios vencidos aparecen en rojo
5. Marcar como **Hecho** cuando se completa

---

## ROL: CONSULTOR

**Color de interfaz:** Naranja/Rojo  
**Acceso inicial:** Auto-registro en el portal público

### 6.1 Auto-registro

1. Ir a `http://localhost/SMIA2/registro.php`
2. Completar el formulario con datos personales y profesionales
3. El sistema genera automáticamente un username
4. **Importante:** La **Clave Única** (SMIA-XXXXXXXX) se asigna cuando secretaría finaliza el primer trámite. Hasta entonces, el campo aparece vacío.

### 6.2 Dashboard

Muestra:
- Alertas si algún trámite tiene observaciones (acción requerida del consultor)
- Estadísticas: total HDR, por estado
- Accesos directos: Nueva HDR, Formulario RAI, Subir Documentos, Mis Trámites
- Tabla con todos sus trámites

### 6.3 Ingresar una nueva Hoja de Ruta (HDR)

1. Ir a **Nueva Hoja de Ruta** o hacer clic en el botón del dashboard
2. Seleccionar la modalidad:
   - **Nuevo RAI:** la empresa obtiene su registro por primera vez
   - **RAI Asignado:** la empresa ya tiene número RAI y solo necesita habilitación
3. Configurar prioridad y días límite (opcional)
4. Hacer clic en **Crear Hoja de Ruta**
5. El sistema genera el código **HDR-YYYY-NNNNNN** automáticamente
6. El director recibe una notificación inmediata

### 6.4 Llenar el Formulario RAI

1. Ir a **Formulario RAI** en el menú
2. Seleccionar la Hoja de Ruta correspondiente
3. Completar las 5 secciones:
   - **Datos de la empresa** (razón social, NIT, actividad)
   - **Ubicación** (municipio, departamento, dirección, coordenadas)
   - **Clasificación** (categoría A/B/C, tipo de actividad industrial)
   - **Representante Legal** (nombre, CI, cargo, contacto)
   - **Gestión Ambiental** (residuos, efluentes, emisiones, compromisos)
4. **Guardar Borrador:** guarda sin enviar, puede seguir editando
5. **Enviar Formulario:** lo envía para revisión del técnico (ya no se puede editar)

#### Categorías RAI:
| Categoría | Tipo de actividad |
|-----------|------------------|
| A | Alto impacto ambiental (industria pesada, minería) |
| B | Impacto moderado (manufactura, agroindustria) |
| C | Bajo impacto (pequeña industria, comercio) |

### 6.5 Subir Documentos

1. Ir a **Subir Documentos**
2. Seleccionar la Hoja de Ruta
3. Seleccionar el tipo de documento del listado
4. Arrastrar el archivo al área de carga o hacer clic para seleccionar
5. Formatos aceptados: PDF, JPG, PNG, DOC, DOCX (máx. 20 MB)
6. El técnico recibe notificación al subir cada documento
7. Ver el estado de cada documento: Pendiente / Aprobado / Observado / Rechazado

#### Checklist de documentos requeridos:
- Nuevo RAI: Formulario RAI, Memorial descriptivo, Croquis de ubicación, Planos, Ficha ambiental, Licencia de funcionamiento, NIT, CI del representante, Poder notarial
- RAI Asignado: Número RAI existente, Memorial de actualización, Documentos de identidad

### 6.6 Seguimiento de mis trámites

1. Ir a **Mis Trámites**
2. Ver el estado actual de cada HDR
3. Hacer clic en el código para ver el detalle completo:
   - Historial de estados con fechas y observaciones
   - Lista de documentos con su estado de revisión
   - Datos del técnico asignado

> Si un trámite está en estado **Observado**, revisar las observaciones del técnico y subir la documentación corregida.

### 6.7 Recuperar Clave Única

Si olvidó su clave única o username:
1. Ir a `http://localhost/SMIA2/recuperar_clave.php`
2. Ingresar su email o clave única
3. El sistema muestra los datos de acceso
4. Opcionalmente puede cambiar su contraseña

---

## ROL: SECRETARIA

**Color de interfaz:** Violeta/Morado  
**Responsabilidad:** Registrar la habilitación ambiental final y asignar clave única al consultor

### 7.1 Dashboard

Muestra:
- Trámites aprobados pendientes de habilitación (acción requerida)
- Habilitaciones registradas recientemente
- Certificados próximos a vencer (alerta 30 días)

### 7.2 Ver trámites listos para habilitar

1. Ir a **Trámites para Habilitar** en el menú
2. Ver la lista de HDR con estado **Aprobado** por el técnico
3. Los que ya tienen habilitación registrada aparecen en verde
4. Hacer clic en 👁 para ver los datos completos de la empresa y el consultor

### 7.3 Registrar Habilitación Ambiental

1. En **Trámites para Habilitar**, hacer clic en **Habilitar** en el trámite correspondiente
2. Seleccionar el resultado:

#### Si es HABILITADO:
- Ingresar **N° RAI Otorgado** (formato: RAI-LP-2026-XXXXXX)
- Ingresar **N° de Certificado**
- Ingresar **N° de Resolución**
- Seleccionar **Fecha de Emisión**
- Configurar **Vigencia en años** (1 a 5 años, el sistema calcula el vencimiento automáticamente)
- Agregar condiciones especiales si corresponde

#### Si es NO HABILITADO:
- Ingresar el **Motivo de No Habilitación** (texto descriptivo)

3. Ingresar el nombre del director que aprueba
4. Hacer clic en **Registrar Habilitación Definitiva**
5. Confirmar la acción en el cuadro de diálogo

**Al registrar, el sistema automáticamente:**
- Finaliza la Hoja de Ruta
- Genera y asigna la **Clave Única** al consultor si no tenía
- Notifica al consultor con su clave única
- Notifica a los directores

### 7.4 Verificar Habilitaciones

1. Ir a **Verificar Habilitaciones** en el menú
2. Buscar por: código HDR, empresa, NIT o número de certificado
3. Ver el panel de detalle con:
   - Datos del certificado (número, resolución, fechas, vigencia)
   - Datos del consultor y su clave única
   - Datos de la empresa
4. Las habilitaciones próximas a vencer (menos de 30 días) se marcan en rojo

---

## PARTE V — SISTEMA DE NOTIFICACIONES

Todas las notificaciones aparecen en el **ícono de campana** en la barra superior.

| Evento | Quién recibe |
|--------|-------------|
| Nueva HDR ingresada | Directores |
| HDR asignada a técnico | Técnico + Consultor |
| Documento subido | Técnico asignado |
| Estado de HDR cambiado | Consultor + Directores |
| HDR aprobada por técnico | Secretaria + Directores |
| Habilitación registrada | Consultor (con clave única) + Directores |
| Alerta de vencimiento de plazo | Director + Técnico asignado |

- Las notificaciones no leídas muestran un número rojo en la campana
- Hacer clic en una notificación la marca como leída
- El botón **Marcar todas como leídas** limpia todas a la vez
- Las notificaciones se actualizan automáticamente cada 60 segundos

---

## PARTE VI — SISTEMA DE ALARMAS

El sistema revisa automáticamente en cada página los trámites con plazos próximos a vencer:

- Si quedan **≤ N días** (configurado en cada HDR), se genera una alerta automática
- Las alertas aparecen en la **barra roja** en la parte superior del dashboard
- Solo se envía una vez por trámite (no se repite)
- El director y el técnico asignado reciben la notificación

---

## PARTE VII — FLUJO COMPLETO DE UN TRÁMITE

```
1. CONSULTOR    →  Se registra en el portal
2. CONSULTOR    →  Crea una nueva Hoja de Ruta (HDR-2026-XXXXXX)
3. CONSULTOR    →  Llena el Formulario RAI
4. CONSULTOR    →  Sube los documentos requeridos
5. DIRECTOR     →  Recibe notificación, revisa y asigna a un técnico
6. TÉCNICO      →  Recibe notificación, revisa documentos y formulario RAI
7. TÉCNICO      →  Puede observar (devolver) o aprobar el trámite
8. CONSULTOR    →  Si fue observado: corrige y reenvía documentos
9. TÉCNICO      →  Aprueba el trámite
10. SECRETARIA  →  Recibe notificación, registra la habilitación definitiva
11. CONSULTOR   →  Recibe notificación con resultado y su Clave Única
12. DIRECTOR    →  Visualiza el trámite como Finalizado en el dashboard
```

**Duración típica del proceso:** configurable por trámite (por defecto 30 días)

---

## PARTE VIII — PREGUNTAS FRECUENTES

**¿Qué hago si olvidé mi contraseña?**  
Ir a `http://localhost/SMIA2/recuperar_clave.php` e ingresar su email. Si aún no tiene clave única asignada, contactar a la secretaría.

**¿Puedo tener dos sesiones abiertas al mismo tiempo?**  
No. El sistema solo permite una sesión activa por usuario. Al iniciar sesión en otro dispositivo, la sesión anterior se cierra automáticamente.

**¿Qué formatos acepta el sistema para documentos?**  
PDF, JPG, PNG, DOC, DOCX. Tamaño máximo por archivo: 20 MB.

**¿Quién asigna la Clave Única al consultor?**  
Se genera y asigna automáticamente cuando la secretaría registra la primera habilitación del consultor. Hasta ese momento, el campo aparece vacío.

**¿Puedo editar el Formulario RAI después de enviarlo?**  
No. Una vez enviado, el formulario queda bloqueado. Si necesita corregirlo, el técnico debe devolver el trámite con estado "Observado".

**¿Qué significa que un trámite esté en estado "Observado"?**  
El técnico detectó fallas o falta documentación. El consultor debe revisar las observaciones en **Mis Trámites**, corregir y volver a subir los documentos.

---

## PARTE IX — INFORMACIÓN TÉCNICA PARA ADMINISTRADORES

### Estructura de directorios

```
C:\xampp\htdocs\SMIA2\
├── api\                  → Endpoints JSON (notificaciones)
├── assets\css\           → Estilos del portal público
├── assets\js\            → Scripts del portal público
├── config\               → Configuración DB y sesiones
├── dashboard\            → Dashboards por rol
├── includes\             → Header y footer compartidos
├── modules\
│   ├── consultor\        → Módulos del consultor
│   ├── director\         → Módulos del director
│   ├── secretaria\       → Módulos de secretaría
│   └── tecnico\          → Módulos del técnico
├── tool\                 → Utilidades de administración
├── uploads\              → Archivos subidos por consultores
├── smiabasedata.sql      → Script de instalación de BD
├── index.php             → Portal público
├── login.php             → Inicio de sesión
├── logout.php            → Cierre de sesión
└── registro.php          → Auto-registro de consultores
```

### Base de datos — Tablas principales

| Tabla | Descripción |
|-------|-------------|
| roles | Roles del sistema con colores e iconos |
| usuarios | Todos los usuarios del sistema |
| sesiones | Control de sesiones únicas |
| hojas_de_ruta | Trámites HDR con su estado actual |
| formularios_rai | Formulario RAI por cada HDR |
| tipos_documento | Catálogo de tipos de documento requeridos |
| documentos | Archivos subidos con hash de integridad |
| habilitaciones_ambientales | Resultado final de cada trámite |
| historial_estados | Trazabilidad de todos los cambios de estado |
| notificaciones | Bandeja de notificaciones por usuario |
| turnos | Turnos de atención del técnico |
| recordatorios | Recordatorios con repetición |
| kpi_tecnicos | KPI mensual por técnico |
| auditoria | Registro de todas las acciones del sistema |

### Respaldo de datos (recomendado semanal)

```bash
# Exportar base de datos
C:\xampp\mysql\bin\mysqldump.exe -u root smiabasedata > backup_YYYYMMDD.sql

# Copiar archivos subidos
xcopy C:\xampp\htdocs\SMIA2\uploads\ D:\Backups\uploads\ /E /I
```

---

*SMIA2 v2.0 — Dirección de Saneamiento Básico, Recursos Hídricos y Control Ambiental*  
*Documento generado: Mayo 2026*
