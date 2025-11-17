document.addEventListener('DOMContentLoaded', function() {
    // Inicializar los selectores
    ['documents', 'payroll', 'cae'].forEach(tabId => {
        const select = document.getElementById('yearFilter' + tabId);
        if (select) {
            select.addEventListener('change', function() {
                filterDocumentsByYear(tabId);
            });
        }
    });
});

function filterDocumentsByYear(tabId) {
    const yearSelect = document.getElementById('yearFilter' + tabId);
    const selectedYear = yearSelect.value;
    const tab = document.getElementById(tabId);
    const documents = tab.querySelectorAll('.card.shadow.mb-3');
    
    let visibleCount = 0;
    documents.forEach(doc => {
        const docForm = doc.querySelector('form');
        const docYear = docForm ? docForm.getAttribute('data-year') : null;
        
        if (!selectedYear || docYear === selectedYear) {
            doc.style.display = '';
            visibleCount++;
        } else {
            doc.style.display = 'none';
        }
    });

    // Actualizar el contador en la pestaña
    const badge = document.querySelector('#' + tabId + '-tab .badge');
    if (badge) {
        badge.textContent = visibleCount;
    }
}