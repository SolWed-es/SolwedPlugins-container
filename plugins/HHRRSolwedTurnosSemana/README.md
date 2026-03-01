# HHRRSolwedTurnosSemana Plugin para FacturaScripts

![FacturaScripts](https://img.shields.io/badge/FacturaScripts-2024-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)
![License](https://img.shields.io/badge/License-LGPL--3.0-green.svg)
![Version](https://img.shields.io/badge/Version-1.3.0-orange.svg)

## 📋 Descripción General

**HHRRSolwedTurnosSemana** es un plugin avanzado para FacturaScripts que extiende las funcionalidades de gestión de recursos humanos, específicamente enfocado en la **gestión de turnos de trabajo** para empleados con soporte para horarios organizados por días de la semana.

> Desarrollado por **José Ferrán** basándose en el excelente trabajo del plugin **Human Resources** creado por **Carlos García Gómez** y **Jose Antonio Cuello Principal**.

## 🎯 Características Principales

### Gestión de Turnos
- ✅ **Creación y edición de turnos** con ubicación y número identificador
- ✅ **Horarios configurables por día de la semana** (lunes a domingo)
- ✅ **Turnos autoasignables** para selección por empleados
- ✅ **Control de fechas** de inicio y fin de asignaciones
- ✅ **Sistema de activación/desactivación** de turnos
- ✅ **Gestión de ubicaciones** de trabajo
- ✅ **Notas adicionales** para cada turno

### Asignación de Turnos
- ✅ **Asignación manual** por administradores y gestores RRHH
- ✅ **Auto-asignación** por empleados (turnos marcados como autoasignables)
- ✅ **Validación de solapamientos** automática
- ✅ **Control de vigencia** mediante fechas de inicio y fin
- ✅ **Estados activo/inactivo** para cada asignación

### Interfaz de Usuario
- ✅ **Calendario visual mensual** para empleados
- ✅ **Panel administrativo** completo con filtros avanzados
- ✅ **Vista responsive** adaptada a dispositivos móviles
- ✅ **Autocompletado** de empleados en formularios
- ✅ **Navegación intuitiva** por meses
- ✅ **Integración con panel de empleados** para registro de asistencias

### Sistema Multiidioma
- ✅ **Español (es_ES)** - Idioma principal
- ✅ **Inglés (en_EN)** - Idioma secundario
- ✅ **Sistema extensible** para agregar más idiomas

### Integración con FacturaScripts
- ✅ **Menú RRHH** integrado en el dashboard principal
- ✅ **Permisos por roles** (employee, hr_manager, admin)
- ✅ **Base de datos optimizada** con relaciones apropiadas
- ✅ **Extensiones del core** de FacturaScripts

## 🛠 Funcionalidad del selector de turnos autoasignables

Esta funcionalidad permite a los empleados seleccionar un turno de trabajo autoasignable directamente desde su panel de empleado.

### Descripción

- Se extiende el controlador original del panel de empleados para cargar los turnos que están marcados como autoasignables en la base de datos.
- Los turnos autoasignables se filtran mediante el campo `autoassignable` en la tabla de turnos.
- La lista de turnos se expone a la plantilla Twig como la variable `fsc.shifts`.
- En la plantilla Twig del panel de asistencias del empleado, se muestra un selector desplegable que lista estos turnos.
- El modal para la entrada manual de asistencias también utiliza esta lista para permitir seleccionar un turno.

### Archivos implicados

- `Controller/EmployeePanel.php`: Controlador extendido que carga los turnos autoasignables y los expone a la vista.
- `View/Tab/EmployeePanelAttendances.html.twig`: Plantilla Twig que muestra el selector de turnos y el modal de entrada manual.
- `Init.php`: Archivo de inicialización del plugin que asegura la correcta carga y desinstalación del plugin.
- `test_shifts.php`: Script de prueba para verificar la carga correcta de los turnos desde la base de datos.

Esta funcionalidad mejora la experiencia del empleado al facilitar la selección de turnos disponibles y la gestión de sus asistencias.

---

C
## 🛠 Solución para el problema del selector de turnos autoasignables vacío

En versiones anteriores, el selector de turnos autoasignables en el panel de empleados no mostraba los turnos correctamente debido a una extensión incorrecta del controlador original.

### Problema identificado

- La extensión del controlador `EmployeePanel` se implementaba usando un Closure en el método `privateCore`, lo cual no es compatible con FacturaScripts.
- Esto impedía que la variable `shifts` con los turnos autoasignables se pasara correctamente a la plantilla Twig.

### Solución implementada

- Se eliminó la extensión basada en Closure.
- Se creó un controlador `EmployeePanel` que extiende el controlador original de HumanResources.
- Se sobrescribió el método `privateCore` para cargar los turnos autoasignables desde la base de datos usando `DataBaseWhere('autoassignable', true)`.
- Se expuso la variable `$this->shifts` para que esté disponible en la plantilla Twig como `fsc.shifts`.
- Se simplificaron las plantillas Twig para usar directamente `fsc.shifts` en el selector de turnos.
- Se añadió el método `uninstall()` en la clase `Init` para cumplir con la interfaz requerida.

### Beneficios

- El selector de turnos autoasignables ahora muestra correctamente los turnos disponibles.
- La solución sigue las mejores prácticas de FacturaScripts para extender controladores.
- El código es más mantenible y fácil de depurar.

### Archivos modificados

- `Controller/EmployeePanel.php`: Nuevo controlador extendido con carga de turnos.
- `View/Tab/EmployeePanelAttendances.html.twig`: Simplificación para usar `fsc.shifts`.
- `Init.php`: Añadido método `uninstall()`.

Esta solución mejora la experiencia del usuario y la integración del plugin con FacturaScripts.

---





## 🚀 Instalación y Configuración

### Requisitos Previos
- **FacturaScripts** 2024.3 o superior
- **PHP** 8.0 o superior
- **Plugin Human Resources** instalado y configurado
- **Base de datos** MySQL/PostgreSQL

### Pasos de Instalación

#### 1. Descargar el Plugin
```bash
cd /ruta/a/facturascripts/Plugins/
git clone [URL_DEL_REPOSITORIO] HHRRSolwedTurnosSemana
```

#### 2. Activar el Plugin
1. Acceder al panel de administración de FacturaScripts
2. Ir a **Admin > Plugins**
3. Buscar **HHRRSolwedTurnosSemana**
4. Hacer clic en **Activar**

#### 3. Configurar Permisos
1. Asignar permisos de RRHH a los usuarios administradores
2. Los empleados automáticamente tendrán acceso a su vista de turnos

## 📊 Arquitectura Técnica

### Estructura de Base de Datos

#### Tabla: `rrhh_shifts` (Turnos)
| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | SERIAL | Clave primaria | NOT NULL |
| `location` | VARCHAR(100) | Ubicación del turno | NOT NULL |
| `shift_number` | INTEGER | Número identificador del turno | NOT NULL, DEFAULT 1 |
| `autoassignable` | BOOLEAN | Turno autoasignable por empleados | NOT NULL, DEFAULT false |
| `notes` | TEXT | Notas adicionales | NULL |
| `creation_date` | TIMESTAMP | Fecha de creación | NULL |
| `last_nick` | VARCHAR(50) | Último usuario que modificó | NULL |
| `last_update` | TIMESTAMP | Última actualización | NULL |
| `nick` | VARCHAR(50) | Usuario que creó | NULL |

**Restricciones:**
- **PRIMARY KEY**: `id`
- **UNIQUE**: `(location, shift_number)` - Evita duplicados

#### Tabla: `rrhh_employeesshifts` (Asignaciones)
| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | SERIAL | Clave primaria | NOT NULL |
| `idemployee` | INTEGER | ID del empleado (FK) | NOT NULL |
| `idshift` | INTEGER | ID del turno (FK) | NOT NULL |
| `assignment_date` | DATE | Fecha de asignación | NOT NULL, DEFAULT CURRENT_DATE |
| `start_date` | DATE | Fecha de inicio del turno | NULL |
| `end_date` | DATE | Fecha de fin del turno | NULL |
| `notes` | TEXT | Notas adicionales | NULL |
| `active` | BOOLEAN | Estado activo/inactivo | NOT NULL, DEFAULT TRUE |
| `autoassignable` | BOOLEAN | Asignación autoasignable | NOT NULL, DEFAULT TRUE |

**Restricciones:**
- **PRIMARY KEY**: `id`
- **FOREIGN KEY**: `idemployee` → `rrhh_employees.id` ON DELETE CASCADE
- **FOREIGN KEY**: `idshift` → `rrhh_shifts.id` ON DELETE CASCADE
- **CHECK**: `end_date IS NULL OR start_date IS NULL OR end_date >= start_date`

#### Tabla: `rrhh_shift_week_days` (Horarios por Día)
| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | SERIAL | Clave primaria | NOT NULL |
| `idshift` | INTEGER | ID del turno (FK) | NOT NULL |
| `dayofweek` | INTEGER | Día de la semana (1=Lunes, 7=Domingo) | NOT NULL |
| `morning_entry` | TIME | Hora de entrada mañana | NULL |
| `morning_exit` | TIME | Hora de salida mañana | NULL |
| `afternoon_entry` | TIME | Hora de entrada tarde | NULL |
| `afternoon_exit` | TIME | Hora de salida tarde | NULL |

**Restricciones:**
- **PRIMARY KEY**: `id`
- **UNIQUE**: `(idshift, dayofweek)` - Un único registro por turno y día
- **FOREIGN KEY**: `idshift` → `rrhh_shifts.id` ON DELETE CASCADE

### Estructura de Archivos

```
HHRRSolwedTurnosSemana/
├── Controller/
│   ├── AssignShift.php          # Asignación de turnos
│   ├── EditEmployeeShift.php    # Edición de asignaciones
│   ├── EditShift.php            # Edición de turnos
│   ├── EditShiftWeekDay.php     # Edición de horarios por día
│   ├── EmployeePanel.php        # Panel de empleados
│   ├── ListEmployeeShift.php    # Lista de asignaciones (admin)
│   ├── ListShift.php            # Lista de turnos (admin)
│   ├── ListShiftWeekDay.php     # Lista de horarios por día
│   └── MyShifts.php             # Calendario de empleados
├── Data/
│   ├── Lang/
│   │   ├── es_ES.json           # Traducciones español
│   │   └── en_EN.json           # Traducciones inglés
│   └── permissions.xml          # Configuración de permisos
├── Extension/
│   ├── Controller/
│   │   └── Dashboard.php        # Extensión del dashboard
│   ├── Model/
│   │   └── Contacto.php         # Extensión modelo contacto
│   └── XMLView/
│       └── EditShift.xml        # Vista edición turnos extendida
├── Model/
│   ├── EmployeeShift.php        # Modelo de asignaciones
│   ├── Shift.php                # Modelo de turnos
│   └── ShiftWeekDay.php         # Modelo de horarios por día
├── Table/
│   ├── rrhh_shifts.xml          # Definición tabla turnos
│   ├── rrhh_employeesshifts.xml # Definición tabla asignaciones
│   └── rrhh_shift_week_days.xml # Definición tabla horarios
├── XMLView/
│   ├── EditEmployeeShift.xml    # Vista edición asignaciones
│   ├── EditShift.xml            # Vista edición turnos
│   ├── EditShiftWeekDay.xml     # Vista edición horarios por día
│   ├── ListEmployeeShift.xml    # Vista lista asignaciones
│   ├── ListShift.xml            # Vista lista turnos
│   ├── ListShiftWeekDay.xml     # Vista lista horarios por día
│   └── MyShifts.xml             # Vista mis turnos
├── Init.php                     # Inicialización del plugin
├── facturascripts.ini           # Configuración del plugin
└── README.md                    # Este archivo
```

## 🎮 Guía de Uso

### Para Empleados

#### Acceso al Calendario de Turnos
1. **Iniciar sesión** en FacturaScripts
2. En el menú principal, ir a **RRHH > Mis Turnos**
3. **Visualizar** el calendario mensual con turnos asignados

#### Navegación del Calendario
- **Mes Anterior/Siguiente**: Botones de navegación en la parte superior
- **Vista de turnos**: Cada día muestra los turnos asignados con colores distintivos
- **Detalles**: Pasa el cursor sobre los turnos para ver información completa
- **Tabla de detalles**: Lista completa de turnos del mes debajo del calendario

#### Registro de Asistencias con Turno
1. **Acceder** al **Panel de Empleados** desde el dashboard
2. **Seleccionar turno**: Dropdown con turnos autoasignables disponibles
3. **Registrar asistencia**: El sistema asociará la asistencia con el turno seleccionado

### Para Administradores y Gestores RRHH

#### Gestión de Turnos
1. **Acceder** a **RRHH > Turnos**
2. **Crear nuevo turno**:
   - Definir **ubicación** y **número de turno**
   - Establecer **horarios** para cada día de la semana
   - Configurar **días activos** de la semana
   - Marcar como **autoasignable** si permite selección por empleados
   - Agregar **notas** descriptivas

#### Gestión de Asignaciones
1. **Acceder** a **RRHH > Turnos de Empleados**
2. **Crear nueva asignación**:
   - **Seleccionar empleado** (autocompletado disponible)
   - **Seleccionar turno** de la lista disponible
   - **Establecer fechas** de inicio y fin de la asignación
   - **Configurar autoasignación** (permite que el empleado se autoasigne)
   - **Agregar notas** adicionales si es necesario

#### Filtros y Búsquedas Avanzadas
- **Por empleado**: Autocompletado inteligente
- **Por turno**: Selector desplegable
- **Por ubicación**: Filtro de texto libre
- **Por rango de fechas**: Selectores de fecha
- **Por estado**: Activo/Inactivo
- **Por autoasignable**: Filtro booleano

## 🔧 Configuración y Personalización

### Sistema de Permisos

#### Roles y Permisos

**Empleado (`employee`)**
- **MyShifts**: Solo lectura (ver sus propios turnos)
- **Sin permisos de gestión**

**Gestor RRHH (`hr_manager`)**
- **MyShifts**: Lectura
- **ListShift, EditShift**: Lectura, escritura, eliminación
- **ListEmployeeShift, EditEmployeeShift**: Lectura, escritura, eliminación
- **AssignShift**: Lectura, escritura

**Administrador (`admin`)**
- **Todos los permisos** de gestor RRHH
- **Permisos adicionales** de eliminación en AssignShift
- **Control total** sobre todas las funcionalidades

### Validaciones del Sistema

#### Validaciones de Turnos
- ✅ **Ubicación requerida**: No puede estar vacía
- ✅ **Número de turno requerido**: Debe ser un entero válido
- ✅ **Horarios requeridos**: Entrada y salida obligatorios para días activos
- ✅ **Formato de tiempo**: Validación HH:MM:SS o HH:MM
- ✅ **Unicidad**: Combinación ubicación + número debe ser única

#### Validaciones de Asignaciones
- ✅ **Empleado requerido**: Debe existir en la base de datos
- ✅ **Turno requerido**: Debe existir en la base de datos
- ✅ **Fecha de asignación**: Formato de fecha válido
- ✅ **Orden de fechas**: Fecha fin ≥ fecha inicio
- ✅ **Solapamientos**: No permite asignaciones solapadas para el mismo empleado

### Personalización de Vistas

#### Modificar Colores del Calendario
Editar el archivo `View/MyShifts.html.twig`:
```css
.calendar-day.today {
    background-color: #e3f2fd !important;
    border: 2px solid #2196f3 !important;
}

.badge-success {
    background-color: #28a745;
}
```

#### Agregar Campos Personalizados
1. **Modificar tabla**: Editar archivos XML en `Table/`
2. **Actualizar modelo**: Modificar archivos PHP en `Model/`
3. **Actualizar vistas**: Editar archivos XML en `XMLView/`

### Traducciones Personalizadas

#### Agregar Nuevo Idioma
1. **Crear archivo**: `Data/Lang/[codigo_idioma].json`
2. **Copiar estructura** de `es_ES.json`
3. **Traducir textos** al idioma deseado

#### Ejemplo para Francés (fr_FR):
```json
{
    "my-shifts": "Mes Équipes",
    "previous-month": "Mois Précédent",
    "next-month": "Mois Suivant",
    "shift": "Équipe",
    "location": "Emplacement",
    "autoassignable": "Auto-assignable"
}
```

## 🛠️ Desarrollo y Extensión

### API y Métodos Principales

#### MyShifts Controller
```php
// Obtener empleado del usuario actual
$employee = $this->getEmployeeFromUser($user);

// Obtener turnos del empleado
$shifts = $this->getEmployeeShifts();

// Obtener datos del calendario
$calendar = $this->getCalendarData();

// Obtener traducciones
$translations = $this->getTranslations();
```

#### EmployeeShift Model
```php
// Validar datos de la asignación
$assignment->test();

// Obtener asignaciones por empleado
$assignments = $assignment->all([
    new DataBaseWhere('idemployee', $employeeId)
]);

// Verificar solapamientos
$hasOverlap = $assignment->checkOverlappingAssignments();

// Verificar si está activa
$isActive = $assignment->isActive();
```

#### Shift Model
```php
// Validar datos del turno
$shift->test();

// Obtener turnos por ubicación
$shifts = $shift->all([
    new DataBaseWhere('location', $location)
]);

// Validar formato de tiempo
$isValid = $shift->validateTimeFormat($time);
```

### Agregar Nuevas Funcionalidades

#### Ejemplo: Agregar Campo "Supervisor"
1. **Actualizar tabla**:
```xml
<column>
    <name>supervisor</name>
    <type>character varying(100)</type>
    <null>YES</null>
</column>
```

2. **Actualizar modelo**:
```php
/**
 * Supervisor del turno
 *
 * @var string
 */
public $supervisor;
```

3. **Actualizar vistas XML**:
```xml
<column name="supervisor" display="left" order="110">
    <widget type="text" fieldname="supervisor" />
</column>
```

## 🐛 Solución de Problemas

### Problemas Comunes y Soluciones

| Problema | Causa | Solución |
|----------|--------|----------|
| **"Employee not found"** | Usuario no asociado a empleado | Ir a RRHH > Empleados y asociar usuario |
| **"Permission denied"** | Sin permisos de RRHH | Ir a Admin > Usuarios y asignar permisos |
| **Calendario sin turnos** | Turnos inactivos o fechas incorrectas | Verificar estado activo y rangos de fechas |
| **"Overlapping assignments"** | Asignaciones solapadas | Ajustar fechas para evitar conflictos |
| **Traducciones no funcionan** | Archivos de idioma corruptos | Verificar sintaxis JSON y reiniciar caché |

### Logs y Debugging

#### Activar Logs de Debug
En `config.php`:
```php
define('FS_DEBUG', true);
define('FS_DB_HISTORY', true);
```

#### Ubicación de Logs
- **Logs generales**: `MyFiles/Logs/`
- **Logs de base de datos**: Panel de administración > Logs

#### Debugging del Plugin
El plugin incluye logging en puntos clave:
```php
// En EmployeePanel extension
Tools::log()->warning('Loading shifts for employee panel');
Tools::log()->info('Found ' . count($shifts) . ' auto-assignable shifts');
```

## 📄 Licencia y Términos

### Licencia
Este proyecto está licenciado bajo la **GNU Lesser General Public License v3.0** - ver el archivo [LICENSE](LICENSE) para más detalles.

### Términos de Uso
- ✅ **Uso comercial** permitido
- ✅ **Modificación** permitida
- ✅ **Distribución** permitida
- ✅ **Uso privado** permitido
- ❗ **Responsabilidad limitada**
- ❗ **Sin garantía**

## 📋 Historial de Versiones (Changelog)

### [1.3.0] - 2024-12-19
#### ✨ Nuevas Funcionalidades
- **Gestión completa de turnos semanales** con horarios por día de la semana
- **Asignación de empleados a turnos** con validación de fechas
- **Calendario visual de turnos** para empleados
- **Sistema de permisos por roles** (employee, hr_manager, admin)

#### 🔧 Cambios Técnicos
- Modelo `ShiftWeekDay` para horarios por día de la semana
- Relaciones optimizadas entre turnos y empleados
- Vistas XML mejoradas para gestión de turnos
- Nuevos índices para optimizar consultas

### [1.2.1] - 2024-12-19
#### 🔧 Correcciones de Bugs
- Corregido namespace duplicado en controladores
- Mejorada validación de datos en formularios
- Optimizadas consultas de base de datos

### [1.2.0] - 2024-12-19
#### ✨ Nuevas Funcionalidades
- **Sistema de turnos autoasignables** para empleados
- **Gestión de horarios por día de la semana**
- **Panel de empleado mejorado** con vista de turnos asignados

### [1.1.0] - 2024-12-19
#### ✨ Nuevas Funcionalidades
- **Campo Autoasignable** en modelo EmployeeShift
- **Gestión de Autoasignación** para empleados
- **Filtros Avanzados** por autoasignables
- **Interfaz de Usuario** mejorada con checkbox

### [1.0.0] - 2024-12-XX
#### ✅ Funcionalidades Iniciales
- Gestión completa de turnos y asignaciones
- Calendario visual para empleados
- Panel administrativo con filtros
- Sistema multiidioma
- Validaciones completas
- Control de permisos por roles
- Integración con Human Resources

## 🤝 Contribución y Soporte

### Cómo Contribuir
1. **Fork** del repositorio
2. **Crear rama** para nueva funcionalidad: `git checkout -b feature/nueva-funcionalidad`
3. **Realizar cambios** y commit: `git commit -am 'Agregar nueva funcionalidad'`
4. **Push** a la rama: `git push origin feature/nueva-funcionalidad`
5. **Crear Pull Request**

### Estándares de Código
- **PSR-12** para PHP
- **Comentarios** en español
- **Variables** en inglés
- **Métodos** documentados con PHPDoc
- **Validaciones** en todos los modelos
- **Logging** para debugging

### Reportar Problemas
- **GitHub Issues**: [Crear nuevo issue](https://github.com/[usuario]/HHRRSolwedTurnosSemana/issues)
- **Descripción detallada** del problema
- **Pasos para reproducir** el error
- **Versión** de FacturaScripts y PHP
- **Logs** relevantes del sistema

### Soporte Comercial
Para soporte comercial personalizado, contactar directamente con José Ferrán.

## 🚀 Roadmap y Futuras Funcionalidades

### Corto Plazo (3 meses)
- [ ] **Notificaciones** por email para cambios de turno
- [ ] **Reportes PDF** de turnos y asistencias
- [ ] **API REST** para integración externa
- [ ] **Validación avanzada** de horarios y solapamientos

### Medio Plazo (6 meses)
- [ ] **App Móvil** nativa para empleados
- [ ] **Geolocalización** para control de ubicación
- [ ] **Integraciones** con Slack y Microsoft Teams
- [ ] **Sincronización** con Google Calendar y Outlook

### Largo Plazo (12 meses)
- [ ] **Inteligencia Artificial** para sugerencias automáticas
- [ ] **Analytics avanzado** con dashboard de métricas
- [ ] **Multi-empresa** para gestión de varias empresas
- [ ] **Workflow** de aprobaciones para cambios de turno

## 📚 Documentación Adicional

### Enlaces Útiles
- [Documentación FacturaScripts](https://facturascripts.com/doc)
- [Plugin Human Resources Base](https://github.com/FacturaScripts/HumanResources)
- [Comunidad FacturaScripts](https://facturascripts.com/comunidad)

### Tutoriales y Recursos
- [Cómo crear un plugin para FacturaScripts](https://facturascripts.com/doc/developer-guide)
- [Guía de desarrollo de extensiones](https://facturascripts.com/doc/extensions)

### Documentación Técnica
- **Modelos de datos**: Documentación completa en archivos PHP
- **Estructura de base de datos**: Definiciones XML en directorio Table/
- **Vistas**: Documentación en archivos XMLView/
- **Traducciones**: Archivos JSON en Data/Lang/

---

## 👥 Créditos y Reconocimientos

### Desarrollador Principal
- **José Ferrán** - Desarrollo y mantenimiento del plugin

### Basado en el Trabajo de
- **Carlos García Gómez** - Creador del plugin Human Resources base
- **Jose Antonio Cuello Principal** - Co-desarrollador del plugin Human Resources base

### Agradecimientos Especiales
- **Comunidad FacturaScripts** - Por el framework y soporte continuo
- **Contribuidores** - Por mejoras y reportes de bugs
- **Usuarios** - Por feedback y sugerencias

---

**¡Gracias por usar HHRRSolwedTurnosSemana!** 🎉

Si este plugin te ha sido útil, considera:
- ⭐ **Dar una estrella** al repositorio
- 🐛 **Reportar bugs** que encuentres
- 💡 **Sugerir mejoras** y nuevas funcionalidades

Perfecto, Jose.
Voy a mantener la estructura de tu `README.md` tal como está, pero al final voy a añadir una sección **"🆕 Novedades y Cambios Recientes"** documentando todos los ajustes que hemos hecho juntos:

---

## 🆕 Novedades y Cambios Recientes

### Nuevas acciones en `execPreviousAction()`

Se han añadido dos acciones personalizadas que interceptan el flujo antes de llegar al controlador base de HumanResources:

1. **insert-attendance**

   * Captura datos del formulario para registrar asistencia con turno (`idturno`) y tipo (`kind`).
   * Corrige un problema por el cual las salidas se registraban como entradas, asegurando que el valor de `kind` se inyecta correctamente en el modelo `Attendance`.
   * Compatible con envío desde botones de entrada/salida y el modal de asistencia manual.
   * Usa el turno por defecto del empleado si no se selecciona uno en el formulario.

2. **set-default-shift**

   * Permite que el empleado marque un turno como predeterminado desde su panel.
   * Actualiza la tabla `rrhh_employeesshifts`, limpiando el valor `isdefault` de otros turnos y marcando el turno elegido como nuevo por defecto.
   * Se adapta la sintaxis SQL al sistema de base de datos de FacturaScripts usando `$this->dataBase->var2str()` para evitar inyecciones y errores de sintaxis.
   * Al recargar el panel, el turno por defecto aparece preseleccionado en el selector.

---

### Cambios en `EmployeePanel.js`

* Evita el apilado de campos ocultos (`hidden`) que provocaba que siempre se tomara el primer valor de `kind` enviado.
* Se implementa la función `upsertHidden` para reemplazar o actualizar campos ocultos.
* En el envío manual no se añade un `kind` extra desde JS, ya que el modal ya lo incluye en el formulario.

---

### Cambios en la plantilla `EmployeePanelAttendances.html.twig`

* Eliminada la estrella decorativa en los `<option>` del turno por defecto.
* Añadido botón junto al selector de turnos para marcar como predeterminado (`saveAsDefaultShift()`).
* Se muestra aviso con el turno por defecto configurado (`fsc.defaultShift`) si existe.

---

### Cambios en la base de datos

* Añadida la columna `isdefault` a `rrhh_employeesshifts` para identificar el turno por defecto de un empleado.
* Lógica ajustada para que el turno por defecto se use automáticamente en el registro de asistencia si no se selecciona uno manualmente.

---

### Depuración y Logs

* Añadidos logs para verificar la recepción de datos en `saveAttendanceWithShift()`.
* Log adicional para registrar el valor de `kind` recibido y evitar inconsistencias.

---


