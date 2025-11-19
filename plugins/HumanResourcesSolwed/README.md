# HumanResourcesSolwed

Extensión oficial para FacturaScripts que amplía el plugin **HumanResources** con mejoras enfocadas en usabilidad de empleados, visibilidad de vacaciones, gestión documental y control de nóminas. Este documento resume todas las funcionalidades entregadas y sirve como referencia para nuevos desarrolladores.

---

## Requisitos

- FacturaScripts ≥ 2025.43.
- Plugin **HumanResources** activo.
- PHP ≥ 8.0.

---

## Instalación

1. Copiar el directorio `HumanResourcesSolwed` dentro de `Plugins/`.
2. Activar el plugin desde *Admin → Plugins*.
3. Limpiar la caché Twig (*Admin → Herramientas → Limpiar caché Twig*) tras cada actualización para que las plantillas personalizadas se recarguen.

---

## Estructura del plugin

```
Plugins/HumanResourcesSolwed/
├── Assets/JS/EmployeePanel.js           # JS específico para el panel del empleado
├── Controller/EmployeePanel.php         # Controlador que sustituye al original
├── Extension/
│   └── Controller/
│       ├── EditEmployee.php             # Lógica adicional para la pestaña de archivos/vacaciones
│       └── ListEmployee.php             # Reglas para listas (vacaciones y nóminas)
├── Model/
│   ├── Attendance.php                   # Añade nuevos campos en asistencias
│   ├── EmployeeHoliday.php              # Añade estado por defecto y helpers
│   └── Join/EmployeeHoliday.php         # Expone `applyto` a la capa de vistas
├── View/
│   └── Tab/EmployeePanelAttendances.html.twig
│       # Vista de panel del empleado con mejoras de UI
└── README.md                            # Este archivo
```

---

## Funcionalidades principales

### 1. Panel del empleado

- **Geolocalización automática**: al registrar entradas/salidas `EmployeePanel.js` solicita permisos de ubicación, maneja errores habituales y envía `location` y `localizacion`.
- **Botones inteligentes**: se evita el doble clic en entrada/salida manual, se controlan los períodos actuales y se añade modal de tiempo computable compatible con Bootstrap 4/5.
- **Solicitud de vacaciones**:
  - Modal renovado con campo “Aplicar a” (`applyto`) que permite fijar el año de imputación.
  - Impide cancelaciones accidentales (atributos `data-dismiss`/`data-bs-dismiss`).
  - Envío a `execInsertHolidaysWithStatus()` que persiste el año indicado y marca el estado “Solicitadas”.
- **Visualización agrupada**:
  - Los periodos se agrupan por año con totales de días tomados/pedientes y resumen por estado.
  - Tabla expandible con columnas `fecha inicio`, `fin`, `días`, `año aplicado`, `estado`, `notas` y acciones (eliminación condicionada a fechas futuras).

### 2. Panel administrativo (EditEmployee)

- **Tablero anual** (`EmployeeHolidayYearBoard`) alimentado por `Extension\Controller\EditEmployee`:
  - Filtros por año (`holidayYear`) y orden (`holidayOrder` asc/desc) con persistencia en recargas.
  - Los datos llegan ya agrupados desde el controlador, evitando procesamientos en Twig.
- **Gestión de archivos** (`EmployeeFiles`):
  - Documentos agrupados en subcarpetas por `year_group`, mostrando contadores por pestaña (Documentos, Nóminas, CAE).
  - Selector de orden para mostrar archivos ascendentes/descendentes (`filesorder`).
  - Editor simplificado con formularios embebidos y previsualización.

### 3. Listados para RRHH (`ListEmployee`)

La extensión `ListEmployee` inyecta lógica adicional en dos vistas listas:

| Vista | Cambios aplicados |
|-------|-------------------|
| `ListEmployeeHoliday` | filtros siempre visibles, selector *Aplicar a* con años reales (`applyto`), orden por estado, fecha y nombre. |
| `ListPayRoll` | filtros visibles, selector *Año* basado en `YEAR(startdate)`, orden por fecha de inicio (default), fin, creación y nombre. |

Los selectores se construyen con consultas `DISTINCT` sobre las tablas `rrhh_employeesholidays` y `rrhh_payroll`, por lo que siempre reflejan los años existentes.

### 4. Modelo y datos

- `Attendance` añade `localizacion` y campos auxiliares para registrar ubicación.
- `EmployeeHoliday`:
  - Valor por defecto `holidaystatus = 'Solicitadas'`.
  - Guardado automático del año (`applyto`) si el usuario no lo establece.
  - La unión (`Join\EmployeeHoliday`) ahora expone `applyto` para tablas y filtros.
- `EmployeePanel.php` reimplementa `execInsertHolidaysWithStatus` y `hydrateHolidayGroups` para generar estadísticas reutilizables en las vistas.

---

## Guía para desarrolladores

### Extender controladores
- Utilizamos el sistema de extensiones de FacturaScripts (trait `ExtensionsTrait`). Cada método debe devolver un `Closure`. Si necesitas helpers, define funciones internas (como `$loadDistinctValues`) dentro del propio `Closure`.
- Para añadir filtros/orden a una vista existente: localizarla en `$this->views`, forzar `showFilters = true` y manipular `orderOptions`.

### Plantillas Twig
- Todas las vistas se sitúan en `Plugins/HumanResourcesSolwed/View`. Bootstrap 4/5 coexiste, por lo que los botones utilizan ambos atributos (`data-dismiss` y `data-bs-dismiss`).
- Los bloques que generan tablas/accordeones reciben datos preprocesados desde el controlador para minimizar lógica en Twig.

### JavaScript
- `Assets/JS/EmployeePanel.js` debe mantenerse en modo ES5 (sin build). Evita dependencias adicionales y respeta el patrón jQuery ya existente.
- Las funciones auxiliares como `insertHolidays()` rellenan campos si el usuario los deja vacíos (por ejemplo, copia el año de `startdate` hacia `applyto`).

### Base de datos / Migraciones
- No se incluyen migraciones automáticas. Para nuevos campos replicar el patrón utilizado con `localizacion`: añadirlos al modelo y crear scripts de actualización según necesidades del cliente.

---

## Uso operativo

1. **Empleados**: acceden al panel → registran fichajes con geolocalización → solicitan vacaciones indicando año y motivo.
2. **Recursos Humanos**:
   - Revisan solicitudes agrupadas por año, aplican filtros y ordenan según necesidad.
   - Gestionan documentos dentro de `EmployeeFiles` con subcarpetas anuales.
   - En nóminas (`ListPayRoll`), filtran por año y ordenan cronológicamente para validar períodos.

---

## Solución de problemas

- **Botones no hacen nada**: limpiar caché del navegador y de FacturaScripts, revisar permisos de geolocalización, comprobar errores en consola.
- **Filtros no aparecen**: limpiar caché Twig, verificar que la extensión `ListEmployee` esté cargada (ver `Plugins/HumanResourcesSolwed/Init.php`).
- **Campos sin datos**: recuerda ejecutar cron o scripts que sincronicen asistencias y nóminas, ya que el plugin no los genera automáticamente.

---

## Roadmap sugerido

- [ ] Visualización de ubicaciones sobre mapa.
- [ ] Geofencing y alertas de localización fuera de rango.
- [ ] Notificaciones push para solicitudes de vacaciones.
- [ ] Exportación PDF/Excel de los paneles agrupados por año.

---

## Créditos y soporte

- Desarrollo base: Jose Antonio Cuello Principal (plugin HumanResources).
- Adaptaciones Solwed: equipo Solwed (geolocalización, vistas personalizadas, filtros avanzados).
- Para soporte o nuevas peticiones contactar con el equipo de FacturaScripts / Solwed.

---

## Avisos

- Tras actualizar este plugin **es necesario limpiar la caché Twig**.
- No borres las carpetas `MyFiles/Cache/Twig` manualmente en producción; utiliza el panel de administración.

¡Listo! Con esta información cualquier desarrollador puede continuar iterando sobre HumanResourcesSolwed sin perder contexto.
