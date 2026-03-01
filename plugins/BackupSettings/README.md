# BackupSetting (FacturaScripts)

Extensión para el plugin Backup que permite configurar la frecuencia del cron de copias de seguridad en FacturaScripts. Con este plugin puedes elegir entre ejecución diaria, semanal o mensual sin duplicar tareas ni lógica: reutiliza el planificador y la creación de backups del propio plugin Backup.

- Frecuencias soportadas: daily, weekly, monthly
- Valor por defecto: weekly (si no hay configuración o si es inválida)

## Requisitos

- FacturaScripts en funcionamiento.
- Plugin Backup instalado y activo.
- Cron/tareas programadas del servidor habilitadas (Linux cron, Windows Task Scheduler, etc.).

## Instalación

1. Copia la carpeta del plugin en:
   - `Plugins/BackupSetting`
2. Activa el plugin desde: Extensiones → Plugins.
3. Verifica que el plugin Backup también esté activo.

## Configuración

1. Ve a: Administración → Backup Setting.
2. Selecciona la frecuencia deseada (Diaria, Semanal, Mensual).
3. Guarda los cambios. La nueva planificación se aplicará en el siguiente ciclo de ejecución del cron.

La configuración se guarda en:
- `MyFiles/Config/BackupSetting.json`

Ejemplo de contenido:
```json
{
  "frequency": "weekly"
}
## Cómo funciona

El plugin registra un job de cron usando el mismo nombre que el plugin Backup, sustituyendo únicamente su planificación (no crea trabajos duplicados).
Llama internamente al método de creación de backup del plugin Backup, por lo que no reimplementa la lógica de copia.

Mapea la frecuencia elegida a intervalos:
    daily → "1 day"
    weekly → "1 week"
    monthly → "1 month" (si tu planificador no soporta "1 month", el servidor puede tratarlo como 4 weeks según su implementación).
    
También emite una entrada de log con la frecuencia aplicada para facilitar el diagnóstico.

Cron del sistema
Asegúrate de tener configurado el cron del sistema para ejecutar las tareas programadas de FacturaScripts según la documentación oficial.