# Plugin Vehículos para FacturaScripts

Gestión completa de vehículos integrada con FacturaScripts.

## Características

### ✅ Gestión de Vehículos
- Registro completo de vehículos con marca, modelo, matrícula y bastidor
- Asignación de vehículos a clientes
- Control de kilometraje
- Información detallada del propietario

### ✅ Integración con Documentos
- **Facturas**: Información del vehículo mostrada horizontalmente
- **Albaranes**: Datos del vehículo en cabecera
- **Pedidos**: Integración completa
- **Presupuestos**: Información del vehículo incluida

### ✅ Experiencia de Usuario
- Interfaz limpia y moderna
- Campos obligatorios claramente marcados
- Autocomplete para selección de clientes
- Información del cliente en tiempo real
- Validaciones automáticas

### ✅ Características Técnicas
- Compatible con PHP 8.1+
- Integración nativa con FacturaScripts
- Sin modificaciones en archivos dinámicos
- Uso de estilos existentes del sistema

## Instalación

1. Copiar el plugin en la carpeta `Plugins/Vehiculos/`
2. Activar el plugin desde el panel de administración
3. El sistema creará automáticamente las estructuras necesarias

## Uso

### Crear un Vehículo
1. Ir a **Ventas > Vehículos**
2. Hacer clic en **Nuevo**
3. Completar los campos obligatorios:
   - **Modelo** (requerido)
   - **Matrícula** (requerido)
4. Asignar a un cliente
5. Completar información adicional si es necesaria

### Campos Disponibles
- **Marca**: Fabricante del vehículo
- **Modelo**: Modelo específico (obligatorio)
- **Matrícula**: Número de matrícula (obligatorio)
- **Nº Bastidor**: Número VIN/bastidor
- **Código Motor**: Identificación del motor
- **Kilómetros**: Kilometraje actual
- **Cliente**: Propietario del vehículo

### Integración con Documentos
Los vehículos aparecen automáticamente en facturas, albaranes, pedidos y presupuestos cuando:
- El cliente tiene vehículos asignados
- Se selecciona un vehículo específico
- La información se muestra de forma horizontal en el documento

## Estructura del Plugin

```
Plugins/Vehiculos/
├── Controller/
│   └── EditVehiculo.php          # Controlador de edición
├── Model/
│   └── Vehiculo.php              # Modelo principal
├── XMLView/
│   └── EditVehiculo.xml          # Vista de edición
├── Extension/
│   └── Controller/               # Extensiones de controladores
├── Lib/
│   └── PlantillasPDF/           # Plantillas PDF personalizadas
├── Translation/
│   └── *.json                   # Archivos de traducción
└── Table/
    └── vehiculos.xml            # Definición de tabla

```

## Soporte

Este plugin ha sido optimizado para:
- ✅ PHP 8.1+
- ✅ FacturaScripts 2022+
- ✅ Diseño responsive
- ✅ Múltiples idiomas
- ✅ Integración con PlantillasPDF

## Desarrollado por

Plugin limpio y optimizado para producción.
Versión: 1.0.0