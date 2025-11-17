/**
 * This file is part of HumanResources plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HumanResources Copyright (C) 2018-2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize year selectors
    ['documents', 'payroll', 'cae'].forEach(tabId => {
        const select = document.getElementById('yearFilter' + tabId);
        if (select) {
            select.addEventListener('change', function() {
                filterDocumentsByYear(tabId);
            });
        }
    });
});

/**
 * Filters documents by year in the specified tab
 * 
 * @param {string} tabId - Tab ID ('documents', 'payroll', 'cae')
 */
function filterDocumentsByYear(tabId) {
    const yearSelect = document.getElementById('yearFilter' + tabId);
    if (!yearSelect) return;

    const selectedYear = yearSelect.value;
    const tab = document.getElementById(tabId);
    if (!tab) return;

    // Find all document cards within the tab
    const documents = tab.querySelectorAll('.card.shadow.mb-3');
    let visibleCount = 0;

    documents.forEach(card => {
        // Find the year input within the card
        const yearInput = card.querySelector('input[name="year_group"]');
        if (yearInput) {
            const docYear = yearInput.value;
            
            if (!selectedYear || docYear === selectedYear) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        }
    });

    // Update the counter in the tab
    const badge = document.querySelector('#' + tabId + '-tab .badge');
    if (badge) {
        badge.textContent = visibleCount;
    }
}