PROMPT 1 – Análisis y planificación
Analiza el archivo README.md del plugin HHRRSolwedDestinos y el controlador AssignShift.php.  
Objetivo: crear una funcionalidad “AssignRuta” dentro de EditRuta.php que asigne rutas a empleados, reutilizando la misma lógica y campos que AssignShift pero adaptándola a la tabla rrhh_employeeroutes (campo idemployee, idruta, fecha_asignacion, activo, notas).  
Entrega un esquema de pasos y los archivos que habrá que examinar bien para tomarlos como referencia o que habría que modificar o crear.


 PROMPT 2 – Crear modelo intermedio (si no existe)
Basándote en el esquema anterior, genera el modelo EmployeeRoute.php (campo id, idemployee, idruta, fecha_asignacion, activo, notas, created_at, updated_at) y la tabla XML correspondiente rrhh_employeeroutes.xml con sus constraints FK a rrhh_employees(id) y rutas(idruta). Si tienes alguna duda, busca información en HHRRSolwedDestinos o el plugin HumanResources. Recordar que las tablas para la base de datos se crean con archivos XML (examina los ejemplos de la carpeta TAble para ver qué tipo de campos se pueden incluir y de qué manera)
Incluye los métodos getRuta() y getEmployee() para facilitar joins.


Prueba prompts de prompts
Actúa como un agente de desarrollo especializado en FacturaScripts.
Ejecuta los siguientes pasos en orden, sin saltarte ninguno.
Después de cada paso, confirma brevemente que se completó correctamente antes de continuar.


paso 1 – Extender EditRuta.php con AssignRuta
Añade dentro de EditRuta.php los métodos privados:

- assignRutaAction()  
- unassignRutaAction()  
- loadRutasDataAction()  

que operen igual que assignShiftAction / unassignShiftAction / loadShiftsDataAction pero usando EmployeeRoute y los campos idemployee, idruta, fecha_asignacion, activo, notas.  
Incluye validaciones de existencia de empleado y ruta, y redirección segura.  
No modifiques la firma de privateCore(), solo añade el switch-case para 'assign-ruta', 'unassign-ruta', 'load-rutas-data'.

paso 2 – Vista/pestaña dentro de EditRuta
Genera el fragmento Twig (archivo Tab/EmpleadosRuta.html.twig) que se incrustará en la pestaña “Empleados” de EditRuta.  
Debe contener:

- Selector de empleado (autocomplete)  
- Campo fecha_asignacion (date)  
- Campo notas (textarea)  
- Botón “Asignar” que haga POST a assign-ruta  
- Tabla/listado AJAX que cargue con load-rutas-data  
- Botones “Desasignar” por fila que llamen a unassign-ruta  

Usa la misma estructura de datos y nombres de campo que AssignShift.

 PROMPT 5 – Integración y pruebas
Proporciona los pasos finales:

1. Regenerar tablas (disable/enable plugin)  
2. Verificar rutas y empleados de prueba  
3. Probar flujo completo desde EditRuta > pestaña Empleados  
4. Revisar logs y mensajes de error/éxito  

Incluye comando bash para limpiar caché y activar plugin.