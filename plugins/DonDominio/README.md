# Plugin DonDominio para FacturaScripts

Integración con la API de DonDominio para la gestión de dominios en FacturaScripts.

## Características

- Configuración de credenciales de API de DonDominio
- Gestión de dominios
- Integración con el SDK de DonDominio
- Compatible con FacturaScripts 2025.43+
- Compatible con PHP 8.1+

## Instalación

1. Descarga el plugin en la carpeta `Plugins/DonDominio`
2. Ve a Panel de control > Actualizaciones
3. Instala el plugin DonDominio

## Configuración

1. Ve a Panel de control > Ajustes > Datos de acceso
2. Ingresa tus credenciales de API de DonDominio:
   - **API Username**: Tu usuario de DonDominio
   - **API Password**: Tu contraseña de DonDominio
   - **HostName**: URL del endpoint de la API (por defecto: https://simple-api.dondominio.net)
   - **Puerto**: Puerto de conexión (por defecto: 443)
   - **Timeout**: Tiempo máximo de espera (por defecto: 15 segundos)
   - **Verificar SSL**: Activar validación de certificados SSL

3. Guarda la configuración

## Uso

### Acceder a la configuración

La configuración se encuentra en: **Panel de control > Ajustes > Datos de acceso**

### Usar la API

```php
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioApiClient;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioConfig;

// Verificar si está configurado
if (DonDominioConfig::isConfigured()) {
    $client = DonDominioApiClient::get();
    // Usar el cliente de la API
}
```

## Estructura del Plugin

```
Plugins/DonDominio/
├── Controller/
│   └── DonDominioController.php
├── Lib/
│   ├── DonDominioApiClient.php
│   └── DonDominioConfig.php
├── Translation/
│   ├── es_ES.json
│   └── en_US.json
├── XMLView/
│   └── SettingsDonDominio.xml
├── facturascripts.ini
├── Init.php
├── PluginInfo.json
├── composer.json
└── README.md
```

## Requisitos

- FacturaScripts 2025.43 o superior
- PHP 8.1 o superior
- Credenciales válidas de DonDominio

## Licencia

Este plugin está bajo licencia MIT.
