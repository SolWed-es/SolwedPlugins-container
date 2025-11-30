/**
 * JavaScript para la funcionalidad de dominios en el portal cliente
 * Plugin Dominios para FacturaScripts
 */

class DominiosPortalDomains {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // AuthCode buttons
        document.querySelectorAll('.domain-authcode-button').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.showAuthCode(button);
            });
        });

        // Transfer lock buttons
        document.querySelectorAll('.domain-transferlock-button').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleTransferLock(button);
            });
        });

        // Download autorenew contract buttons
        document.querySelectorAll('.download-autorenew-contract').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.downloadAutorenewContract(button);
            });
        });

        // Copy authcode button
        const copyButton = document.getElementById('authcodeCopy');
        if (copyButton) {
            copyButton.addEventListener('click', () => {
                this.copyAuthCode();
            });
        }
    }

    showAuthCode(button) {
        const domainId = button.getAttribute('data-domain');
        const contactId = button.getAttribute('data-contact');
        const token = button.getAttribute('data-token');

        if (!domainId || !contactId || !token) {
            this.showAlert('Parámetros inválidos', 'danger');
            return;
        }

        // Show loading in modal
        const modal = new bootstrap.Modal(document.getElementById('authcodeModal'));
        const authcodeValue = document.getElementById('authcodeValue');
        const authcodeAlert = document.getElementById('authcodeAlert');

        authcodeValue.value = 'Cargando...';
        authcodeAlert.classList.add('d-none');
        modal.show();

        // Make AJAX request
        const formData = new FormData();
        formData.append('action', 'get-authcode');
        formData.append('domain', domainId);

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.authcode) {
                authcodeValue.value = data.authcode;
                authcodeAlert.classList.add('d-none');
            } else {
                authcodeValue.value = '';
                this.showAlert(data.error || 'Error al obtener el código de autorización', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            authcodeValue.value = '';
            this.showAlert('Error de conexión', 'danger');
        });
    }

    toggleTransferLock(button) {
        const domainId = button.getAttribute('data-domain');
        const contactId = button.getAttribute('data-contact');
        const token = button.getAttribute('data-token');
        const confirmEnable = button.getAttribute('data-confirm-enable');
        const confirmDisable = button.getAttribute('data-confirm-disable');
        const enabledLabel = button.getAttribute('data-enabled-label');
        const disabledLabel = button.getAttribute('data-disabled-label');

        if (!domainId || !contactId || !token) {
            this.showAlert('Parámetros inválidos', 'danger');
            return;
        }

        // Determine current state and confirmation message
        const labelElement = button.querySelector('.transfer-lock-label');
        const isEnabled = labelElement.textContent.trim() === enabledLabel;
        const confirmMessage = isEnabled ? confirmDisable : confirmEnable;
        const newState = !isEnabled;

        if (!confirm(confirmMessage)) {
            return;
        }

        // Show loading
        const originalText = labelElement.textContent;
        labelElement.textContent = 'Procesando...';
        button.disabled = true;

        // Make AJAX request
        const formData = new FormData();
        formData.append('action', 'toggle-transfer-lock');
        formData.append('domain', domainId);
        formData.append('enable', newState ? 'true' : 'false');

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                labelElement.textContent = newState ? enabledLabel : disabledLabel;
                this.showAlert(data.message || 'Operación completada', 'success');
            } else {
                labelElement.textContent = originalText;
                this.showAlert(data.error || 'Error en la operación', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            labelElement.textContent = originalText;
            this.showAlert('Error de conexión', 'danger');
        })
        .finally(() => {
            button.disabled = false;
        });
    }

    downloadAutorenewContract(button) {
        const domainId = button.getAttribute('data-domain');
        const contactId = button.getAttribute('data-contact');
        const token = button.getAttribute('data-token');

        if (!domainId || !contactId || !token) {
            this.showAlert('Parámetros inválidos', 'danger');
            return;
        }

        // For now, just redirect to the contract URL
        // In a real implementation, this might generate a custom contract
        const contractUrl = 'https://filedn.eu/litOB0SUT8q5aLOM933djFm/Contrato%20domiciliaci%C3%B3n-Solwed.pdf';
        window.open(contractUrl, '_blank');
    }

    copyAuthCode() {
        const authcodeValue = document.getElementById('authcodeValue');

        if (!authcodeValue || !authcodeValue.value) {
            this.showAlert('No hay código para copiar', 'warning');
            return;
        }

        navigator.clipboard.writeText(authcodeValue.value).then(() => {
            this.showAlert('Código copiado al portapapeles', 'success');
        }).catch(() => {
            // Fallback for older browsers
            authcodeValue.select();
            document.execCommand('copy');
            this.showAlert('Código copiado al portapapeles', 'success');
        });
    }

    showAlert(message, type = 'info') {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Add to body
        document.body.appendChild(alertDiv);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);

        // Initialize Bootstrap alert
        if (typeof bootstrap !== 'undefined') {
            new bootstrap.Alert(alertDiv);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    new DominiosPortalDomains();
});

// Export for potential use in other scripts
window.DominiosPortalDomains = DominiosPortalDomains;
