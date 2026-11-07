@php
    $storeWhatsapp = $store->orderContact()['whatsapp'];
@endphp
@if($storeWhatsapp)
    <aside class="store-enquiry-chat" aria-label="WhatsApp enquiries">
        <button class="store-enquiry-toggle" type="button" aria-expanded="false" aria-controls="store-enquiry-panel">
            <span class="store-enquiry-icon" aria-hidden="true">⌕</span>
            <span>Ask us</span>
        </button>
        <div class="store-enquiry-panel" id="store-enquiry-panel" hidden>
            <div class="store-enquiry-heading">
                <strong>Have a question?</strong>
                <button class="store-enquiry-close" type="button" aria-label="Close enquiry chat">×</button>
            </div>
            <p>Send us an enquiry on WhatsApp and our team will get back to you.</p>
            <textarea rows="3" maxlength="1000" placeholder="Type your enquiry here..." aria-label="Your enquiry"></textarea>
            <button class="store-enquiry-send" type="button">Continue on WhatsApp</button>
        </div>
    </aside>
    <script>
        (() => {
            const widget = document.querySelector('.store-enquiry-chat');
            const toggle = widget?.querySelector('.store-enquiry-toggle');
            const panel = widget?.querySelector('.store-enquiry-panel');
            const close = widget?.querySelector('.store-enquiry-close');
            const textarea = widget?.querySelector('textarea');
            const send = widget?.querySelector('.store-enquiry-send');
            if (!widget || !toggle || !panel || !close || !textarea || !send) return;

            const whatsappNumber = @json($storeWhatsapp);
            const storeName = @json($store->store_name);

            const setOpen = (open) => {
                panel.hidden = !open;
                toggle.setAttribute('aria-expanded', String(open));
                if (open) textarea.focus();
            };

            toggle.addEventListener('click', () => setOpen(panel.hidden));
            close.addEventListener('click', () => setOpen(false));
            send.addEventListener('click', () => {
                const enquiry = textarea.value.trim();
                if (!enquiry) {
                    textarea.focus();
                    return;
                }

                const message = `Hello ${storeName}, I have an enquiry:\n\n${enquiry}`;
                window.open(`https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`, '_blank', 'noopener,noreferrer');
            });
        })();
    </script>
@endif
