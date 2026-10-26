(function () {
    'use strict';

    const config = window.POS_OFFLINE_CONFIG || {};
    if (!config.saleUrl || !window.indexedDB) return;

    const databaseName = 'smartprobook-pos';
    const storeName = 'pending-sales';
    const inventoryStoreName = 'inventory-snapshots';
    const catalogueUrl = config.catalogueUrl;
    const companyId = String(config.companyId || '');
    const branchId = String(config.branchId || config.branchName || '');
    const contextKey = `${companyId}:${branchId}`;
    let syncPromise = null;
    let refreshPromise = null;

    function openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(databaseName, 3);
            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains(storeName)) {
                    db.createObjectStore(storeName, { keyPath: 'client_sale_id' });
                }
                if (!db.objectStoreNames.contains(inventoryStoreName)) {
                    db.createObjectStore(inventoryStoreName, { keyPath: 'key' });
                }
                if (!db.objectStoreNames.contains('offline-credentials')) {
                    db.createObjectStore('offline-credentials', { keyPath: 'key' });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async function transact(targetStore, mode, action) {
        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(targetStore, mode);
            const request = action(transaction.objectStore(targetStore));
            let result;
            request.onsuccess = () => {
                result = request.result;
            };
            request.onerror = () => reject(request.error);
            transaction.oncomplete = () => {
                db.close();
                resolve(result);
            };
            transaction.onerror = () => {
                db.close();
                reject(transaction.error);
            };
            transaction.onabort = () => {
                db.close();
                reject(transaction.error || new Error('Offline data could not be saved.'));
            };
        });
    }

    const all = () => transact(storeName, 'readonly', (store) => store.getAll());
    const put = (sale) => transact(storeName, 'readwrite', (store) => store.put(sale));
    const remove = (id) => transact(storeName, 'readwrite', (store) => store.delete(id));
    const allInventory = () => transact(inventoryStoreName, 'readonly', (store) => store.getAll());
    const putInventory = (record) => transact(inventoryStoreName, 'readwrite', (store) => store.put(record));
    const removeInventory = (key) => transact(inventoryStoreName, 'readwrite', (store) => store.delete(key));

    function uuid() {
        if (crypto.randomUUID) return crypto.randomUUID();
        return 'pos-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    function belongsToActiveContext(sale) {
        return String(sale.offline_company_id || '') === companyId
            && String(sale.offline_branch_id || sale.offline_branch_name || '') === branchId;
    }

    function productId(item) {
        return String(item.id || item.product_id || '');
    }

    function stockUnits(item) {
        return Number(item.stockUnits ?? item.stock_units ?? item.qty ?? item.quantity ?? 0);
    }

    function updateProductStock(id, stock) {
        document.querySelectorAll('.product-card').forEach((card) => {
            if (String(card.dataset.id || '') !== id) return;
            card.dataset.stock = String(stock);
            card.dataset.outOfStock = stock <= 0 ? '1' : '0';
            card.setAttribute('aria-disabled', stock <= 0 ? 'true' : 'false');
            card.style.opacity = stock <= 0 ? '.55' : '';
            card.style.filter = stock <= 0 ? 'grayscale(.15)' : '';
            if (window.jQuery) window.jQuery(card).data('stock', stock);
        });

        const option = Array.from(document.querySelectorAll('#product-select option'))
            .find((candidate) => String(candidate.value) === id);
        if (option) {
            option.dataset.stock = String(stock);
            if (window.jQuery) window.jQuery(option).data('stock', stock);
        }

        if (String(document.getElementById('product-select')?.value || '') === id) {
            const quickStock = document.getElementById('quick-selected-stock');
            if (quickStock) quickStock.textContent = String(stock);
        }
    }

    async function applySaleToInventory(sale) {
        const records = (await allInventory()).filter((record) => record.context_key === contextKey);
        const stockByProduct = new Map(records.map((record) => [String(record.product_id), record]));
        for (const item of sale.items || []) {
            const id = productId(item);
            const amount = stockUnits(item);
            const record = stockByProduct.get(id);
            if (!id || !record || !Number.isFinite(amount) || amount <= 0) continue;
            record.stock = Math.max(0, Number(record.stock || 0) - amount);
            await putInventory(record);
            updateProductStock(id, record.stock);
        }
    }

    function replaceCatalogue(sourceDocument) {
        const currentGrid = document.getElementById('product-grid');
        const currentSelect = document.getElementById('product-select');
        const freshGrid = sourceDocument.getElementById('product-grid');
        const freshSelect = sourceDocument.getElementById('product-select');
        if (!currentGrid || !currentSelect || !freshGrid || !freshSelect
            || !sourceDocument.querySelector('.pos-product-shelf-card')) {
            throw new Error('The latest product catalogue could not be loaded. Reconnect and retry.');
        }

        const selectedId = String(currentSelect.value || '');
        currentGrid.replaceChildren(...Array.from(freshGrid.children, (child) => document.importNode(child, true)));
        currentSelect.replaceChildren(...Array.from(freshSelect.children, (child) => document.importNode(child, true)));
        if (selectedId && Array.from(currentSelect.options).some((option) => option.value === selectedId)) {
            currentSelect.value = selectedId;
            if (window.jQuery) window.jQuery(currentSelect).trigger('change');
        }

        const categoryPills = document.getElementById('category-pills');
        const freshCategoryPills = sourceDocument.getElementById('category-pills');
        if (categoryPills && freshCategoryPills) {
            categoryPills.replaceChildren(...Array.from(freshCategoryPills.children, (child) => document.importNode(child, true)));
        }

        window._posComboRefresh?.();
        window.POS_REFRESH_OFFLINE_PRODUCTS?.();
        if (window.jQuery) {
            window.jQuery(document).trigger('pos:catalogue-refreshed');
            window.jQuery('#quick-search').trigger('input');
        }
    }

    async function pullCatalogue() {
        if (!navigator.onLine || !catalogueUrl) return;
        const response = await fetch(catalogueUrl, {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok || response.redirected) {
            throw new Error('Could not refresh products from the online workspace.');
        }
        const sourceDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
        replaceCatalogue(sourceDocument);

        const products = new Map();
        sourceDocument.querySelectorAll('.product-card[data-id]').forEach((card) => {
            const id = String(card.dataset.id || '');
            const stock = Number(card.dataset.stock);
            if (id && Number.isFinite(stock)) products.set(id, stock);
        });
        sourceDocument.querySelectorAll('#product-select option[value]').forEach((option) => {
            const id = String(option.value || '');
            const stock = Number(option.dataset.stock);
            if (id && Number.isFinite(stock)) products.set(id, stock);
        });

        const existing = (await allInventory()).filter((record) => record.context_key === contextKey);
        const presentIds = new Set(products.keys());
        for (const record of existing) {
            if (!presentIds.has(String(record.product_id))) await removeInventory(record.key);
        }
        for (const [id, stock] of products) {
            await putInventory({
                key: `${contextKey}:${id}`,
                context_key: contextKey,
                product_id: id,
                stock,
                refreshed_at: new Date().toISOString(),
            });
        }

        const queued = (await all()).filter(belongsToActiveContext);
        for (const sale of queued) await applySaleToInventory(sale);
        const records = (await allInventory()).filter((record) => record.context_key === contextKey);
        records.forEach((record) => updateProductStock(String(record.product_id), Number(record.stock || 0)));
    }

    async function initializeInventory() {
        if (navigator.onLine) {
            await sync();
            try {
                await pullCatalogue();
            } catch (error) {
                console.error('POS could not refresh the online product catalogue.', error);
            }
        } else {
            const records = (await allInventory()).filter((record) => record.context_key === contextKey);
            records.forEach((record) => updateProductStock(String(record.product_id), Number(record.stock || 0)));
        }
    }

    async function updateIndicator() {
        const indicator = document.getElementById('pos-offline-status');
        if (!indicator) return;
        const sales = (await all()).filter(belongsToActiveContext);
        const queued = sales.filter((sale) => sale.sync_status !== 'needs_review').length;
        const review = sales.filter((sale) => sale.sync_status === 'needs_review').length;
        indicator.classList.toggle('is-offline', !navigator.onLine);
        indicator.classList.toggle('has-review', review > 0);
        indicator.textContent = !navigator.onLine
            ? `Offline${queued ? ` - ${queued} queued` : ''}`
            : review ? `${review} sale${review === 1 ? '' : 's'} need review`
                : queued ? `Syncing ${queued} sale${queued === 1 ? '' : 's'}` : 'Online';
        indicator.title = review
            ? 'A queued sale could not synchronize. Click to review stock, account, or business/branch details.'
            : 'POS connection and offline sale queue status';
    }

    async function send(payload) {
        const response = await fetch(config.saleUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(result.message || 'Sale synchronization failed.');
            error.responseReceived = true;
            error.status = response.status;
            throw error;
        }
        return result;
    }

    async function queue(payload) {
        const queued = {
            ...payload,
            client_sale_id: payload.client_sale_id || uuid(),
            client_recorded_at: payload.client_recorded_at || new Date().toISOString(),
            offline_company_id: companyId,
            offline_branch_id: String(config.branchId || ''),
            offline_branch_name: String(config.branchName || ''),
            sync_status: 'pending',
            sync_error: null,
        };
        await put(queued);
        await applySaleToInventory(queued);
        await updateIndicator();
        return queued;
    }

    async function sync() {
        if (!navigator.onLine) return;
        if (syncPromise) return syncPromise;
        syncPromise = (async () => {
            try {
                const pending = (await all()).filter((sale) => belongsToActiveContext(sale)
                    && sale.sync_status !== 'needs_review');
                for (const sale of pending) {
                    try {
                        await send(sale);
                        await remove(sale.client_sale_id);
                    } catch (error) {
                        if (!error.responseReceived || error.status === 401 || error.status === 419) break;
                        sale.sync_status = 'needs_review';
                        sale.sync_error = error.message;
                        await put(sale);
                    }
                }
            } finally {
                syncPromise = null;
                await updateIndicator();
            }
        })();
        return syncPromise;
    }

    async function refreshWhenOnline() {
        if (!navigator.onLine) return;
        if (refreshPromise) return refreshPromise;
        refreshPromise = (async () => {
            await sync();
            try {
                await pullCatalogue();
            } catch (error) {
                console.error('POS product and stock refresh failed.', error);
            }
            await updateIndicator();
        })();
        try {
            await refreshPromise;
        } finally {
            refreshPromise = null;
        }
    }

    async function reviewQueue() {
        const sales = (await all()).filter((sale) => belongsToActiveContext(sale));
        const review = sales.filter((sale) => sale.sync_status === 'needs_review');
        if (!review.length) {
            if (navigator.onLine) sync();
            return;
        }

        const details = review.map((sale, index) => {
            const amount = Number(sale.total || 0).toLocaleString(undefined, {
                style: 'currency',
                currency: config.currencyCode || 'USD',
            });
            return `${index + 1}. ${amount}: ${sale.sync_error || 'Synchronization failed'}`;
        }).join('\n');

        if (!window.confirm(`${details}\n\nRetry these sales now?`)) return;
        for (const sale of review) {
            sale.sync_status = 'pending';
            sale.sync_error = null;
            await put(sale);
        }
        await updateIndicator();
        await sync();
    }

    async function submit(payload) {
        const paymentMethod = String(payload.payment_method || '').toLowerCase();
        const canQueue = Number(payload.wallet_amount || 0) <= 0
            && !payload.source
            && !['chargetoroom', 'charge_to_room', 'charge-room'].includes(paymentMethod);
        const prepared = {
            ...payload,
            client_sale_id: payload.client_sale_id || uuid(),
            client_recorded_at: payload.client_recorded_at || new Date().toISOString(),
            offline_company_id: companyId,
            offline_branch_id: String(config.branchId || ''),
            offline_branch_name: String(config.branchName || ''),
        };

        if (!navigator.onLine) {
            if (!canQueue) {
                throw new Error('This payment type requires an internet connection.');
            }
            await validateOfflineStock(prepared);
            return { queued: true, sale: await queue(prepared) };
        }

        try {
            const result = await send(prepared);
            await applySaleToInventory({ ...prepared, sync_status: 'synced' });
            return { queued: false, result };
        } catch (error) {
            if (error.responseReceived) throw error;
            if (!canQueue) throw new Error('This payment type requires an internet connection.');
            await validateOfflineStock(prepared);
            return { queued: true, sale: await queue(prepared) };
        }
    }

    async function validateOfflineStock(sale) {
        const records = (await allInventory()).filter((record) => record.context_key === contextKey);
        const stockByProduct = new Map(records.map((record) => [String(record.product_id), Number(record.stock || 0)]));
        const demandByProduct = new Map();
        for (const item of sale.items || []) {
            const id = productId(item);
            const amount = stockUnits(item);
            if (!id || !Number.isFinite(amount) || amount <= 0) continue;
            if (!stockByProduct.has(id)) {
                throw new Error('Offline stock is not available for one or more items. Connect to the internet once to refresh this device.');
            }
            demandByProduct.set(id, (demandByProduct.get(id) || 0) + amount);
        }
        for (const [id, demand] of demandByProduct) {
            const available = stockByProduct.get(id);
            if (demand > available) {
                const option = Array.from(document.querySelectorAll('#product-select option'))
                    .find((candidate) => String(candidate.value) === id);
                const name = option?.dataset.name || option?.textContent?.trim() || 'This product';
                throw new Error(`Only ${available} ${name} available on this device. Connect to refresh stock or reduce the quantity.`);
            }
        }
    }

    window.SmartProBookOffline = { submit, sync, updateIndicator, reviewQueue };
    window.addEventListener('online', refreshWhenOnline);
    window.addEventListener('online', updateIndicator);
    window.addEventListener('offline', updateIndicator);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshWhenOnline();
    });
    document.getElementById('pos-offline-status')?.addEventListener('click', reviewQueue);
    window.setInterval(refreshWhenOnline, 60000);
    initializeInventory().then(updateIndicator).catch((error) => {
        console.error('POS offline inventory could not be initialized.', error);
        updateIndicator();
    });
})();
