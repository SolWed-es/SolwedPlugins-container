# Plugin DonDominio para FacturaScript

Integración simplificada con la API de DonDominio para visualizar dominios en **tiempo real** sin almacenarlos en base de datos.

**Versión:** 1.0.4 (Rediseño Final Nov 2024) | **Compatible:** FS 2025.43+ | **PHP:** 8.1+

---

## ⚡ Características Principales

- ✅ **Datos en Tiempo Real** - Sincronización directa con API de DonDominio
- ✅ **Sin Persistencia BD** - No almacena dominios (siempre frescos)
- ✅ **Portal del Cliente** - Los clientes ven sus dominios en el portal personal
- ✅ **Alerta de Expiración** - Notifica dominios próximos a vencer (30 días)
- ✅ **Información Completa** - Estado, nameservers, renovación automática, etc.
- ✅ **Código Simple** - 68% menos código que versiones anteriores
- ✅ **Instalación Fácil** - Sin migraciones BD complicadas

---

## 📋 Requisitos

- **FacturaScript** 2025.43 o superior
- **PHP** 8.1 o superior
- **API DonDominio**: Credenciales válidas (usuario + clave)
- **Conexión HTTPS** a api.dondominio.net

---

## 🚀 Instalación

### Paso 1: Copiar el Plugin
```bash
cp -r Plugins/DonDominio /ruta/a/facturascript/Plugins/
```

### Paso 2: Habilitar el Plugin
1. Ir a **Admin → Complementos**
2. Buscar "DonDominio"
3. Hacer clic en **Habilitar**

### Paso 3: Configurar Credenciales
1. Ir a **Admin → Ajustes → Configuración General**
2. Buscar sección "DonDominio"
3. Ingresar:
   - **Usuario API**: Usuario de DonDominio
   - **Clave API**: Contraseña de DonDominio
4. Guardar cambios

> ⚠️ **Nota**: Dejar vacío "Servidor", "Puerto", "Timeout" para usar valores predeterminados.

---

## 📖 Uso

### Portal del Cliente
Los clientes pueden ver sus dominios en: **Portal → Pestaña "Dominios"**

**Información mostrada:**
- 🌐 Nombre del dominio (enlace directo)
- 📍 Estado (Activo/Pendiente/Expirado/Suspendido)
- 📅 Fecha de expiración
- 🔄 Renovación automática (sí/no)
- 🔗 Nameservers (con tooltip)
- 🔍 Enlace directo a WHOIS

### Admin - Lista de Clientes (Opcional)
Si está habilitado: **Admin → Clientes → Pestaña "Dominios"**
- Ver todos los dominios de todos los clientes
- Filtrar por estado
- Buscar por nombre

---

## ⚙️ Configuración Avanzada

### Configuración Obligatoria
```ini
[dondominio]
dondominio_apiuser = "tu_usuario"
dondominio_apipasswd = "tu_contraseña"
```

### Configuración Opcional
```ini
dondominio_endpoint = "https://simple-api.dondominio.net"  # Por defecto
dondominio_port = "443"                                    # Por defecto
dondominio_timeout = "15"                                  # Segundos
dondominio_verifyssl = "1"                                 # 1=sí, 0=no
dondominio_enable_listcliente = "0"                        # Mostrar en lista clientes
```

---

## 🔄 Flujo de Datos

```
Cliente accede Portal
    ↓
PortalCliente::loadData()
    ↓
DomainApiService::getClientContacts()
    ↓
API DonDominio (solicitud HTTP)
    ↓
Datos parseados (sin guardar en BD)
    ↓
Vista renderizada
    ↓
Datos descartados (se obtienen frescos en próxima solicitud)
```

**Ventaja**: Siempre información actualizada. **Desventaja**: Requiere acceso a API en cada carga.

---

## 🛠️ Desarrollo

### Usar el Servicio de API

```php
use FacturaScripts\Plugins\DonDominio\Lib\DomainApiService;

$service = new DomainApiService();

// Obtener dominios de un cliente
$contacts = $service->getClientContacts('C001');

// Obtener dominios próximos a expirar
$expiring = $service->getExpiringDomains('C001', 30);

// Obtener nameservers de un dominio
$nameservers = $service->getDomainNameservers('example.com');

// Obtener información completa de dominio
$info = $service->getDomainInfo('example.com');
```

### Extender Funcionalidades

1. Abrir `Lib/DomainApiService.php`
2. Agregar nuevo método:
   ```php
   public function myMethod($param): array {
       if (null === $this->apiClient) {
           return [];
       }

       try {
           $response = $this->apiClient->myApiMethod($param);
           // Parsear respuesta
           return $data;
       } catch (\Throwable $e) {
           Tools::log()->error('error-key', ['%message%' => $e->getMessage()]);
           return [];
       }
   }
   ```

3. Usar en controlador/vista

---

## 📊 Estructura del Plugin

```
Plugins/DonDominio/
├── Init.php                                    # Inicialización
├── facturascripts.ini                          # Metadatos
├── PluginInfo.json                             # Información
├── README.md                                   # Este archivo
│
├── Lib/                                        # Servicios
│   ├── DonDominioApiClient.php                 # Cliente SDK
│   ├── DonDominioConfig.php                    # Configuración
│   └── DomainApiService.php                    # Servicio principal (SIN caché)
│
├── Extension/Controller/                       # Extensiones
│   ├── PortalCliente.php                       # Portal del cliente
│   └── ListCliente.php                         # Lista de clientes
│
├── View/                                       # Vistas
│   └── Portal/Tab/PortalDomains.html.twig     # Template
│
├── Translation/                                # Idiomas
│   ├── es_ES.json                              # Español
│   └── en_US.json                              # Inglés
│
└── vendor/                                     # SDK oficial
    └── dondominio/api-sdk-php/
```

---

## 🔍 Solución de Problemas

### Los dominios no aparecen

**Causa 1**: Credenciales incorrectas
```
Solución: Verificar en Admin → Ajustes → DonDominio
```

**Causa 2**: Cliente sin NIF registrado
```
Solución: Agregar NIF/CIF al cliente en Admin → Clientes
```

**Causa 3**: No existe contacto en DonDominio con ese NIF
```
Solución: Crear contacto en panel de control de DonDominio
```

### Error de conexión a API

**Revisar logs:**
```bash
tail -f MyFiles/Logs/default.log | grep dondominio
```

**Validar acceso:**
```bash
curl -I https://simple-api.dondominio.net
```

---

## 📝 Historial de Versiones

### v1.0.4 (Nov 2024) - Rediseño
- ✨ **NUEVO**: Eliminada persistencia en BD
- ✨ **NUEVO**: DomainApiService para consultas directas
- 🔧 **MEJORADO**: Init.php simplificado
- 🔧 **MEJORADO**: Vistas rediseñadas
- 🔧 **MEJORADO**: Código 68% más simple
- ❌ **REMOVIDO**: Migraciones BD
- ❌ **REMOVIDO**: Modelos BD
- ❌ **REMOVIDO**: Servicios de caché

### v1.0.3 y anteriores
- Consultar ANALISIS_COMPLETADO.md

---

## 🔐 Notas de Seguridad

- ✅ Las credenciales se guardan en settings (proteger BD)
- ✅ No hay datos sensibles almacenados en tablas
- ✅ Todas las llamadas a API usan HTTPS
- ✅ Validación de entrada en controladores
- ✅ No hay sincronización en background
- ✅ Logs de error sin exponer sensibles

---

## 📄 Licencia

Parte de FacturaScript 2025.43+

---

## 🔗 Enlaces

- [Documentación Completa](./REDISENO_2024.md)
- [DonDominio](https://www.dondominio.com)
- [FacturaScript](https://facturascripts.com)
- [Soporte FS](https://facturascripts.com/soporte)

---

**Última actualización:** Noviembre 2024 | **Desarrollador:** Rediseño de Plugin
