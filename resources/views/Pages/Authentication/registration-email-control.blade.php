<div class="mb-3 registration-email-control" data-purpose="{{ $purpose }}">
    <button type="button" class="btn btn-outline-primary registration-email-send">Send verification code</button>
    <p class="registration-email-status mt-2" role="status">The business owner must confirm their email before registration can be completed.</p>
    <noscript>Email verification requires JavaScript. Please enable it to register.</noscript>
</div>
<script>
(() => {
    const control = document.currentScript.previousElementSibling;
    const form = control.closest('form');
    const email = form.querySelector('[name="email"]');
    const button = control.querySelector('.registration-email-send');
    const status = control.querySelector('.registration-email-status');
    const csrf = form.querySelector('[name="_token"]').value;
    let timer;
    const post = async (url) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify({email: email.value.trim(), purpose: control.dataset.purpose})
        });
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Please try again.');
        return data;
    };
    button.addEventListener('click', async () => {
        if (!email.reportValidity()) return;
        clearInterval(timer);
        button.disabled = true;
        try {
            const result = await post(@json(route('registration-verification.send')));
            status.textContent = result.message;
            const requestedEmail = email.value.trim().toLowerCase();
            const expiresAt = Date.now() + 5 * 60 * 1000;
            timer = setInterval(async () => {
                if (Date.now() >= expiresAt) {
                    clearInterval(timer);
                    status.textContent = 'The code has expired. Send a new verification code.';
                    return;
                }
                if (email.value.trim().toLowerCase() !== requestedEmail) {
                    clearInterval(timer);
                    status.textContent = 'Email changed. Send a new verification code.';
                    return;
                }
                try {
                    const result = await post(@json(route('registration-verification.status')));
                    if (result.approved) {
                        clearInterval(timer);
                        status.textContent = 'Email confirmed. You can now complete registration.';
                    }
                } catch (error) {
                    clearInterval(timer);
                    status.textContent = error.message;
                }
            }, 4000);
        } catch (error) {
            status.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    });
})();
</script>
