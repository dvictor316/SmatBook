(function (root) {
    'use strict';

    const DATABASE_NAME = 'smartprobook-pos';
    const DATABASE_VERSION = 3;
    const CREDENTIAL_STORE = 'offline-credentials';
    const ACTIVE_CREDENTIAL_KEY = 'active';
    const PENDING_LOGIN_KEY = 'smartprobook-offline-login-pending';
    const PBKDF2_ITERATIONS = 310000;
    const encoder = new TextEncoder();
    let noticeTimeout = null;

    function toHex(bytes) {
        return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
    }

    function fromHex(value) {
        if (typeof value !== 'string' || !/^(?:[0-9a-f]{2})+$/i.test(value)) {
            throw new Error('Invalid offline credential data.');
        }
        return Uint8Array.from(value.match(/.{2}/g), (byte) => parseInt(byte, 16));
    }

    async function deriveVerifier(password, saltHex, iterations, cryptoApi) {
        if (!cryptoApi?.subtle || typeof password !== 'string' || !password) {
            throw new Error('Secure offline sign-in is not supported by this browser.');
        }

        const salt = fromHex(saltHex);
        const key = await cryptoApi.subtle.importKey(
            'raw',
            encoder.encode(password),
            'PBKDF2',
            false,
            ['deriveBits']
        );
        const bits = await cryptoApi.subtle.deriveBits({
            name: 'PBKDF2',
            salt,
            iterations,
            hash: 'SHA-256',
        }, key, 256);

        return toHex(new Uint8Array(bits));
    }

    function verifierMatches(candidate, expected) {
        if (typeof candidate !== 'string' || typeof expected !== 'string' || candidate.length !== expected.length) {
            return false;
        }

        let difference = 0;
        for (let index = 0; index < candidate.length; index += 1) {
            difference |= candidate.charCodeAt(index) ^ expected.charCodeAt(index);
        }
        return difference === 0;
    }

    function normalizeLoginIdentifier(value) {
        const identifier = String(value || '').trim();
        if (identifier.includes('@')) return identifier.toLowerCase();
        const digits = identifier.replace(/\D+/g, '');
        if (!digits) return identifier.toLowerCase();
        return `${identifier.startsWith('+') ? '+' : ''}${digits}`;
    }

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { deriveVerifier, verifierMatches, normalizeLoginIdentifier };
    }

    if (!root?.document) return;

    const document = root.document;

    function setLoginNotice(message, isError = false) {
        const notice = document.getElementById('offline-login-setup-status');
        if (!notice) return;
        notice.textContent = message;
        notice.classList.toggle('text-danger', isError);
        notice.classList.toggle('text-success', !isError);
    }

    function clearPendingLogin() {
        try {
            root.sessionStorage.removeItem(PENDING_LOGIN_KEY);
        } catch (error) {
            console.error('Could not clear pending offline sign-in data.', error);
        }
    }

    function cancelLoginPreparation() {
        clearPendingLogin();
    }

    async function prepareLogin(form) {
        if (!root.navigator.onLine) {
            setLoginNotice('Connect to the internet to sign in and prepare this device for offline POS use.', true);
            return false;
        }

        const password = form.querySelector('input[name="password"]')?.value || '';
        const identifier = form.querySelector('input[name="login"]')?.value || '';
        if (!password) return false;

        try {
            const salt = root.crypto.getRandomValues(new Uint8Array(16));
            const saltHex = toHex(salt);
            const verifier = await deriveVerifier(password, saltHex, PBKDF2_ITERATIONS, root.crypto);
            root.sessionStorage.setItem(PENDING_LOGIN_KEY, JSON.stringify({
                salt: saltHex,
                verifier,
                iterations: PBKDF2_ITERATIONS,
                identifier: normalizeLoginIdentifier(identifier),
                prepared_at: Date.now(),
            }));
            setLoginNotice('Secure offline sign-in prepared. Complete login and open the POS online once to finish setup.');
            return true;
        } catch (error) {
            clearPendingLogin();
            console.error('Could not prepare offline sign-in.', error);
            setLoginNotice('Offline sign-in could not be prepared on this device. You can still sign in online.', true);
            return false;
        }
    }

    function openDatabase() {
        return new Promise((resolve, reject) => {
            if (!root.indexedDB) {
                reject(new Error('Offline storage is not available in this browser.'));
                return;
            }

            const request = root.indexedDB.open(DATABASE_NAME, DATABASE_VERSION);
            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains('pending-sales')) {
                    db.createObjectStore('pending-sales', { keyPath: 'client_sale_id' });
                }
                if (!db.objectStoreNames.contains('inventory-snapshots')) {
                    db.createObjectStore('inventory-snapshots', { keyPath: 'key' });
                }
                if (!db.objectStoreNames.contains(CREDENTIAL_STORE)) {
                    db.createObjectStore(CREDENTIAL_STORE, { keyPath: 'key' });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error || new Error('Offline storage could not be opened.'));
            request.onblocked = () => reject(new Error('Close other SmartProbook windows to finish offline storage setup.'));
        });
    }

    async function transactCredential(mode, action) {
        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(CREDENTIAL_STORE, mode);
            const request = action(transaction.objectStore(CREDENTIAL_STORE));
            let result;
            request.onsuccess = () => {
                result = request.result;
            };
            request.onerror = () => reject(request.error || new Error('Offline sign-in data could not be accessed.'));
            transaction.oncomplete = () => {
                db.close();
                resolve(result);
            };
            transaction.onerror = () => {
                db.close();
                reject(transaction.error || new Error('Offline sign-in data could not be saved.'));
            };
            transaction.onabort = () => {
                db.close();
                reject(transaction.error || new Error('Offline sign-in data could not be saved.'));
            };
        });
    }

    const getCredential = () => transactCredential('readonly', (store) => store.get(ACTIVE_CREDENTIAL_KEY));
    const saveCredential = (credential) => transactCredential('readwrite', (store) => store.put(credential));
    const deleteCredential = () => transactCredential('readwrite', (store) => store.delete(ACTIVE_CREDENTIAL_KEY));

    function getPendingLogin() {
        try {
            const raw = root.sessionStorage.getItem(PENDING_LOGIN_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (error) {
            console.error('Could not read pending offline sign-in data.', error);
            return null;
        }
    }

    function setNotice(message, type = 'info', dismissAfter = 6000) {
        const notice = document.getElementById('pos-offline-auth-notice');
        const messageElement = notice?.querySelector('[data-notice-message]');
        if (!notice || !messageElement) return;
        if (notice.parentElement !== document.body) {
            document.body.appendChild(notice);
        }
        if (noticeTimeout !== null) {
            root.clearTimeout(noticeTimeout);
            noticeTimeout = null;
        }
        messageElement.textContent = message;
        notice.dataset.type = type;
        notice.hidden = false;
        if (dismissAfter > 0) {
            noticeTimeout = root.setTimeout(() => {
                notice.hidden = true;
                noticeTimeout = null;
            }, dismissAfter);
        }
    }

    function dismissNotice() {
        const notice = document.getElementById('pos-offline-auth-notice');
        if (notice) notice.hidden = true;
        if (noticeTimeout !== null) {
            root.clearTimeout(noticeTimeout);
            noticeTimeout = null;
        }
    }

    function currentContext(config) {
        return {
            userId: String(config.userId || ''),
            email: String(config.userEmail || '').trim().toLowerCase(),
            phone: String(config.userPhone || '').trim(),
            companyId: String(config.companyId || ''),
            branchId: String(config.branchId || config.branchName || ''),
        };
    }

    async function saveOnlineCredential(config) {
        const context = currentContext(config);
        if (!context.userId || !context.companyId || !context.email) {
            throw new Error('The signed-in POS account or business context is missing.');
        }

        const pending = getPendingLogin();
        const pendingIdentifier = normalizeLoginIdentifier(pending?.identifier);
        const identityMatches = [context.email, context.phone]
            .map(normalizeLoginIdentifier)
            .includes(pendingIdentifier);
        if (pending && identityMatches && Number.isInteger(pending.iterations) && pending.iterations >= 100000
            && /^[0-9a-f]{32}$/i.test(pending.salt) && /^[0-9a-f]{64}$/i.test(pending.verifier)) {
            await saveCredential({
                key: ACTIVE_CREDENTIAL_KEY,
                userId: context.userId,
                email: context.email,
                companyId: context.companyId,
                branchId: context.branchId,
                salt: pending.salt,
                verifier: pending.verifier,
                iterations: pending.iterations,
                failed_attempts: 0,
                locked_until: 0,
                last_online_at: Date.now(),
            });
            clearPendingLogin();
            setNotice('Offline sign-in is ready for this account and POS workspace.', 'success', 6000);
            return;
        }

        clearPendingLogin();
        const saved = await getCredential();
        if (saved && saved.userId === context.userId) {
            await saveCredential({
                ...saved,
                email: context.email,
                companyId: context.companyId,
                branchId: context.branchId,
                last_online_at: Date.now(),
            });
            return;
        }

        if (saved) await deleteCredential();
        setNotice('Sign in online with your password to enable offline POS access on this device.', 'info', 6000);
    }

    function setBackgroundLocked(gate, locked) {
        if (gate.parentElement !== document.body) {
            document.body.appendChild(gate);
        }

        if (locked) {
            gate.hidden = false;
            gate.setAttribute('aria-hidden', 'false');
            Array.from(document.body.children).forEach((element) => {
                if (element !== gate && !element.hasAttribute('data-offline-inert-saved')) {
                    element.dataset.offlineInertSaved = element.inert ? '1' : '0';
                    element.inert = true;
                }
            });
            return;
        }

        gate.hidden = true;
        gate.setAttribute('aria-hidden', 'true');
        Array.from(document.body.children).forEach((element) => {
            if (!element.hasAttribute('data-offline-inert-saved')) return;
            element.inert = element.dataset.offlineInertSaved === '1';
            delete element.dataset.offlineInertSaved;
        });
    }

    function showOfflineLock(config, credential, message) {
        const gate = document.getElementById('pos-offline-lock');
        const messageElement = document.getElementById('pos-offline-lock-message');
        const emailInput = document.getElementById('pos-offline-lock-email');
        const passwordInput = document.getElementById('pos-offline-lock-password');
        const submitButton = document.getElementById('pos-offline-lock-submit');
        const form = document.getElementById('pos-offline-lock-form');
        if (!gate || !messageElement || !emailInput || !passwordInput || !submitButton || !form) {
            throw new Error('Offline sign-in screen is missing from the POS page.');
        }

        const context = currentContext(config);
        const matchesContext = credential
            && credential.userId === context.userId
            && credential.companyId === context.companyId
            && credential.branchId === context.branchId;

        setBackgroundLocked(gate, true);
        messageElement.textContent = message || (matchesContext
            ? 'Enter the password for this saved account to unlock the offline POS.'
            : 'This device is not prepared for offline POS access. Connect, sign in, and open the POS online once.');
        emailInput.value = matchesContext ? credential.email : context.email;
        passwordInput.disabled = !matchesContext;
        submitButton.disabled = !matchesContext;

        if (!matchesContext) {
            passwordInput.value = '';
            return;
        }

        form.onsubmit = async (event) => {
            event.preventDefault();
            if (submitButton.disabled) return;

            const lockedUntil = Number(credential.locked_until || 0);
            if (lockedUntil > Date.now()) {
                const seconds = Math.ceil((lockedUntil - Date.now()) / 1000);
                messageElement.textContent = `Too many attempts. Try again in ${seconds} seconds.`;
                return;
            }

            submitButton.disabled = true;
            messageElement.textContent = 'Checking saved sign-in…';
            try {
                const candidate = await deriveVerifier(
                    passwordInput.value,
                    credential.salt,
                    credential.iterations,
                    root.crypto
                );
                if (!verifierMatches(candidate, credential.verifier)) {
                    const failedAttempts = Number(credential.failed_attempts || 0) + 1;
                    credential = {
                        ...credential,
                        failed_attempts: failedAttempts >= 5 ? 0 : failedAttempts,
                        locked_until: failedAttempts >= 5 ? Date.now() + 60000 : 0,
                    };
                    await saveCredential(credential);
                    messageElement.textContent = failedAttempts >= 5
                        ? 'Too many incorrect passwords. Wait one minute before trying again.'
                        : 'That password did not match this saved account. Please try again.';
                    return;
                }

                credential.failed_attempts = 0;
                credential.locked_until = 0;
                await saveCredential(credential);
                passwordInput.value = '';
                setBackgroundLocked(gate, false);
                setNotice('Offline POS unlocked. Saved sales will sync when internet returns.', 'success', 6000);
            } catch (error) {
                console.error('Offline sign-in verification failed.', error);
                messageElement.textContent = 'Offline sign-in could not be verified. Reconnect and sign in online.';
            } finally {
                const wait = Math.max(0, Number(credential.locked_until || 0) - Date.now());
                if (wait > 0) {
                    root.setTimeout(() => {
                        submitButton.disabled = false;
                        messageElement.textContent = 'Enter the password for this saved account to unlock the offline POS.';
                    }, wait);
                } else {
                    submitButton.disabled = false;
                    passwordInput.focus();
                }
            }
        };

        if (Number(credential.locked_until || 0) > Date.now()) {
            submitButton.disabled = true;
            const wait = Number(credential.locked_until) - Date.now();
            messageElement.textContent = `Too many attempts. Try again in ${Math.ceil(wait / 1000)} seconds.`;
            root.setTimeout(() => {
                submitButton.disabled = false;
                messageElement.textContent = 'Enter the password for this saved account to unlock the offline POS.';
                passwordInput.focus();
            }, wait);
        } else {
            passwordInput.focus();
        }
    }

    async function initializePos(config) {
        const forcedOffline = new URLSearchParams(root.location.search).has('desktop_offline');
        if (root.navigator.onLine && !forcedOffline) {
            const gate = document.getElementById('pos-offline-lock');
            if (gate) setBackgroundLocked(gate, false);
            try {
                await saveOnlineCredential(config);
            } catch (error) {
                console.error('Could not prepare this POS for offline sign-in.', error);
                setNotice('Offline sign-in could not be saved. The POS is online, but this device may need internet next time.', 'error');
            }
            return;
        }

        const gate = document.getElementById('pos-offline-lock');
        if (!gate) throw new Error('Offline sign-in screen is missing from the POS page.');
        setBackgroundLocked(gate, true);
        const message = document.getElementById('pos-offline-lock-message');
        message.textContent = 'Checking this device’s saved offline sign-in…';

        try {
            const credential = await getCredential();
            showOfflineLock(config, credential);
        } catch (error) {
            console.error('Could not load saved offline sign-in.', error);
            showOfflineLock(config, null, 'Offline sign-in data could not be opened. Reconnect and sign in online.');
        }
    }

    function start() {
        document.querySelector('#pos-offline-auth-notice [data-notice-dismiss]')
            ?.addEventListener('click', dismissNotice);
        const loginForm = document.querySelector('form[action$="/saas-login"]');
        if (loginForm?.dataset.loginFailed === '1') {
            clearPendingLogin();
        }

        if (root.POS_OFFLINE_CONFIG) {
            initializePos(root.POS_OFFLINE_CONFIG).catch((error) => {
                console.error('Offline POS sign-in could not be initialized.', error);
                const gate = document.getElementById('pos-offline-lock');
                if (gate) {
                    setBackgroundLocked(gate, true);
                    const message = document.getElementById('pos-offline-lock-message');
                    if (message) message.textContent = 'Offline sign-in could not start. Reconnect and sign in online.';
                }
            });
        }
    }

    if (root.navigator.serviceWorker) {
        root.navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data?.type === 'offline-auth-cleared') clearPendingLogin();
        });
    }

    root.SmartProbookOfflineAuth = { prepareLogin, cancelLoginPreparation };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})(typeof window === 'undefined' ? null : window);
