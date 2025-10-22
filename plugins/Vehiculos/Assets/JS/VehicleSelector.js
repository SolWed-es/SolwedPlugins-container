/**
 * Vehicle Selector - Dynamic loading based on selected customer
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez
 */

$(document).ready(function() {
    'use strict';

    // Elementos del DOM
    var $customerSelect = $('select[name="codcliente"]');
    var $vehicleSelect = $('select[name="idmaquina"]');

    if ($customerSelect.length === 0 || $vehicleSelect.length === 0) {
        return; // No hay selectores en esta página
    }

    // Guardar cliente original
    var originalCustomer = $customerSelect.val();

    /**
     * Carga los vehículos del cliente seleccionado
     */
    function loadCustomerVehicles(codcliente) {
        if (!codcliente) {
            $vehicleSelect.html('<option value="">-- ' + i18next.t('select') + ' --</option>');
            $vehicleSelect.prop('disabled', true);
            return;
        }

        // Deshabilitar mientras carga
        $vehicleSelect.prop('disabled', true);
        $vehicleSelect.html('<option value="">' + i18next.t('loading') + '...</option>');

        // Hacer petición AJAX
        $.ajax({
            url: 'index.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get-customer-vehicles',
                codcliente: codcliente
            },
            success: function(response) {
                if (response.ok) {
                    updateVehicleSelect(response.vehicles);
                } else {
                    console.error('Error loading vehicles:', response.message);
                    $vehicleSelect.html('<option value="">-- ' + i18next.t('error') + ' --</option>');
                }
            },
            error: function() {
                console.error('AJAX error loading vehicles');
                $vehicleSelect.html('<option value="">-- ' + i18next.t('error') + ' --</option>');
            },
            complete: function() {
                $vehicleSelect.prop('disabled', false);
            }
        });
    }

    /**
     * Actualiza las opciones del selector de vehículos
     */
    function updateVehicleSelect(vehicles) {
        var currentValue = $vehicleSelect.val();
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

        // Restaurar valor si existe
        if (currentValue && $vehicleSelect.find('option[value="' + currentValue + '"]').length > 0) {
            $vehicleSelect.val(currentValue);
        }
    }

    /**
     * Escapa HTML para prevenir XSS
     */
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

    /**
     * Evento: Cambio de cliente
     */
    $customerSelect.on('change', function() {
        var newCustomer = $(this).val();

        // Si cambió el cliente, limpiar vehículo seleccionado
        if (newCustomer !== originalCustomer) {
            $vehicleSelect.val('');
        }

        // Cargar vehículos del nuevo cliente
        loadCustomerVehicles(newCustomer);

        // Actualizar cliente original
        originalCustomer = newCustomer;
    });

    // Cargar vehículos al iniciar si hay cliente
    if (originalCustomer) {
        // El selector ya viene con las opciones desde el servidor
        // Solo habilitarlo
        $vehicleSelect.prop('disabled', false);
    } else {
        $vehicleSelect.prop('disabled', true);
    }
});
