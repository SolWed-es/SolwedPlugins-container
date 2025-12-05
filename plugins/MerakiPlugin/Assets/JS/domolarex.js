 $(document).ready(function() {
    $(document).on('keydown', function(event) {
        // Comprobar si se presionó la tecla 'q' y si se está presionando la tecla 'Ctrl'
        if (event.key === 'q' && event.ctrlKey) {
            // Prevenir la acción predeterminada de Ctrl+L (por ejemplo, seleccionar la barra de direcciones en algunos navegadores)
            event.preventDefault();
            
            // Hacer foco en el elemento con id 'findProductInput'
            console.log('has pulsado');
            $('#findProductInput').focus();
        }
    });
}); 