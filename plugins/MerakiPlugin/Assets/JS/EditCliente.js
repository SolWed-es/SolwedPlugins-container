 $(document).ready(function() {
    // Selecciona el input con name="email" y type="text"
    $('input[name="email"][type="text"]').attr('type', 'email');

    // Selecciona todas las divisiones con enctype "multipart/form-data" dentro de #EditDireccionContacto
    $('#EditDireccionContacto div[enctype="multipart/form-data"]').each(function() {
        // Busca un input con name="descripcion" y valor "Facturación" dentro de la división actual
        var inputDescripcion = $(this).find('input[name="descripcion"][value="Facturación"]');
        
        // Si se encuentra el input con el valor deseado
        if (inputDescripcion.length > 0) {
            // Mueve la división al principio de #EditDireccionContacto
            $('#EditDireccionContacto').prepend($(this));
            return false; // Sal del bucle .each() después de mover la primera coincidencia
        }
    });
}); 