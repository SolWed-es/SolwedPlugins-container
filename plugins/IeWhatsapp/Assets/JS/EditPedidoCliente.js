document.addEventListener('DOMContentLoaded', function() {
    // Wait for modal to be in DOM before attaching events
    const modalObserver = new MutationObserver(function(mutations, observer) {
        const whatsappModal = document.getElementById('whatsappModal');
        if (whatsappModal) {
            // Initialize when modal is found
            initWhatsappModal();
            observer.disconnect(); // Stop observing once found
        }
    });

    // Start observing the document body for changes
    modalObserver.observe(document.body, {
        childList: true,
        subtree: true
    });

    function initWhatsappModal() {
        // Initialize visibility
        updateCustomPhoneVisibility();

        // Phone select change handler
        document.getElementById('whatsappPhone').addEventListener('change', updateCustomPhoneVisibility);

        // Send button handler
        document.getElementById('sendWhatsappBtn').addEventListener('click', function() {
            const phone = getSelectedPhone();
            const prefix = document.getElementById('whatsappPrefix').value.replace(/\D/g, '');
            const msg = encodeURIComponent(document.getElementById('whatsappMessage').value);

            if (!phone || phone.length < 6) {
                alert('Por favor, introduce un número de teléfono válido.');
                return;
            }

            window.open(`https://wa.me/${prefix}${phone}?text=${msg}`, '_blank');
            bootstrap.Modal.getInstance(document.getElementById('whatsappModal')).hide();
        });
    }

    function updateCustomPhoneVisibility() {
        const select = document.getElementById('whatsappPhone');
        const customGroup = document.getElementById('customPhoneGroup');

        if (select && select.value === 'custom') {
            customGroup.classList.remove('d-none');
            document.getElementById('customPhoneInput').focus();
        } else if (customGroup) {
            customGroup.classList.add('d-none');
        }
    }
});

// Keep these global functions
function getSelectedPhone() {
    const select = document.getElementById('whatsappPhone');
    if (!select) return '';

    const selected = select.value;
    if (selected === 'custom') {
        const customInput = document.getElementById('customPhoneInput');
        return customInput ? customInput.value.replace(/\D/g, '') : '';
    }
    return selected.replace(/\D/g, '');
}

function copyWhatsAppMessage() {
    const msgEl = document.getElementById('whatsappMessage');
    if (!msgEl) return;

    const msg = msgEl.value;
    if (!msg.trim()) {
        alert('No hay mensaje para copiar.');
        return;
    }

    navigator.clipboard.writeText(msg).then(() => {
        alert('Mensaje copiado al portapapeles.');
    }).catch(() => {
        // Fallback for older browsers
        msgEl.select();
        document.execCommand('copy');
        alert('Mensaje copiado');
    });
}