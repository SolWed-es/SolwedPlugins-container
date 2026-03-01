document.addEventListener('change', function (e) {
    if (e.target && e.target.matches('select[name^="proveedorpersonalizado_"]')) {
        updateCosteFromProveedor(e.target);
    }
});


function updateCosteFromProveedor(selectElement) {
    const selected = selectElement.options[selectElement.selectedIndex];
    const coste = selected.dataset.coste;
    const lineId = selectElement.name.split('_')[1];
    const costeInput = document.querySelector(`input[name="costepersonalizado_${lineId}"]`);
    if (coste && costeInput) {
        costeInput.value = coste;

        // Trigger change to ensure it's seen by FS
        const event = new Event('change', { bubbles: true });
        costeInput.dispatchEvent(event);

        // Trigger recalculation
        salesFormActionWait('recalculate-line', '0', event);
    }
}