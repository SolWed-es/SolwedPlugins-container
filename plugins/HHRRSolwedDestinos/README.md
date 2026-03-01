# 📋 HHRRSolwedDestinos – Gestión integral de Destinos, Rutas y Turnos para FacturaScripts

**HHRRSolwedDestinos** es un plugin avanzado para [FacturaScripts](https://www.facturascripts.com) que amplía el módulo *Human Resources* incorporando todo lo necesario para gestionar:

* Destinos u obras a visitar/atender.
* Rutas compuestas por varios destinos y con vigencia en el tiempo.
* Asignación de rutas a uno o varios empleados.
* Asignación de destinos puntuales a empleados (fuera de ruta).
* Turnos de trabajo (en combinación con el plugin hermano **HHRRSolwedTurnos**).

El objetivo es ofrecer una solución completa para empresas de servicios que necesitan planificar rutas de trabajo, controlar horarios y mantener un histórico de asignaciones.

> Autor principal: **José Ferran**  –  Basado en el trabajo de **Carlos García Gómez** y **Jose Antonio Cuello Principal** (plugin *Human Resources*).

---

## 🗺️ Estado del proyecto (Jun 2025)

| Área | Estado | Cobertura |
|------|--------|-----------|
| Modelos | ✅ Completos | 7 / 7 |
| Controladores | ✅ Completos | 14 / 14 |
| Tablas SQL | ✅ Completas | 7 / 7 |
| Vistas XML | ✅ Completas | 18 / 18 |
| Vistas Twig | ✅ Completas | 9 / 9 |
| Traducciones | ✅ 5 idiomas | 100 % |
| Sistema de permisos | ✅ Configurado | 100 % |
| Optimización automática de rutas | 🔄 En desarrollo | 30 % |

---

## 🔑 Funcionalidades implementadas

### 1. Gestión de Destinos  `100 %`
* **Modelo:** `Destino.php`
* **Tabla:** `destinos`
* **Controladores:** `EditDestino.php`, `ListDestino.php`
* **Vistas XML:** `EditDestino.xml`, `ListDestino.xml`
* Coordenadas GPS, horarios, vigencia, campo *referencia*, auditoría completa.

### 2. Gestión de Rutas  `100 %`
* **Modelo:** `Ruta.php`
* **Tabla:** `rutas`
* **Controladores:** `EditRuta.php`, `ListRuta.php`
* **Vistas:** `EditRuta.xml`, `ListRuta.xml`, `EditRuta.html.twig`
* Campo *optimizada* preparado para cálculos de optimización.

### 3. Asignación Destino ↔ Ruta  `100 %`
* **Modelo:** `RutaDestino.php`
* **Tabla:** `ruta_destino`
* **Controlador:** `EditRutaDestino.php`
* **Vista XML:** `ListRutaDestino.xml` (inline en pestaña *Destinos* de `EditRuta`).
* Orden secuencial drag-&-drop con validación de unicidad.

### 4. Asignación Destino ↔ Empleado  `100 %`
* **Modelo:** `DestinoTrabajador.php`
* **Tabla:** `destino_trabajador`
* **Controladores:** `EditDestinoTrabajador.php`, `ListDestinoTrabajador.php`
* **Vistas XML:** `EditDestinoTrabajador.xml`, `ListDestinoTrabajador.xml`

### 5. Gestión de Turnos  `100 %`
* **Modelos:** `Shift.php`, `EmployeeShift.php`
* **Tablas:** `rrhh_shifts`, `rrhh_employeesshifts`
* **Controladores:** `EditShift.php`, `ListShift.php`, `EditEmployeeShift.php`, `ListEmployeeShift.php`, `AssignShift.php`, `MyShifts.php`
* **Vista Twig:** `MyShifts.html.twig` (calendario interactivo).

### 6. Asignación Ruta ↔ Empleado  `100 % (NOVEDAD)`
* **Modelo:** `EmployeeRoute.php`
* **Tabla:** `rrhh_employeeroutes`
* **Pestaña en EditRuta:** *Empleados* (vista HTML `Tab/EmpleadosRuta.html.twig`).
* **Endpoints AJAX en `EditRuta.php`:** `assign-ruta`, `unassign-ruta`, `get-employees`, `load-rutas-data`.
* Validaciones: duplicados, fecha de asignación, estado *activo*.
* Desasignación con confirmación modal.

### 7. Sistema de Pestañas avanzado en `EditRuta`
* Pestaña **Datos** (formulario principal).
* Pestaña **Destinos**: lista inline con drag-&-drop (sortable).
* Pestaña **Empleados**: asignación y listado en tiempo real (funcional desde v1.1).

### 8. Multi-idioma y permisos
* 5 idiomas completos (`es_ES`, `es_AR`, `es_MX`, `en_EN`, `fr_FR`).
* Traducciones adicionales específicas en `Data/Lang`.
* `Data/permissions.xml` define roles `employee`, `hr_manager`, `admin`.

---

## 🗄️ Estructura de base de datos

### Tabla `destinos`
```sql
iddestino SERIAL PRIMARY KEY,
nombre VARCHAR(120) NOT NULL,
direccion TEXT,
ciudad VARCHAR(60),
cp VARCHAR(10),
lat DOUBLE,
lon DOUBLE,
duracion_min INTEGER DEFAULT 60,
hora_entrada TIME,
hora_salida TIME,
fecha_inicio DATE,
fecha_fin DATE,
activo BOOLEAN DEFAULT TRUE,
referencia BOOLEAN DEFAULT FALSE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

### Tabla `rutas`
```sql
idruta SERIAL PRIMARY KEY,
nombre VARCHAR(100) NOT NULL,
fecha_inicio DATE,
fecha_fin DATE,
optimizada BOOLEAN DEFAULT FALSE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

### Tabla `ruta_destino`
```sql
id SERIAL PRIMARY KEY,
idruta INTEGER NOT NULL REFERENCES rutas(idruta) ON DELETE CASCADE,
iddestino INTEGER NOT NULL REFERENCES destinos(iddestino) ON DELETE CASCADE,
orden INTEGER NOT NULL,
UNIQUE(idruta, iddestino)
```

### Tabla `destino_trabajador`
```sql
id SERIAL PRIMARY KEY,
idemployee INTEGER NOT NULL REFERENCES rrhh_employees(id),
iddestino INTEGER NOT NULL REFERENCES destinos(iddestino),
fecha DATE NOT NULL,
activo BOOLEAN DEFAULT TRUE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

### Tabla `rrhh_employeeroutes`  *(nueva)*
```sql
id SERIAL PRIMARY KEY,
idemployee INTEGER NOT NULL REFERENCES rrhh_employees(id) ON DELETE CASCADE,
idruta INTEGER NOT NULL REFERENCES rutas(idruta) ON DELETE CASCADE,
fecha_asignacion DATE NOT NULL DEFAULT CURRENT_DATE,
activo BOOLEAN DEFAULT TRUE,
notas TEXT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
UNIQUE(idemployee, idruta, fecha_asignacion)
```

Las tablas de turnos se comparten con el plugin **HHRRSolwedTurnos** (`rrhh_shifts`, `rrhh_employeesshifts`).

---

## 🎛️ Controladores

| Controlador | Propósito |
|-------------|-----------|
| **EditDestino** / **ListDestino** | CRUD de destinos |
| **EditRuta** / **ListRuta** | CRUD de rutas + pestañas avanzadas |
| **EditRutaDestino** | Asignar destinos a rutas |
| **EditDestinoTrabajador** / **ListDestinoTrabajador** | Asignar destinos sueltos a empleados |
| **EditShift** / **ListShift** | CRUD de turnos |
| **EditEmployeeShift** / **ListEmployeeShift** | Asignar turnos a empleados |
| **AssignShift** | Asignación masiva de turnos |
| **MyShifts** | Calendario personal de turnos |

> `EditRuta` añade internamente las acciones AJAX `assign-ruta`, `unassign-ruta`, `load-rutas-data` y `get-employees`.

---

## 🖥️ Estructura de archivos clave

```
HHRRSolwedDestinos/
├── Controller/
│   ├── EditRuta.php          # Core con pestañas y endpoints AJAX
│   ├── EditRutaDestino.php   # Gestión ruta ↔ destino
│   ├── EditDestino.php       # CRUD destinos
│   ├── EditDestinoTrabajador.php
│   ├── AssignShift.php …
│   └── …
├── Model/
│   ├── Ruta.php
│   ├── Destino.php
│   ├── RutaDestino.php
│   ├── DestinoTrabajador.php
│   ├── EmployeeRoute.php   # NOVEDAD v1.1
│   └── …
├── Table/
│   ├── rutas.xml
│   ├── destinos.xml
│   ├── ruta_destino.xml
│   ├── destino_trabajador.xml
│   ├── rrhh_employeeroutes.xml  # NUEVA
│   └── …
├── View/
│   ├── EditRuta.html.twig
│   ├── MyShifts.html.twig
│   └── Tab/
│       ├── DestinoRuta.html.twig
│       ├── EmpleadosRuta.html.twig   # Asignación ruta ↔ empleado
│       └── EmpleadosRutaList.html.twig
└── …
```

---

## 🚀 Instalación

1. Copiar la carpeta **HHRRSolwedDestinos** dentro de `/Plugins/`.
2. Activar el plugin desde *Sistema > Plugins*.
3. Al activar/actualizar se ejecuta `Init.php`, creando automáticamente las tablas (incluida `rrhh_employeeroutes`).
4. Conceder los permisos deseados a los roles desde *Sistema > Usuarios y permisos*.
5. (Opcional) Instalar el plugin **HHRRSolwedTurnos** para la gestión completa de turnos.

> Requisitos mínimos: FacturaScripts 2024.3+, PHP 8.0.

---

## 🛠️ Uso básico

### 1. Crear destinos base
Ruta: *RRHH > Destinos* → *Nuevo*.

### 2. Crear una ruta
Ruta: *RRHH > Rutas* → *Nuevo*.

1. Complete los datos principales.
2. Guarde y abra la pestaña **Destinos** para añadir paradas.
3. Abra la pestaña **Empleados** para asignar responsables.

### 3. Asignar destinos puntuales a empleados
Ruta: *RRHH > Destinos > Asignar a trabajador*.

### 4. Gestionar turnos (opcional)
Vía plugin **HHRRSolwedTurnos**.

---

## 🗓️ Roadmap próximo

1. **Optimización automática de rutas** (distancias, Google / OSRM API).
2. **Dashboard de métricas** (tiempos, cumplimientos, KPIs).
3. **Mapa interactivo** en pestaña *Destinos* con arrastre visual.
4. **Integración con dispositivos móviles** para check-in/check-out.

---

## 🤝 Contribución

1. Haz *fork* del repositorio.
2. Crea una rama descriptiva (`feature/…` o `fix/…`).
3. Sigue los estándares de código de FacturaScripts.
4. Acompaña tu PR de tests o evidencias de uso.
5. Actualiza este README y el *changelog*.

---

## 📄 Metadatos del plugin

| Dato | Valor |
|------|-------|
| Nombre | **HHRRSolwedDestinos** |
| Versión | **1.1.0** |
| Licencia | LGPL-3.0 |
| Compatibilidad | FacturaScripts 2024.3+ |
| PHP | ≥ 8.0 |
| Última actualización | **Jun 2025** |

---

© 2025 Jose Ferran – Proyecto liberado bajo licencia **LGPL-3.0**.