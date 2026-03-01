# AcercaFacturaScripts (Solwed) — Plugin para FacturaScripts

Este plugin está pensado para uso interno de Solwed. Añade páginas informativas a FacturaScripts, incluyendo una específica para visualizar los datos de servidor de Solwed.

## Funcionalidades

- Acerca de FacturaScripts: página informativa de ejemplo.
- Datos de servidor de Solwed: muestra estadísticas/datos del servidor de Solwed embebidos en un iframe. La plantilla ajusta automáticamente la altura del iframe al espacio disponible en la ventana.

Si deseas mostrar los datos de un servicio distinto, solo tienes que actualizar manualmente el atributo `src` en el fichero `View/ServerData.html.twig` (pendiente de que sea configurable de forma automática). En concreto, cambia la URL actual:

src="https://solwedpleskapp-production.up.railway.app/jesusmartinezvergel.erpsolwed.es"

por la URL del servicio que quieras visualizar.

## Estructura del plugin

- facturascripts.ini: metadatos del plugin.
- Controller/
  - AboutFacturaScripts.php: controlador que carga la vista `View/AboutFacturaScripts.html.twig`.
  - ServerData.php: controlador de la página "Datos de servidor". Define el título, menú (admin) e icono, y renderiza la vista `View/ServerData.html.twig`.
- View/
  - AboutFacturaScripts.html.twig: plantilla de la página "Acerca de FacturaScripts".
  - ServerData.html.twig: plantilla de la página "Datos de servidor". Extiende `Master/MenuBghTemplate.html.twig`, incluye un iframe con id `serverStatsFrame` cuyo `src` apunta a la URL de datos de Solwed, y un script JS que redimensiona el iframe al cargar y al cambiar el tamaño de la ventana.
- Public/
  - img/facturae.png: recurso de imagen incluido en el plugin.

## Instalación

1. Copia la carpeta del plugin (AcercaFacturaScripts) en el directorio de plugins de FacturaScripts.
2. Activa el plugin desde el panel de administración de FacturaScripts.

## Uso

- Accede a las páginas proporcionadas por el plugin desde el menú correspondiente (el apartado de "admin" para la página de "Datos de servidor").
- La página "Datos de servidor" mostrará la información embebida desde la URL configurada en `View/ServerData.html.twig`.

## Personalización

- Para apuntar a otro servicio distinto de Solwed, edita `View/ServerData.html.twig` y actualiza el atributo `src` del iframe con la URL deseada. Este ajuste se realiza manualmente por ahora; está pendiente hacerlo configurable desde la propia aplicación.

---

