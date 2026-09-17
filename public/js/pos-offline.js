(function () {
    'use strict';

    const config = window.POS_OFFLINE_CONFIG || {};
    if (!config.saleUrl || !window.indexedDB) return;

    const databaseName = 'smartprobook-pos';
    const storeName = 'pending-sales';
    let syncing = false;

    function openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(databaseName, 1);
            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains(storeName)) {
                    db.createObjectStore(storeName, { keyPath: 'client_sale_id' });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async function transact(mode, action) {
        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(storeName, mode);
            const store = transaction.objectStore(storeName);
            const request = action(store);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
            transaction.oncomplete = () => db.close();
        });
    }

    const all = () => transact('readonly', (store) => store.getAll());
    const put = (sale) => transact('readwrite', (store) => store.put(sale));
    const remove = (id) => transact('readwrite', (store) => store.delete(id));

    function uuid() {
        if (crypto.randomUUID) return crypto.randomUUID();
        return 'pos-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    async function updateIndicator() {
        const indicator = document.getElementById('pos-offline-status');
        if (!indicator) return;
        const queued = (await all()).filter((sale) => sale.sync_status !== 'needs_review').length;
        const review = (await all()).filter((sale) => sale.sync_status === 'needs_review').length;
        indicator.classList.toggle('is-offline', !navigator.onLine);
        indicator.classList.toggle('has-review', review > 0);
        indicator.textContent = !navigator.onLine
            ? `Offline${queued ? ` - ${queued} queued` : ''}`
            : review ? `${review} sale${review === 1 ? '' : 's'} need review`
                : queued ? `Syncing ${queued} sale${queued === 1 ? '' : 's'}` : 'Online';
        indicator.title = review
            ? 'A queued sale could not synchronize, usually because stock or account details changed.'
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
            sync_status: 'pending',
            sync_error: null,
        };
        await put(queued);
        await updateIndicator();
        return queued;
    }

    async function sync() {
        if (syncing || !navigator.onLine) return;
        syncing = true;
        try {
            const pending = (await all()).filter((sale) => sale.sync_status !== 'needs_review');
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
            syncing = false;
            await updateIndicator();
        }
    }

    async function reviewQueue() {
        const sales = await all();
        const review = sales.filter((sale) => sale.sync_status === 'needs_review');
        if (!review.length) {
            if (navigator.onLine) sync();
            return;
        }

        const details = review.map((sale, index) => {
            const amount = Number(sale.total || 0).toLocaleString('en-NG', {
                style: 'currency',
                currency: 'NGN',
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
        };

        if (!navigator.onLine) {
            if (!canQueue) {
                throw new Error('This payment type requires an internet connection.');
            }
            return { queued: true, sale: await queue(prepared) };
        }

        try {
            return { queued: false, result: await send(prepared) };
        } catch (error) {
            if (error.responseReceived) throw error;
            if (!canQueue) throw new Error('This payment type requires an internet connection.');
            return { queued: true, sale: await queue(prepared) };
        }
    }

    window.SmartProBookOffline = { submit, sync, updateIndicator, reviewQueue };
    window.addEventListener('online', sync);
    window.addEventListener('online', updateIndicator);
    window.addEventListener('offline', updateIndicator);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) sync();
    });
    document.getElementById('pos-offline-status')?.addEventListener('click', reviewQueue);
    updateIndicator();
    sync();
})();
