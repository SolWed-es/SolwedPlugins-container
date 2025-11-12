(function ($) {
    const updatePriceWithDiscount = (form) => {
        if (!form || !form.precio || !form.descuento) {
            return;
        }

        const precio = parseFloat(form.precio.value);
        const descuento = parseFloat(form.descuento.value);

        if (isNaN(precio) || isNaN(descuento) || descuento < 0 || descuento > 100) {
            return;
        }

        // Calcular precio con descuento
        const precioConDescuento = precio * (100 - descuento) / 100;

        // Mostrar el precio con descuento en algún lugar visible
        // Por ahora solo aseguramos que los cálculos sean correctos
        console.log('Precio original:', precio, 'Descuento:', descuento + '%', 'Precio con descuento:', precioConDescuento.toFixed(2));
    };

    $(document).ready(function () {
        // Escuchar cambios en el campo descuento
        $(document).off('change.descuentoproducto', 'input[name="descuento"]')
                   .on('change.descuentoproducto', 'input[name="descuento"]', function () {
            updatePriceWithDiscount(this.form);
        });

        // También escuchar cambios en el precio para recalcular
        $(document).off('change.descuentoproducto', 'input[name="precio"]')
                   .on('change.descuentoproducto', 'input[name="precio"]', function () {
            updatePriceWithDiscount(this.form);
        });
    });
})(jQuery);
