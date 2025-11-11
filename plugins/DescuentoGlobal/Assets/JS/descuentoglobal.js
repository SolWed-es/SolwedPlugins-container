(function ($) {
    const updatePrice = (form) => {
        if (!form || !form.coste || !form.margen || !form.precio) {
            return;
        }

        const cost = parseFloat(form.coste.value);
        const margin = parseFloat(form.margen.value);
        if (isNaN(cost) || isNaN(margin) || margin >= 0) {
            return;
        }

        form.precio.value = cost * (100 + margin) / 100;
    };

    $(document).ready(function () {
        $('.calc-cost').off('change.descuentoglobal').on('change.descuentoglobal', function () {
            updatePrice(this.form);
        });

        $('.calc-margin').off('change.descuentoglobal').on('change.descuentoglobal', function () {
            updatePrice(this.form);
        });
    });
})(jQuery);
