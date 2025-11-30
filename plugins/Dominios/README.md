# Plugin Dominios para FacturaScripts

Gestión avanzada de dominios con integración completa de API en tiempo real.

## Características

- ✅ **Lectura de dominios** desde API de DonDominio
- ✅ **Visualización en panel interno** (EditCliente)
- ✅ **Portal cliente** con gestión completa
- ✅ **Gestión de nameservers**
- ✅ **Códigos de autorización** (AuthCode)
- ✅ **Renovación automática** de dominios
- ✅ **Bloqueo de transferencias**
- ✅ **Compra de dominios** nuevos
- ✅ **Verificación de disponibilidad**
- ✅ **100% STATELESS** - No usa base de datos

## Instalación

### Requisitos del Sistema

- **PHP**: 8.1 o superior
- **Extensión cURL**: Debe estar habilitada
- **FacturaScripts**: 2025.43 o superior

### 1. Instalar dependencias de Composer

```bash
cd Plugins/Dominios
composer install
```

### 2. Configurar credenciales

**⚠️ IMPORTANTE:** El archivo `config.php` NO está en el repositorio por seguridad.

```bash
# Copiar el archivo de ejemplo
cp config.example.php config.php

# Editar y poner tus credenciales
nano config.php
```

**Contenido de `config.php`:**

```php
<?php
return [
    'apiuser' => 'TU-USUARIO-API',
    'apipasswd' => 'TU-CONTRASEÑA-API',
    'endpoint' => 'https://simple-api.dondominio.net',
    'port' => 443,
    'timeout' => 15,
    'verifySSL' => true,
];
```

### 3. Activar el plugin

- Ir a **Administración > Plugins**
- Activar **Dominios**

### 4. Verificar la instalación

```bash
# Verificar que config.php carga correctamente
php -r "var_dump(require 'config.php');"
# Debería mostrar el array con tus credenciales

# Verificar que config.php está ignorado por Git
git status
# config.php NO debería aparecer en la lista

# Ver archivos modificados (listos para commit)
git status
# Deberías ver: .gitignore, config.example.php, README.md, etc.
```

## Seguridad

El archivo `config.php` está en `.gitignore` y **nunca se subirá al repositorio**.

Solo existe en tu servidor local. Asegúrate de:

- ✅ Tener permisos `644` en `config.php`
- ✅ No compartir este archivo públicamente
- ✅ Hacer backup seguro de tus credenciales

### Permisos recomendados

```bash
chmod 644 config.php
chown www-data:www-data config.php
```

## Actualización del Plugin

**⚠️ IMPORTANTE:** Antes de actualizar el plugin, haz backup de `config.php`:

```bash
# Hacer backup antes de actualizar
cp config.php config.php.backup

# Actualizar plugin (git pull, etc)
git pull

# Restaurar config.php si se perdió
cp config.php.backup config.php
```

El archivo `config.php` está en `.gitignore`, por lo que no se sobrescribirá en actualizaciones normales de Git.

## Despliegue en Producción

Cuando despliegues el plugin en otro servidor, ten en cuenta:

### 1. El repositorio NO incluye config.php

El archivo `config.php` está en `.gitignore`, por lo que al clonar o copiar el repositorio **no vendrá incluido**.

### 2. Crear config.php en el nuevo servidor

```bash
# En el servidor de producción
cd Plugins/Dominios

# Copiar el ejemplo
cp config.example.php config.php

# Editar con las credenciales del servidor de producción
nano config.php

# Establecer permisos correctos
chmod 644 config.php
chown www-data:www-data config.php
```

### 3. Verificar antes de activar

```bash
# Verificar sintaxis
php -l config.php

# Verificar que carga correctamente
php -r "var_dump(require 'config.php');"
```

### 4. Hacer backup de config.php

**IMPORTANTE:** Antes de cada actualización del plugin:

```bash
# Backup de seguridad
cp config.php config.php.backup

# Actualizar plugin (git pull, etc.)
git pull

# Si config.php se perdió (no debería pasar), restaurar
cp config.php.backup config.php
```

## Hacer Commit de los Cambios

Si has modificado el plugin y quieres hacer commit:

```bash
# Añadir todos los archivos modificados
git add .

# Verificar que config.php NO está en la lista
git status
# No debería aparecer config.php

# Hacer commit
git commit -m "Actualización del plugin Dominios

- Descripción de los cambios realizados"

# Push al repositorio
git push
```

**Verificación de seguridad:**

```bash
# Asegúrate de que config.php NO está rastreado
git ls-files | grep config.php
# No debería devolver nada (o solo config.example.php)
```

## Configuración

### Credenciales API

Las credenciales se obtienen de tu cuenta en [DonDominio](https://www.dondominio.com):
- **Usuario API**: Tu usuario de API
- **Password API**: Tu contraseña de API

### Configuración Avanzada

Puedes modificar `config.php` con estas opciones:

| Opción | Descripción | Por defecto |
|--------|-------------|-------------|
| `apiuser` | Usuario de la API | *(requerido)* |
| `apipasswd` | Contraseña de la API | *(requerido)* |
| `endpoint` | URL del servidor API | `https://simple-api.dondominio.net` |
| `port` | Puerto de conexión | `443` |
| `timeout` | Timeout en segundos | `8` |
| `verifySSL` | Verificar certificados SSL | `true` |

## Uso

### Panel Interno (EditCliente)

1. Ir a `Clientes → Editar cliente`
2. Hacer clic en la pestaña **"Dominios"**
3. Ver dominios asociados al cliente
4. Gestionar nameservers, renovaciones, etc.

### Portal Cliente

1. El cliente accede a su portal
2. Ve la sección **"Dominios"**
3. Puede gestionar sus dominios

## Solución de Problemas

### Error: "No se encontró el archivo config.php"

**Solución**: Asegúrate de haber creado el archivo:

```bash
cd Plugins/Dominios
cp config.example.php config.php
nano config.php
```

### Error: "El archivo config.php debe retornar un array"

**Solución**: Verifica que `config.php` tenga la estructura correcta:

```php
<?php
return [
    'apiuser' => 'tu-usuario',
    'apipasswd' => 'tu-password',
    // ...
];
```

### Error: "La extensión cURL de PHP es requerida"

**Solución**:
```bash
# Ubuntu/Debian
sudo apt install php8.5-curl

# CentOS/RHEL
sudo yum install php-curl

# Reiniciar web server
sudo systemctl restart apache2
```

### Error: "Dependencias no encontradas"

**Solución**: Asegurarse de que el directorio `vendor/` esté incluido en el plugin:

```bash
cd Plugins/Dominios
composer install
```

## Estructura del Plugin

```
Plugins/Dominios/
├── config.php                    # Tus credenciales (NO en repo)
├── config.example.php            # Ejemplo (SÍ en repo)
├── .gitignore                    # Ignora config.php
├── Assets/JS/                    # JavaScript para portal
├── Extension/Controller/         # Extensiones de controladores
├── Lib/
│   ├── DomainConfig.php          # Lee config.php
│   ├── DomainApiService.php
│   └── ...
├── Model/                        # Modelos de datos
├── Translation/                  # Traducciones
├── View/                         # Templates
├── XMLView/                      # Definiciones XML
├── vendor/                       # Dependencias (incluido)
├── composer.json                 # Configuración Composer
├── facturascripts.ini            # Configuración plugin
├── Init.php                      # Inicialización
└── README.md                     # Este archivo
```

## Desarrollo

### Requisitos para desarrollo

```bash
composer install  # Instalar dependencias de desarrollo
```

### Testing

```bash
vendor/bin/phpunit  # Ejecutar tests
```

## Licencia

Este plugin está bajo la licencia correspondiente de FacturaScripts.

## Soporte

Para soporte técnico contactar con el desarrollador.
