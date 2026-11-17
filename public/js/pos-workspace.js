(function () {
    'use strict';
    const byId = (id) => document.getElementById(id);
    const sell = byId('pos-sell-screen');
    const checkout = byId('pos-checkout-screen');
    const editor = byId('pos-item-editor');
    const backdrop = byId('pos-editor-backdrop');
    let returnFocus = null;
    const customer = document.querySelector('.pos-receipt-toolbar');
    const customerHome = customer.parentNode;
    const customerNext = customer.nextSibling;
    function openEditor() {
        if (!editor.hidden) return;
        returnFocus = document.activeElement;
        editor.hidden = false;
        backdrop.hidden = false;
        document.body.classList.add('pos-item-editor-active');
        byId('pos-editor-close')?.focus();
    }
    function closeEditor() {
        editor.hidden = true;
        backdrop.hidden = true;
        document.body.classList.remove('pos-item-editor-active');
        if (returnFocus?.isConnected) returnFocus.focus();
    }
    function syncSummary() {
        document.querySelectorAll('[data-pos-copy]').forEach((node) => {
            const value = byId(node.dataset.posCopy)?.textContent || '';
            if (node.textContent !== value) node.textContent = value;
        });
        const items = byId('pos-checkout-items');
        items.replaceChildren();
        document.querySelectorAll('#cart-body tr').forEach((row) => {
            const line = document.createElement('div');
            line.className = 'pos-checkout-line';
            const name = document.createElement('span');
            const amount = document.createElement('strong');
            name.textContent = row.cells[0]?.textContent.trim();
            amount.textContent = row.cells[2]?.textContent.trim();
            line.append(name, amount);
            items.append(line);
        });
    }
    function showProducts() {
        sell.hidden = false;
        checkout.hidden = true;
        customerHome.insertBefore(customer, customerNext);
        closeEditor();
        if (location.hash === '#checkout') history.replaceState(null, '', location.pathname + location.search);
    }
    function openCheckout() {
        closeEditor();
        syncSummary();
        byId('pos-checkout-customer-slot').append(customer);
        sell.hidden = true;
        checkout.hidden = false;
        if (location.hash !== '#checkout') history.pushState(null, '', '#checkout');
        byId('pos-checkout-title').focus();
        window.scrollTo(0, 0);
    }
    async function addCustomer() {
        const trigger = document.querySelector('[data-pos-add-customer]');
        if (!trigger || !window.Swal) return false;
        const result = await window.Swal.fire({
            title: 'Add customer',
            html: `<form id="pos-add-customer-form" class="pos-add-customer-form">
                <p>Add a customer and continue this sale.</p>
                <label for="pos-new-customer-name">Customer name <span aria-hidden="true">*</span></label>
                <input id="pos-new-customer-name" name="customer_name" class="form-control" required maxlength="191" autocomplete="name">
                <label for="pos-new-customer-phone">Phone number</label>
                <input id="pos-new-customer-phone" name="phone" class="form-control" type="tel" maxlength="191" autocomplete="tel">
                <label for="pos-new-customer-email">Email address</label>
                <input id="pos-new-customer-email" name="email" class="form-control" type="email" maxlength="191" autocomplete="email">
            </form>`,
            showCancelButton: true,
            confirmButtonText: 'Save & select customer',
            confirmButtonColor: '#0f3a8a',
            showLoaderOnConfirm: true,
            focusConfirm: false,
            allowOutsideClick: () => !window.Swal.isLoading(),
            allowEscapeKey: () => !window.Swal.isLoading(),
            didOpen: () => {
                byId('pos-add-customer-form').addEventListener('submit', (event) => {
                    event.preventDefault();
                    window.Swal.clickConfirm();
                });
                byId('pos-new-customer-name').focus();
            },
            preConfirm: async () => {
                const form = byId('pos-add-customer-form');
                if (!form.reportValidity()) return false;
                try {
                    const response = await fetch(trigger.dataset.customerStoreUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify(Object.fromEntries(new FormData(form))),
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Could not add the customer. Please try again.');
                    if (!data.customer?.id || !data.customer?.name) throw new Error('Could not confirm the new customer. Please check the customer list before retrying.');
                    return data.customer;
                } catch (error) {
                    const message = document.createElement('span');
                    message.textContent = error.message || 'Could not add the customer. Check your connection and try again.';
                    window.Swal.showValidationMessage(message.innerHTML);
                    return false;
                }
            },
        });
        if (!result.isConfirmed) return false;
        document.dispatchEvent(new CustomEvent('pos:customer-created', { detail: result.value }));
        byId('pos-customer-created-feedback').textContent = `${result.value.name} added and selected.`;
        return true;
    }
    document.querySelector('[data-pos-add-customer]')?.addEventListener('click', addCustomer);
    document.querySelectorAll('.category-pill').forEach((pill) => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.category-pill').forEach((option) => option.classList.toggle('active', option === pill));
            window.PosProductSearch?.filterShelf(
                document.querySelectorAll('.product-card'),
                byId('quick-search')?.value || '',
                pill.dataset.category || 'all'
            );
        });
    });
    window.PosWorkspace = { openEditor, closeEditor, openCheckout, showProducts, addCustomer };
    byId('pos-start-checkout')?.addEventListener('click', () => byId('rail-checkout-btn')?.click());
    byId('pos-back-to-sale').addEventListener('click', () => {
        showProducts();
        byId('pos-start-checkout')?.focus();
    });
    window.addEventListener('popstate', () => {
        if (location.hash === '#checkout' && byId('cart-body').children.length) openCheckout();
        else showProducts();
    });
    // A fresh request cannot reconstruct an unsubmitted cart from the URL.
    if (location.hash === '#checkout') showProducts();
    byId('pos-editor-close').addEventListener('click', closeEditor);
    backdrop.addEventListener('click', closeEditor);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !editor.hidden) closeEditor();
        if (event.key === 'Tab' && !editor.hidden) {
            const nodes = Array.from(editor.querySelectorAll('button, input, select, [tabindex="0"]')).filter((node) => !node.disabled && node.getClientRects().length);
            const first = nodes[0], last = nodes[nodes.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
        if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('.product-card')) {
            event.preventDefault(); event.target.click();
        }
    });
    ['rail-scan-btn', 'rail-search-btn', 'rail-discount-btn'].forEach((id) => {
        byId(id)?.addEventListener('click', openEditor);
    });
    byId('pos-cart-clear').addEventListener('click', () => byId('rail-clear-cart-btn')?.click());
    byId('pos-pend-order')?.addEventListener('click', () => byId('payment-suspend-sale-btn')?.click());
    byId('quick-search').addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== 'ArrowDown') return;
        const query = event.currentTarget.value.trim();
        if (!query) return;
        const matches = Array.from(document.querySelectorAll('.product-card')).filter((card) => !card.hidden && card.style.display !== 'none');
        const ordered = window.PosProductSearch.rank(matches.map((card) => ({ ...card.dataset, card })), query);
        if (!ordered.length) return;
        event.preventDefault();
        const first = ordered[0].card;
        if (event.key === 'Enter' && (ordered.length === 1 || window.PosProductSearch.score(first.dataset, query) >= 900)) first.click();
        else first.focus();
    });
    byId('pos-search-reset').addEventListener('click', () => {
        const input = byId('quick-search');
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
    });
    ['grid', 'list'].forEach((mode) => byId(`pos-${mode}-view`).addEventListener('click', () => {
        byId('product-grid').classList.toggle('pos-list-view', mode === 'list');
        ['grid', 'list'].forEach((value) => byId(`pos-${value}-view`).setAttribute('aria-pressed', String(value === mode)));
    }));
    new MutationObserver(syncSummary).observe(document.querySelector('.pos-cart-column'), { childList: true, subtree: true, characterData: true });
    syncSummary();
})();
