(function () {
    'use strict';

    const config = window.CUSTOMER_OFFLINE_CONFIG || {};
    const form = document.getElementById('offline-customer-form');
    const indicator = document.getElementById('customer-offline-status');
    if (!form || !config.storeUrl || !window.indexedDB) return;

    const databaseName = 'smartprobook-offline';
    const storeName = 'pending-customers';
    let syncing = false;

    function openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(databaseName, 1);
            request.onupgradeneeded = () => {
                if (!request.result.objectStoreNames.contains(storeName)) {
                    request.result.createObjectStore(storeName, { keyPath: 'client_record_id' });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async function transact(mode, callback) {
        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(storeName, mode);
            const request = callback(transaction.objectStore(storeName));
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
            transaction.oncomplete = () => db.close();
        });
    }

    const getAll = () => transact('readonly', (store) => store.getAll());
    const save = (record) => transact('readwrite', (store) => store.put(record));
    const remove = (id) => transact('readwrite', (store) => store.delete(id));

    function uuid() {
        if (crypto.randomUUID) return crypto.randomUUID();
        return 'customer-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    function payloadFromForm() {
        const payload = {};
        new FormData(form).forEach((value, key) => {
            if (!(value instanceof File)) payload[key] = value;
        });
        delete payload._token;
        return payload;
    }

    function canQueue() {
        const image = form.querySelector('input[name="image"]')?.files?.[0];
        const openingBalance = Number(form.querySelector('[name="balance"]')?.value || 0);
        const hasBankDetails = ['bank_name', 'account_number', 'account_holder', 'branch', 'ifsc']
            .some((name) => String(form.querySelector(`[name="${name}"]`)?.value || '').trim() !== '');
        return !image && openingBalance <= 0 && !hasBankDetails;
    }

    async function updateIndicator() {
        const records = await getAll();
        const review = records.filter((record) => record.sync_status === 'needs_review').length;
        const pending = records.length - review;
        indicator.className = `badge me-2 ${review ? 'bg-danger' : navigator.onLine ? 'bg-success' : 'bg-warning text-dark'}`;
        indicator.textContent = review
            ? `${review} customer${review === 1 ? '' : 's'} need review`
            : navigator.onLine ? (pending ? `Syncing ${pending}` : 'Online') : `Offline${pending ? ` - ${pending} queued` : ''}`;
    }

    async function send(record) {
        const response = await fetch(config.storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(record),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(result.message || Object.values(result.errors || {})[0]?.[0] || 'Customer synchronization failed.');
            error.status = response.status;
            error.responseReceived = true;
            throw error;
        }
        return result;
    }

    async function synchronize() {
        if (syncing || !navigator.onLine) return;
        syncing = true;
        try {
            const records = (await getAll()).filter((record) => record.sync_status !== 'needs_review');
            for (const record of records) {
                try {
                    await send(record);
                    await remove(record.client_record_id);
                } catch (error) {
                    if (!error.responseReceived || error.status === 401 || error.status === 419) break;
                    record.sync_status = 'needs_review';
                    record.sync_error = error.message;
                    await save(record);
                }
            }
        } finally {
            syncing = false;
            await updateIndicator();
        }
    }

    form.addEventListener('submit', async (event) => {
        if (!form.reportValidity()) return;
        if (!canQueue()) {
            if (!navigator.onLine) {
                event.preventDefault();
                window.alert('Customer photos, opening balances, and bank details require an internet connection.');
            }
            return;
        }

        event.preventDefault();
        const submitButton = form.querySelector('[type="submit"]');
        submitButton.disabled = true;
        const record = {
            ...payloadFromForm(),
            client_record_id: uuid(),
            client_recorded_at: new Date().toISOString(),
            sync_status: 'pending',
            sync_error: null,
        };

        try {
            if (navigator.onLine) {
                try {
                    await send(record);
                    window.location.assign(config.indexUrl);
                    return;
                } catch (error) {
                    if (error.responseReceived) throw error;
                }
            }
            await save(record);
            form.reset();
            await updateIndicator();
            window.alert('Customer saved offline and will synchronize automatically when internet returns.');
        } catch (error) {
            window.alert(error.message || 'Customer could not be saved.');
        } finally {
            submitButton.disabled = false;
        }
    });

    indicator.addEventListener('click', async () => {
        const review = (await getAll()).filter((record) => record.sync_status === 'needs_review');
        if (!review.length) return synchronize();
        const details = review.map((record, index) => `${index + 1}. ${record.customer_name}: ${record.sync_error}`).join('\n');
        if (!window.confirm(`${details}\n\nRetry these customers now?`)) return;
        for (const record of review) {
            record.sync_status = 'pending';
            record.sync_error = null;
            await save(record);
        }
        await synchronize();
    });

    window.addEventListener('online', synchronize);
    window.addEventListener('online', updateIndicator);
    window.addEventListener('offline', updateIndicator);
    updateIndicator();
    synchronize();
})();
