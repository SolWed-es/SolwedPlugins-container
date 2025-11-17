/**
 * This file is part of HumanResources plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HumanResources Copyright (C) 2018-2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */

/**
 * Public const for confirmation text dialog and special operations.
 * The values are set in the TWIG template.
 */
var deleteCancel = "";
var deleteConfirm = "";
var deleteMessage = "";
var deleteTitle = "";
var currentWorkPeriods = [];

/**
 * Clear the attendance modal.
 */
function clearAttendanceManual()
{
    const modal = document.getElementById('modalAttendance');
    const dateInput = modal.querySelector('input[name="date"]');
    dateInput.disable = false;

    const timeHelp = modal.querySelector('#timeHelp');
    timeHelp.textContent = '';

    const kindSelect = modal.querySelector('select[name="kind"]');
    kindSelect.value = 1;

    const noteInput = modal.querySelector('input[name="note"]');
    noteInput.value = '';
}

/**
 * Exec a delete action.
 *
 * @param {String} action
 * @param {Number} id
 */
function deleteAction(action, id)
{
    const form = $("#formEmployeePanel");
    form.find(':input[name="action"]').val(action);
    form.append('<input type="hidden" name="idmodel" value="' + id + '"/>');
    form.submit();
}

/**
 * Delete an attendance.
 * First show a confirmation dialog.
 *
 * @param {String} model
 * @param {Number} id
 * @returns {boolean}
 */
function deleteActionConfirm(model, id)
{
    bootbox.confirm({
        title: deleteTitle,
        message: deleteMessage,
        closeButton: false,
        buttons: {
            cancel: {
                label: '<i class="fas fa-times"></i> ' + deleteCancel
            },
            confirm: {
                label: '<i class="fas fa-check"></i> ' + deleteConfirm,
                className: "btn-danger"
            }
        },
        callback: function (result) {
            if (result) {
                deleteAction("delete-" + model, id);
            }
        }
    });

    return false;
}

/**
 * Delete an attendance.
 *
 * @param {Number} id
 */
function deleteAttendance(id)
{
    deleteActionConfirm("attendance", id);
}

/**
 * Delete a holiday.
 *
 * @param {Number} id
 */
function deleteHoliday(id)
{
    deleteActionConfirm("holidays", id);
}

/**
 * Get the current geolocation.
 *
 * @returns {Promise<String>}
 */
async function getGeoLocation()
{
    if (!useGeolocation) {
        return '';
    }

    if (!navigator.geolocation) {
        return 'Not Available';
    }

    try {
        const position = await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        });

        return 'Latitud: ' + position.coords.latitude + ', Longitud: ' + position.coords.longitude;
    } catch (error) {
        switch (error.code) {
            case error.PERMISSION_DENIED:
                return 'User denied';
            case error.POSITION_UNAVAILABLE:
                return 'Location unavailable';
            case error.TIMEOUT:
                return 'Request timed out';
            default:
                return 'Not Allowed';
        }
    }
}

/**
 * Show the attendance modal for edit.
 *
 * @param {Element} button
 */
function incidenceAttendance(button) {
    const modal = document.getElementById('modalAttendance');
    const dateInput = modal.querySelector('input[name="date"]');
    dateInput.value = button.dataset.date;

    const timeHelp = modal.querySelector('#timeHelp');
    timeHelp.textContent = button.dataset.time;

    const kindSelect = modal.querySelector('select[name="kind"]');
    kindSelect.value = button.dataset.kind;

    const noteInput = modal.querySelector('input[name="note"]');
    noteInput.value = button.dataset.note;

    $(modal).modal('show');
}

/**
 * Insert a new attendance with the current date and indicate kind.
 *
 * @param {String} kind
 * @param {Boolean} adjust
 */
async function insertAttendance(kind, adjust)
{
    const location = await getGeoLocation();
    const localizacion = location;
    const form = $("#formEmployeePanel");
    form.find(':input[name="action"]').val("insert-attendance");
    form.append('<input type="hidden" name="origin" value="3"/>');
    form.append('<input type="hidden" name="kind" value="' + kind + '"/>');
    form.append('<input type="hidden" name="location" value="' + location + '"/>');
    form.append('<input type="hidden" name="localizacion" value="' + localizacion + '"/>');
    form.append('<input type="hidden" name="adjust" value="' + adjust + '"/>');
    form.submit();
}

/**
 * Insert a new attendance with the employee dates.
 */
async function insertAttendanceManual()
{
    const location = await getGeoLocation();
    const localizacion = location;
    const form = $("#formEmployeePanel");
    form.find(':input[name="action"]').val("insert-attendance");
    form.append('<input type="hidden" name="origin" value="1"/>');
    form.append('<input type="hidden" name="location" value="' + location + '"/>');
    form.append('<input type="hidden" name="localizacion" value="' + localizacion + '"/>');
    form.append('<input type="hidden" name="adjust" value="false"/>');
    form.submit();
}

/**
 * Insert a new attendance with the employee decision of if the extra time is computable.
 *
 * @param {Element} button
 */
function insertComputableAttendance(button)
{
    const computable = button.getAttribute('data-computable');
    const kind= button.getAttribute('data-kind');
    insertAttendance(kind, computable === 'false');
}

/**
 * Insert a new holiday with the current date.
 */
function insertHolidays()
{
    const form = $("#formEmployeePanel");
    form.find(':input[name="action"]').val("insert-holidays");

    const startValue = form.find(':input[name="startdate"]').val();
    const yearField = form.find(':input[name="applyto"]');
    if (startValue && yearField.length && !yearField.val()) {
        const parsedYear = new Date(startValue).getFullYear();
        if (!isNaN(parsedYear)) {
            yearField.val(parsedYear);
        }
    }

    form.submit();
}

/**
 * Reload the attendance list for date.
 *
 * @param {String} action
 * @param {String} date
 */
function showAttendance(action, date)
{
    const form = $("#formEmployeePanel");
    form.find(':input[name="action"]').val(action + "-attendance");
    form.append('<input type="hidden" name="selectDate" value="' + date + '"/>');
    form.submit();
}

/**
 * Show the computable modal windows to confirm extra time.
 * First check if the current time is into a work period.
 * If yes, insert the attendance without adjusting to the work period.
 * If not, show a computable question to user.
 *
 * @param {String} kind
 */
function showComputableModal(kind)
{
    // Check if the current time is into a work period.
    const now = new Date();
    const nowTime = now.getHours() + ':' + now.getMinutes();
    for (const period of currentWorkPeriods) {
        if (period.starttime <= nowTime && period.endtime >= nowTime) {
            insertAttendance(kind, false);
            return;
        }
    }

    // Show the computable question.
    document.getElementById('btnComputable').setAttribute('data-kind', kind);
    document.getElementById('btnNotComputable').setAttribute('data-kind', kind);
    $(document.querySelector('#modalComputable')).modal({
        backdrop: 'static',
        keyboard: false,
        focus: true,
        show: true,
    });
}

/**
 * Set the use of geolocation.
 *
 * @param {boolean} value
 */
function setGeolocation(value)
{
    useGeolocation = value;
}

/**
 * Main process.
 *  - Control enters key in noEnterKey class inputs.
 *  - Handle button clicks for attendance
 */
$(document).ready(function ()
{
    $(".noEnterKey").keypress(function (e) {
        return !(e.which === 13 || e.keyCode === 13);
    });

    $('#btnInput, #btnOutput').click(function() {
        var $btn = $(this);

        $btn.prop('disabled', true);

        var action = $btn.data('action');
        var type = $btn.data('type');

        if (action === 'showComputableModal') {
            showComputableModal(type);
        } else {
            insertAttendance(type, false);
        }

        setTimeout(function() {
            $btn.prop('disabled', false);
        }, 2000);
    });

    setGeolocation(true);
});
