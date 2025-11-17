$(document).ready(function() {
    // Función para actualizar el estado del span y los textos de estado
    function updateHolidayStatus() {
        // Buscar filas que contengan los diferentes estados
        const solicitadasRows = $('.table-responsive tr td:contains("Solicitadas")');
        const aceptadasRows = $('.table-responsive tr td:contains("Aceptadas")');
        const holidaySpan = $('span:contains("Vacaciones")');

        // Estilizar el texto "Solicitadas" dentro de las filas
        solicitadasRows.css({
            'color': 'red',
            'font-weight': 'bold'
        });

        // Estilizar el texto "Aceptadas" dentro de las filas
        aceptadasRows.css({
            'color': 'green',
            'font-weight': 'bold'
        });

        // Actualizar el span de Vacaciones si hay filas con Solicitadas
        if (solicitadasRows.length > 0) {
            holidaySpan.css({
                'color': 'red',
                'font-weight': 'bold'
            });
        } else {
            holidaySpan.css({
                'color': '',
                'font-weight': ''
            });
        }

        // Mostrar el contador total de "Solicitadas" dentro del badge del tab de vacaciones
        const $badge = $('a[href="#ListEmployeeHoliday"] .badge-secondary');
        if ($badge.length) {
            // Guardar el contenido original del badge para restaurarlo cuando no haya "Solicitadas"
            if (!$badge.data('original-html')) {
                $badge.data('original-html', $badge.html());
            }

            const totalSolicitadas = solicitadasRows.length;

            if (totalSolicitadas > 0) {
                // Escribir el número dentro del propio badge y resaltarlo
                $badge
                    .text(totalSolicitadas)
                    .css({ 'color': 'white', 'font-weight': 'bold' })
                    .show();
            } else {
                // Restaurar contenido y estilos originales
                $badge
                    .html($badge.data('original-html'))
                    .css({ 'color': '', 'font-weight': '' });
            }
        }
    }

    // Ejecutar cuando el documento esté listo
    updateHolidayStatus();

    // Observar cambios en la tabla de vacaciones
    const tableEl = $('.card.border-info .table').get(0);
    if (tableEl) {
        const observer = new MutationObserver(updateHolidayStatus);
        observer.observe(tableEl, {
            childList: true,
            subtree: true
        });
    }
});