(() => {
    function initializeInvoiceCustomerForm() {
        const form = document.getElementById('invoice-customer-form');
        const modalElement = document.getElementById('invoice-customer-modal');
        const customerSelect = document.querySelector('#invoice-form .customer-select');
        if (!form || !modalElement || !customerSelect) return;

        const errors = document.getElementById('invoice-customer-errors');
        const confirmation = document.getElementById('invoice-customer-created-message');
        const nameInput = form.querySelector('[name="customer_name"]');
        const saveButton = form.querySelector('[type="submit"]');
        let saving = false;

        modalElement.addEventListener('shown.bs.modal', () => nameInput.focus());
        modalElement.addEventListener('hide.bs.modal', event => {
            if (saving) event.preventDefault();
        });

        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (saving || !form.reportValidity()) return;

            saving = true;
            const originalLabel = saveButton.textContent;
            const controls = form.querySelectorAll('[type="submit"], [data-bs-dismiss="modal"]');
            controls.forEach(button => { button.disabled = true; });
            saveButton.textContent = 'Saving...';
            errors.classList.add('d-none');
            errors.replaceChildren();

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const payload = await response.json().catch(() => null);
                if (!response.ok || !payload?.success || !payload.customer?.id) {
                    const messages = payload?.errors ? Object.values(payload.errors).flat() : [];
                    throw new Error(messages.length ? messages.join('\n') : (payload?.message || 'Could not save the customer. Please try again.'));
                }

                const customer = payload.customer;
                // Restore options hidden by a previous search before adding the new customer.
                const search = document.getElementById('invoice-customer-search');
                if (search) search.value = '';
                if (typeof filterInvoiceCustomerOptions === 'function') filterInvoiceCustomerOptions('');

                let option = Array.from(customerSelect.options).find(item => item.value === String(customer.id));
                if (!option) {
                    option = new Option(customer.name, String(customer.id));
                    customerSelect.add(option);
                }
                option.textContent = customer.name;
                option.dataset.priceListId = customer.price_list_id || '';
                option.dataset.outstandingBalance = String(customer.outstanding_balance || 0);
                customerSelect.value = String(customer.id);
                if (typeof snapshotCustomerOptions === 'function') snapshotCustomerOptions();
                // Refresh the selector without recalculating or replacing invoice line items.
                if (window.jQuery && window.jQuery.fn.select2) {
                    window.jQuery(customerSelect).trigger('change.select2');
                }
                if (typeof updateCustomerBalanceDisplay === 'function') updateCustomerBalanceDisplay();

                confirmation.textContent = `${customer.name} added and selected.`;
                confirmation.classList.remove('d-none');
                customerSelect.classList.add('border-success');
                customerSelect.nextElementSibling?.querySelector('.select2-selection')?.classList.add('border-success');
                form.reset();
                saving = false;
                window.bootstrap.Modal.getInstance(modalElement)?.hide();
            } catch (error) {
                errors.textContent = error.message || 'Could not save the customer. Please try again.';
                errors.style.whiteSpace = 'pre-line';
                errors.classList.remove('d-none');
                errors.focus();
            } finally {
                saving = false;
                controls.forEach(button => { button.disabled = false; });
                saveButton.textContent = originalLabel;
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeInvoiceCustomerForm);
    } else {
        initializeInvoiceCustomerForm();
    }
})();
