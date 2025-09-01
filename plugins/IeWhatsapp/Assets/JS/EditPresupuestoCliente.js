// WhatsApp Modal Handler - Optimized Version
(function() {
    'use strict';

    // Configuration constants
    const CONFIG = {
        MIN_PHONE_LENGTH: 6,
        MAX_OBSERVER_ATTEMPTS: 50,
        OBSERVER_TIMEOUT: 10000, // 10 seconds
        DEBUG: true // Set to false in production to disable logs
    };

    // Debug logging function
    function debugLog(message, ...args) {
        if (CONFIG.DEBUG) {
            console.log(`WhatsApp Modal: ${message}`, ...args);
        }
    }

    function debugWarn(message, ...args) {
        if (CONFIG.DEBUG) {
            console.warn(`WhatsApp Modal: ${message}`, ...args);
        }
    }

    function debugError(message, ...args) {
        if (CONFIG.DEBUG) {
            console.error(`WhatsApp Modal: ${message}`, ...args);
        }
    }

    // Cached DOM elements
    let elements = {};
    let observerAttempts = 0;
    let observerTimer = null;

    debugLog('Initializing...');

    // Check if script is loaded
    debugLog('Script loaded at:', new Date().toISOString());
    debugLog('Document ready state:', document.readyState);
    debugLog('Window location:', window.location.href);

    // Multiple initialization strategies
    function initializeScript() {
        debugLog('DOM Content Loaded');
        debugLog('Document body exists:', !!document.body);
        debugLog('Bootstrap available:', typeof bootstrap !== 'undefined');

        startModalObserver();
    }

    // Try different initialization approaches
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeScript);
    } else {
        // DOM is already ready
        debugLog('DOM already ready, initializing immediately');
        initializeScript();
    }

    // Fallback initialization
    window.addEventListener('load', function() {
        debugLog('Window load event fired');
        if (observerAttempts === 0) {
            debugLog('No observer attempts yet, starting fallback initialization');
            startModalObserver();
        }
    });

    function startModalObserver() {
        debugLog('Starting mutation observer');
        debugLog('Observer attempts reset to 0');
        observerAttempts = 0;

        // Check if modal already exists
        const existingModal = document.getElementById('whatsappModal');
        if (existingModal) {
            debugLog('Modal already exists in DOM, initializing directly');
            initWhatsappModal();
            return;
        }

        // Set up timeout fallback
        observerTimer = setTimeout(() => {
            debugWarn('Observer timeout reached, stopping observation');
            if (modalObserver) {
                modalObserver.disconnect();
            }
        }, CONFIG.OBSERVER_TIMEOUT);

        const modalObserver = new MutationObserver(function(mutations, observer) {
            observerAttempts++;
            debugLog(`Observer attempt ${observerAttempts}/${CONFIG.MAX_OBSERVER_ATTEMPTS}`);

            const whatsappModal = document.getElementById('whatsappModal');

            if (whatsappModal) {
                debugLog('Modal found in DOM');
                clearTimeout(observerTimer);
                initWhatsappModal();
                observer.disconnect();
            } else if (observerAttempts >= CONFIG.MAX_OBSERVER_ATTEMPTS) {
                debugWarn('Max attempts reached, stopping observation');
                clearTimeout(observerTimer);
                observer.disconnect();
            }
        });

        // Check if document.body exists
        if (!document.body) {
            debugError('Document body not available, waiting...');
            setTimeout(() => startModalObserver(), 100);
            return;
        }

        // Start observing the document body for changes
        modalObserver.observe(document.body, {
            childList: true,
            subtree: true
        });

        debugLog('MutationObserver started on document.body');
    }

    function cacheElements() {
        debugLog('Caching DOM elements');

        elements = {
            modal: document.getElementById('whatsappModal'),
            phoneSelect: document.getElementById('whatsappPhone'),
            customGroup: document.getElementById('customPhoneGroup'),
            customInput: document.getElementById('customPhoneInput'),
            sendBtn: document.getElementById('sendWhatsappBtn'),
            prefix: document.getElementById('whatsappPrefix'),
            message: document.getElementById('whatsappMessage')
        };

        // Log which elements were found
        Object.keys(elements).forEach(key => {
            if (elements[key]) {
                debugLog(`✓ Found ${key}`);
            } else {
                debugWarn(`✗ Missing ${key}`);
            }
        });

        return elements;
    }

    function validateElements() {
        const requiredElements = ['modal', 'phoneSelect', 'sendBtn', 'prefix', 'message'];
        const missing = requiredElements.filter(key => !elements[key]);

        if (missing.length > 0) {
            debugError('Missing required elements:', missing);
            return false;
        }

        debugLog('All required elements found');
        return true;
    }

    function initWhatsappModal() {
        debugLog('Initializing modal functionality');

        // Cache all elements
        cacheElements();

        // Validate required elements exist
        if (!validateElements()) {
            debugError('Initialization failed - missing required elements');
            return;
        }

        // Remove existing event listeners to prevent duplicates
        cleanupEventListeners();

        try {
            // Initialize visibility
            updateCustomPhoneVisibility();

            // Phone select change handler
            elements.phoneSelect.addEventListener('change', handlePhoneSelectChange);
            debugLog('Phone select change handler attached');

            // Send button handler
            elements.sendBtn.addEventListener('click', handleSendClick);
            debugLog('Send button handler attached');

            debugLog('Initialization complete');

            // Test alert to verify JavaScript is working
            if (CONFIG.DEBUG) {
                debugLog('Modal initialization successful - JavaScript is working');
            }

        } catch (error) {
            debugError('Error during initialization:', error);
        }
    }

    function cleanupEventListeners() {
        debugLog('Cleaning up existing event listeners');

        if (elements.phoneSelect) {
            elements.phoneSelect.removeEventListener('change', handlePhoneSelectChange);
        }
        if (elements.sendBtn) {
            elements.sendBtn.removeEventListener('click', handleSendClick);
        }
    }

    function handlePhoneSelectChange() {
        debugLog('Phone select changed to:', elements.phoneSelect.value);
        updateCustomPhoneVisibility();
    }

    function handleSendClick() {
        debugLog('Send button clicked');

        try {
            const phone = getSelectedPhone();
            const prefix = elements.prefix.value.replace(/\D/g, '');
            const msg = encodeURIComponent(elements.message.value);

            debugLog('Phone:', phone, 'Prefix:', prefix, 'Message length:', elements.message.value.length);

            if (!phone || phone.length < CONFIG.MIN_PHONE_LENGTH) {
                debugWarn('Invalid phone number');
                alert('Por favor, introduce un número de teléfono válido.');
                return;
            }

            const whatsappUrl = `https://wa.me/${prefix}${phone}?text=${msg}`;
            debugLog('Opening URL:', whatsappUrl);

            window.open(whatsappUrl, '_blank');

            // Hide modal
            const modalInstance = bootstrap.Modal.getInstance(elements.modal);
            if (modalInstance) {
                modalInstance.hide();
                debugLog('Modal hidden');
            } else {
                debugWarn('Could not get modal instance');
            }

        } catch (error) {
            debugError('Error in send handler:', error);
        }
    }

    function updateCustomPhoneVisibility() {
        debugLog('Updating custom phone visibility');

        if (!elements.phoneSelect) {
            debugError('Phone select not available');
            return;
        }

        const isCustomSelected = elements.phoneSelect.value === 'custom';
        debugLog('Custom phone selected:', isCustomSelected);

        if (elements.customGroup) {
            if (isCustomSelected) {
                elements.customGroup.classList.remove('d-none');
                if (elements.customInput) {
                    elements.customInput.focus();
                    debugLog('Custom input focused');
                }
            } else {
                elements.customGroup.classList.add('d-none');
            }
        } else {
            debugWarn('Custom group element not found');
        }
    }

    function getSelectedPhone() {
        debugLog('Getting selected phone');

        if (!elements.phoneSelect) {
            debugError('Phone select not available');
            return '';
        }

        const selected = elements.phoneSelect.value;
        debugLog('Selected value:', selected);

        if (selected === 'custom') {
            if (!elements.customInput) {
                debugError('Custom input not available');
                return '';
            }
            const customPhone = elements.customInput.value.replace(/\D/g, '');
            debugLog('Custom phone:', customPhone);
            return customPhone;
        }

        const phone = selected.replace(/\D/g, '');
        debugLog('Selected phone:', phone);
        return phone;
    }

    function copyWhatsAppMessage() {
        debugLog('Copy message requested');

        const msgEl = elements.message || document.getElementById('whatsappMessage');
        if (!msgEl) {
            debugError('Message element not found');
            return;
        }

        const msg = msgEl.value;
        debugLog('Message length:', msg.length);

        if (!msg.trim()) {
            debugWarn('No message to copy');
            alert('No hay mensaje para copiar.');
            return;
        }

        // Try modern clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(msg).then(() => {
                debugLog('Message copied via clipboard API');
                alert('Mensaje copiado al portapapeles.');
            }).catch((error) => {
                debugError('Clipboard API failed:', error);
                fallbackCopy(msgEl, msg);
            });
        } else {
            debugLog('Using fallback copy method');
            fallbackCopy(msgEl, msg);
        }
    }

    function fallbackCopy(msgEl, msg) {
        try {
            msgEl.select();
            const success = document.execCommand('copy');
            if (success) {
                debugLog('Message copied via fallback method');
                alert('Mensaje copiado');
            } else {
                debugError('Fallback copy failed');
                alert('Error al copiar el mensaje');
            }
        } catch (error) {
            debugError('Fallback copy error:', error);
            alert('Error al copiar el mensaje');
        }
    }

    // Expose global functions with namespace
    window.WhatsAppModal = {
        getSelectedPhone: getSelectedPhone,
        copyMessage: copyWhatsAppMessage,
        // Debug functions for troubleshooting
        reinitialize: function() {
            debugLog('Manual reinitialization requested');
            startModalObserver();
        },
        checkElements: function() {
            debugLog('Manual element check requested');
            cacheElements();
            return elements;
        },
        toggleDebug: function() {
            CONFIG.DEBUG = !CONFIG.DEBUG;
            debugLog('Debug mode toggled:', CONFIG.DEBUG);
        }
    };

    debugLog('Global functions exposed under WhatsAppModal namespace');

    // Global error handler to catch any uncaught errors
    window.addEventListener('error', function(e) {
        debugError('Global error caught:', e.error, 'at', e.filename, ':', e.lineno);
    });

    // Test if console is available
    if (typeof console === 'undefined') {
        debugError('Console not available in this environment');
    }

})();