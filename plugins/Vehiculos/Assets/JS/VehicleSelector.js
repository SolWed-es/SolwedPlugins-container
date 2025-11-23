/**
 * Vehicle Selector - Dynamic loading based on selected customer
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez
 */

$(document).ready(function() {
    'use strict';

    var updating = false;

    function getElements() {
        return {
            customer: $('select[name="codcliente"]'),
            banner: $('[data-role="vehicle-selector"]'),
            formField: $('select[name="idmaquina"], input[name="idmaquina"]').not('[data-role="vehicle-selector"]')
        };
    }

    function loadCustomerVehicles(codcliente, $vehicleSelect, currentValue) {
        if (!codcliente) {
            $vehicleSelect.html('<option value="">-- ' + i18next.t('select') + ' --</option>');
            syncBodyValue('');
            return;
        }

        $vehicleSelect.html('<option value="">' + i18next.t('loading') + '...</option>');

        $.ajax({
            url: 'AjaxVehiculos',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get-customer-vehicles',
                codcliente: codcliente
            },
            success: function(response) {
                if (response.ok) {
                    updateVehicleSelect(response.vehicles, $vehicleSelect, currentValue);
                } else {
                    console.error('Error loading vehicles:', response.message);
                    $vehicleSelect.html('<option value="">-- ' + i18next.t('error') + ' --</option>');
                }
            },
            error: function() {
                console.error('AJAX error loading vehicles');
                $vehicleSelect.html('<option value="">-- ' + i18next.t('error') + ' --</option>');
            }
        });
    }

    function updateVehicleSelect(vehicles, $vehicleSelect, currentValue) {
        var html = '<option value="">-- ' + i18next.t('select') + ' --</option>';

        if (vehicles && vehicles.length > 0) {
            vehicles.forEach(function(vehicle) {
                var displayName = vehicle.nombre;
                if (vehicle.matricula) {
                    displayName += ' (' + vehicle.matricula + ')';
                }
                html += '<option value="' + vehicle.idmaquina + '">' +
                    escapeHtml(displayName) + '</option>';
            });
        } else {
            html = '<option value="">' + i18next.t('no-vehicles-for-customer') + '</option>';
        }

        $vehicleSelect.html(html);

        if (currentValue && $vehicleSelect.find('option[value="' + currentValue + '"]').length > 0) {
            $vehicleSelect.val(currentValue);
        } else {
            $vehicleSelect.val('');
            syncBodyValue('');
        }
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function syncBodyValue(value) {
        updating = true;
        $('select[name="idmaquina"], input[name="idmaquina"]').not('[data-role="vehicle-selector"]').each(function() {
            $(this).val(value);
        });
        updating = false;
    }

    function initializeState() {
        var elems = getElements();
        if (elems.banner.length === 0) {
            return;
        }

        var value = elems.formField.first().val() || '';
        if (value && elems.banner.find('option[value="' + value + '"]').length) {
            elems.banner.val(value);
        } else if (!value) {
            elems.banner.val('');
        }

        if (elems.customer.val()) {
            loadCustomerVehicles(elems.customer.val(), elems.banner, value);
        } else {
            elems.banner.html('<option value="">-- ' + i18next.t('select') + ' --</option>');
            syncBodyValue('');
        }
    }

    $(document).on('change', 'select[name="codcliente"]', function() {
        var codcliente = $(this).val();
        var elems = getElements();

        if (elems.banner.length === 0) {
            return;
        }

        if (!codcliente) {
            elems.banner.html('<option value="">-- ' + i18next.t('select') + ' --</option>');
            syncBodyValue('');
            return;
        }

        loadCustomerVehicles(codcliente, elems.banner, elems.formField.first().val() || '');
    });

    $(document).on('change', '[data-role="vehicle-selector"]', function() {
        if (updating) {
            return;
        }
        var value = $(this).val() || '';
        syncBodyValue(value);
    });

    $(document).on('change', 'select[name="idmaquina"], input[name="idmaquina"]', function() {
        if (updating) {
            return;
        }
        var value = $(this).val() || '';
        $('[data-role="vehicle-selector"]').val(value);
    });

    initializeState();

    var header = document.getElementById('salesFormHeader');
    if (header) {
        var observer = new MutationObserver(function() {
            setTimeout(initializeState, 0);
        });
        observer.observe(header, {childList: true});
    }
});
