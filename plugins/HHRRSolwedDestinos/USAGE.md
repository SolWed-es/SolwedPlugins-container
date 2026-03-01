# HHRRSolwedDestinos Plugin for FacturaScripts

Este plugin extiende FacturaScripts con funcionalidades avanzadas de recursos humanos, enfocándose en la gestión de turnos de trabajo de empleados.

## Funcionalidades Principales

### 1. Gestión de Turnos
- **ListShift**: Administración de turnos disponibles
- **EditShift**: Creación y edición de turnos
- **AssignShift**: Asignación de turnos a empleados

### 2. Gestión de Asignaciones de Turnos
- **ListEmployeeShift**: Lista de todas las asignaciones de turnos
- **EditEmployeeShift**: Edición de asignaciones individuales
- **MyShifts**: Vista de calendario para empleados

### 3. Modelos de Datos

#### Shift (Turno)
- Ubicación del turno
- Número de turno
- Horarios de entrada y salida
- Días de la semana activos

#### EmployeeShift (Asignación de Turno)
- Relación empleado-turno
- Fechas de inicio y fin
- Estado activo/inactivo
- Notas adicionales

## Uso del Plugin

### Para Administradores

1. **Crear Turnos**:
   - Ir a "Recursos Humanos" > "Turnos"
   - Definir ubicación, número y horarios
   - Especificar días de la semana

2. **Asignar Turnos a Empleados**:
   - Ir a "Recursos Humanos" > "Asignaciones de Turnos"
   - Crear nueva asignación
   - Seleccionar empleado y turno
   - Definir fechas de vigencia

3. **Gestionar Asignaciones**:
   - Ver todas las asignaciones en la lista
   - Filtrar por empleado, turno o fechas
   - Activar/desactivar asignaciones masivamente

### Para Empleados

1. **Ver Mis Turnos**:
   - Acceder a "Mis Turnos" desde el menú
   - Ver calendario mensual con turnos asignados
   - Navegar entre meses
   - Ver detalles de cada turno

## Base de Datos

### Tabla: rrhh_shifts
- `id`: Identificador único
- `location`: Ubicación del turno
- `shift_number`: Número del turno
- `entry_time`: Hora de entrada
- `exit_time`: Hora de salida
- `monday`, `tuesday`, etc.: Días activos
- `active`: Estado del turno

### Tabla: rrhh_employeesshifts
- `id`: Identificador único
- `idemployee`: FK a empleado
- `idshift`: FK a turno
- `assignment_date`: Fecha de asignación
- `start_date`: Fecha de inicio
- `end_date`: Fecha de fin
- `active`: Estado de la asignación
- `notes`: Notas adicionales

## Permisos

El plugin utiliza el sistema de permisos de FacturaScripts:
- **Administradores**: Acceso completo a todas las funcionalidades
- **Empleados**: Solo acceso a "Mis Turnos"

## Soporte Multi-idioma

El plugin incluye traducciones en:
- Español (es_ES)
- Inglés (en_EN)

## Instalación

1. Copiar el plugin a la carpeta `Plugins/HHRRSolwedDestinos`
2. Activar el plugin desde el panel de administración
3. El plugin creará automáticamente las tablas necesarias

## Dependencias

- Plugin HumanResources (para el modelo Employee)
- FacturaScripts 2022 o superior

## Licencia

LGPL-3.0 - Ver archivo LICENSE para más detalles.