# HHRRSolwedTurnos Plugin for FacturaScripts

![FacturaScripts](https://img.shields.io/badge/FacturaScripts-2024-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)
![License](https://img.shields.io/badge/License-LGPL--3.0-green.svg)
![Version](https://img.shields.io/badge/Version-1.0.0-orange.svg)

## 📋 Descripción

**HHRRSolwedTurnos** es un plugin avanzado para FacturaScripts que extiende las funcionalidades de gestión de recursos humanos, específicamente enfocado en la **gestión de turnos de trabajo** para empleados.

Este plugin ha sido desarrollado por **José Ferrán** basándose en el excelente trabajo del plugin **Human Resources** creado por **Carlos García Gómez** y **Jose Antonio Cuello Principal**.

## 🎯 Características Principales

### ✅ **Gestión de Turnos**
- **Asignación de turnos** a empleados por parte de administradores
- **Calendario visual** para empleados con vista mensual de sus turnos
- **Gestión de ubicaciones** de trabajo y números de turno
- **Control de fechas** de inicio y fin de asignaciones
- **Sistema de activación/desactivación** de turnos

### ✅ **Interfaz de Usuario**
- **Vista de calendario** moderna y responsive para empleados
- **Panel administrativo** completo para gestión de turnos
- **Filtros avanzados** por empleado, fecha, ubicación y estado
- **Autocompletado** de empleados en formularios
- **Navegación mensual** intuitiva en el calendario

### ✅ **Sistema Multiidioma**
- **Español (es_ES)** - Idioma principal
- **Inglés (en_EN)** - Idioma secundario
- **Sistema extensible** para agregar más idiomas

### ✅ **Integración con FacturaScripts**
- **Menú RRHH** integrado en el dashboard
- **Permisos de usuario** respetados
- **Base de datos** optimizada con relaciones apropiadas
- **Extensiones** del core de FacturaScripts

## 🚀 Instalación

### Requisitos Previos
- **FacturaScripts** 2024 o superior
- **PHP** 8.0 o superior
- **Plugin Human Resources** instalado y configurado
- **Base de datos** MySQL/PostgreSQL

### Pasos de Instalación

1. **Descargar el plugin**
   ```bash
   cd /ruta/a/facturascripts/Plugins/
   git clone [URL_DEL_REPOSITORIO] HHRRSolwedTurnos
   ```

2. **Activar el plugin**
   - Acceder al panel de administración de FacturaScripts
   - Ir a **Admin > Plugins**
   - Buscar **HHRRSolwedTurnos**
   - Hacer clic en **Activar**

3. **Configurar permisos**
   - Asignar permisos de RRHH a los usuarios administradores
   - Los empleados automáticamente tendrán acceso a su vista de turnos

## 📊 Estructura de Base de Datos

### Tabla: `rrhh_employeesshifts`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | SERIAL | Clave primaria |
| `codemployee` | VARCHAR(10) | Código del empleado (FK) |
| `shift_location` | VARCHAR(100) | Ubicación del turno |
| `shift_number` | INTEGER | Número identificador del turno |
| `assignment_date` | DATE | Fecha de asignación |
| `start_date` | DATE | Fecha de inicio del turno |
| `end_date` | DATE | Fecha de fin del turno |
| `active` | BOOLEAN | Estado activo/inactivo |
| `notes` | TEXT | Notas adicionales |

### Relaciones
- **FK**: `codemployee` → `rrhh_employees.codemployee`
- **Índices**: Optimizados para consultas por empleado y fecha

## 🎮 Uso del Plugin

### Para Empleados

#### Acceso al Calendario
1. **Iniciar sesión** en FacturaScripts
2. En el menú principal, ir a **RRHH > Mis Turnos**
3. **Visualizar** el calendario mensual con turnos asignados

#### Navegación del Calendario
- **Mes Anterior/Siguiente**: Botones de navegación
- **Vista de turnos**: Cada día muestra turnos asignados
- **Detalles**: Hover sobre turnos para ver información completa
- **Tabla de detalles**: Lista completa de turnos debajo del calendario

### Para Administradores

#### Gestión de Turnos
1. **Acceder** a **RRHH > Turnos de Empleados**
2. **Crear nuevo turno**:
   - Seleccionar empleado
   - Definir ubicación y número de turno
   - Establecer fechas de inicio y fin
   - Agregar notas si es necesario

#### Filtros Disponibles
- **Por empleado**: Autocompletado de empleados
- **Por ubicación**: Filtro de texto
- **Por fecha**: Selector de fechas
- **Por estado**: Activo/Inactivo

#### Acciones Masivas
- **Activar** múltiples turnos
- **Desactivar** múltiples turnos
- **Exportar** datos a Excel/CSV

## 🔧 Configuración Avanzada

### Personalización de Vistas

#### Modificar Colores del Calendario
Editar el archivo `View/MyShifts.html.twig`:
```css
.calendar-day.today {
    background-color: #e3f2fd !important; /* Color para día actual */
    border: 2px solid #2196f3 !important;
}

.badge-success {
    background-color: #28a745; /* Color para turnos */
}
```

#### Agregar Campos Personalizados
1. **Modificar tabla**: Editar `Table/rrhh_employeesshifts.xml`
2. **Actualizar modelo**: Modificar `Model/EmployeeShift.php`
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
    ...
}
```

## 🛠️ Desarrollo y Extensión

### Estructura de Archivos

```
HHRRSolwedTurnos/
├── Controller/
│   ├── EditEmployeeShift.php    # Edición de turnos
│   ├── ListEmployeeShift.php    # Lista de turnos (admin)
│   └── MyShifts.php             # Calendario de empleados
├── Data/
│   └── Lang/
│       ├── es_ES.json           # Traducciones español
│       └── en_EN.json           # Traducciones inglés
├── Extension/
│   └── Controller/
│       ├── Dashboard.php        # Extensión del dashboard
│       └── EditEmployee.php     # Extensión de empleados
├── Model/
│   └── EmployeeShift.php        # Modelo de turnos
├── Table/
│   └── rrhh_employeesshifts.xml # Definición de tabla
├── View/
│   └── MyShifts.html.twig       # Plantilla del calendario
├── XMLView/
│   ├── EditEmployeeShift.xml    # Vista de edición
│   └── ListEmployeeShift.xml    # Vista de lista
├── Init.php                     # Inicialización del plugin
└── README.md                    # Este archivo
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
public $supervisor;
```

3. **Actualizar vistas XML**:
```xml
<column name="supervisor" display="left" order="110">
    <widget type="text" fieldname="supervisor" />
</column>
```

### API y Métodos Principales

#### MyShifts Controller
```php
// Obtener turnos del empleado actual
$shifts = $this->getEmployeeShifts();

// Obtener datos del calendario
$calendar = $this->getCalendarData();

// Obtener datos de navegación
$navigation = $this->getNavigationData();

// Obtener traducciones
$translations = $this->getTranslations();
```

#### EmployeeShift Model
```php
// Validar datos del turno
$shift->test();

// Obtener turnos por empleado
$shifts = $shift->all([new DataBaseWhere('codemployee', $employee)]);

// Verificar solapamientos
$hasOverlap = $shift->hasOverlappingShifts();
```

## 🐛 Solución de Problemas

### Problemas Comunes

#### Error: "Employee not found"
**Causa**: El usuario no está asociado a un empleado
**Solución**: 
1. Ir a **RRHH > Empleados**
2. Editar el empleado correspondiente
3. Asociar el usuario en el campo "Usuario"

#### Error: "Permission denied"
**Causa**: El usuario no tiene permisos de RRHH
**Solución**:
1. Ir a **Admin > Usuarios**
2. Editar el usuario
3. Marcar "Administrador" o asignar permisos específicos

#### Calendario no muestra turnos
**Causa**: Turnos inactivos o fechas incorrectas
**Solución**:
1. Verificar que los turnos estén **activos**
2. Comprobar que las fechas estén en el rango correcto
3. Revisar que `start_date` ≤ fecha actual ≤ `end_date`

#### Traducciones no funcionan
**Causa**: Archivos de idioma corruptos o faltantes
**Solución**:
1. Verificar archivos en `Data/Lang/`
2. Comprobar sintaxis JSON válida
3. Reiniciar caché de FacturaScripts

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

## 🤝 Contribución

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

### Testing
```bash
# Ejecutar tests (cuando estén disponibles)
php vendor/bin/phpunit tests/
```

## 📄 Licencia

Este proyecto está licenciado bajo la **GNU Lesser General Public License v3.0** - ver el archivo [LICENSE](LICENSE) para más detalles.

### Términos de Uso
- ✅ **Uso comercial** permitido
- ✅ **Modificación** permitida
- ✅ **Distribución** permitida
- ✅ **Uso privado** permitido
- ❗ **Responsabilidad limitada**
- ❗ **Sin garantía**

## 👥 Créditos y Reconocimientos

### Desarrollador Principal
- **José Ferrán** - Desarrollo y mantenimiento del plugin HHRRSolwedTurnos

### Basado en el Trabajo de
- **Carlos García Gómez** - Creador del plugin Human Resources base
- **Jose Antonio Cuello Principal** - Co-desarrollador del plugin Human Resources base

### Agradecimientos Especiales
- **Comunidad FacturaScripts** - Por el framework y soporte continuo
- **Contribuidores** - Por mejoras y reportes de bugs
- **Usuarios** - Por feedback y sugerencias

## 📞 Soporte y Contacto

### Reportar Bugs
- **GitHub Issues**: [Crear nuevo issue](https://github.com/[usuario]/HHRRSolwedTurnos/issues)
- **Descripción detallada** del problema
- **Pasos para reproducir** el error
- **Versión** de FacturaScripts y PHP

### Solicitar Funcionalidades
- **GitHub Discussions**: Para propuestas de nuevas características
- **Documentación clara** de la funcionalidad deseada
- **Casos de uso** específicos

### Soporte Comercial
Para soporte comercial personalizado, contactar directamente con José Ferrán.

## 🔄 Changelog

### Versión 1.0.0 (2024-12-XX)
- ✅ **Inicial**: Lanzamiento inicial del plugin
- ✅ **Calendario**: Vista de calendario para empleados
- ✅ **Gestión**: Panel administrativo de turnos
- ✅ **Multiidioma**: Soporte para español e inglés
- ✅ **Responsive**: Diseño adaptativo para móviles

### Próximas Versiones
- 🔄 **1.1.0**: Notificaciones por email
- 🔄 **1.2.0**: Exportación avanzada de reportes
- 🔄 **1.3.0**: Integración con calendario externo (Google Calendar, Outlook)

## 🚀 Roadmap

### Corto Plazo (3 meses)
- [ ] **Notificaciones**: Sistema de alertas por email
- [ ] **Reportes**: Generación de reportes PDF
- [ ] **API REST**: Endpoints para integración externa

### Medio Plazo (6 meses)
- [ ] **App Móvil**: Aplicación móvil nativa
- [ ] **Geolocalización**: Control de ubicación para turnos
- [ ] **Integraciones**: Slack, Microsoft Teams

### Largo Plazo (12 meses)
- [ ] **IA**: Sugerencias automáticas de turnos
- [ ] **Analytics**: Dashboard de métricas avanzadas
- [ ] **Multi-empresa**: Soporte para múltiples empresas

---

## 📚 Documentación Adicional

### Enlaces Útiles
- [Documentación FacturaScripts](https://facturascripts.com/doc)
- [Plugin Human Resources Base](https://github.com/FacturaScripts/HumanResources)
- [Comunidad FacturaScripts](https://facturascripts.com/comunidad)

### Tutoriales
- [Cómo crear un plugin para FacturaScripts](https://facturascripts.com/doc/developer-guide)
- [Guía de desarrollo de extensiones](https://facturascripts.com/doc/extensions)

---

**¡Gracias por usar HHRRSolwedTurnos!** 🎉

Si este plugin te ha sido útil, considera:
- ⭐ **Dar una estrella** al repositorio
- 🐛 **Reportar bugs** que encuentres
- 💡 **Sugerir mejoras**
- 🤝 **Contribuir** con código
- 📢 **Recomendar** a otros usuarios

*Desarrollado con ❤️ por José Ferrán basado en el excelente trabajo de Carlos García Gómez y Jose Antonio Cuello Principal*