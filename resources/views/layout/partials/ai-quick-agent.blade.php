<div class="settings-icon ai-agent-launcher">
    <span id="ai-agent-trigger" aria-controls="ai-quick-agent-offcanvas" title="AI Assistant">
        <span class="ai-bot-figure" aria-hidden="true">
            <span class="ai-bot-head"><i class="fas fa-robot"></i></span>
            <span class="ai-bot-body"></span>
            <span class="ai-bot-legs"></span>
        </span>
    </span>
</div>

<div class="modal fade" id="aiAssistantIntroModal" tabindex="-1" aria-labelledby="aiAssistantIntroTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ai-human-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">AI Assistant</div>
                        <div class="small text-muted">Your helper</div>
                    </div>
                </div>
                <h5 id="aiAssistantIntroTitle" class="fw-bold mb-2">Hey I'm your personal assistant.</h5>
                <p class="mb-0 text-muted">Ask me any question about your data.</p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Maybe later</button>
                <button type="button" class="btn btn-primary" id="open-ai-chat-btn">Ask Assistant</button>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end border-0" tabindex="-1" id="ai-quick-agent-offcanvas" aria-labelledby="aiQuickAgentLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="aiQuickAgentLabel">AI Personal Assistant</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <div id="ai-agent-messages" class="p-3" style="height: calc(100vh - 210px); overflow-y: auto; background:#f8fafc;">
            <div class="small text-muted mb-2">Try: <b>total sales yesterday</b>, <b>trial balance this month</b>, <b>invoices due today</b></div>
        </div>
        <div class="border-top p-3">
            <div class="input-group">
                <input type="text" id="ai-agent-input" class="form-control" placeholder="Ask something about your data...">
                <button class="btn btn-primary" type="button" id="ai-agent-send">Send</button>
            </div>
        </div>
    </div>
</div>

<style>
    .ai-agent-launcher {
        display: block !important;
        right: 18px !important;
        bottom: 18px !important;
        z-index: 1105 !important;
    }
    .ai-agent-launcher span {
        position: relative;
        overflow: visible;
        animation: aiFloat 2.4s ease-in-out infinite;
        box-shadow: 0 14px 28px rgba(30, 27, 75, 0.35);
        background: linear-gradient(145deg, #1e1b4b, #312e81) !important;
        border: 1px solid #4338ca;
    }
    .ai-human-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #312e81, #4338ca);
        color: #fde68a;
        font-size: 20px;
        box-shadow: 0 10px 20px rgba(30, 27, 75, 0.35);
    }
    .ai-agent-launcher #ai-agent-trigger {
        width: 62px !important;
        height: 62px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .ai-bot-figure {
        position: relative;
        width: 34px;
        height: 44px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        gap: 2px;
    }
    .ai-bot-head {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: #4338ca;
        color: #fde68a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        line-height: 1;
        border: 1px solid #6366f1;
    }
    .ai-bot-body {
        width: 18px;
        height: 14px;
        border-radius: 4px;
        background: #312e81;
        display: block;
        border: 1px solid #4f46e5;
    }
    .ai-bot-legs {
        width: 16px;
        height: 11px;
        display: block;
        position: absolute;
        bottom: 0;
        background:
            linear-gradient(#facc15, #facc15) left 2px top 0 / 4px 10px no-repeat,
            linear-gradient(#facc15, #facc15) right 2px top 0 / 4px 10px no-repeat;
    }
    .ai-agent-launcher.ai-active .ai-bot-figure {
        animation: aiWalk 0.8s ease-in-out infinite;
    }
    @media (max-width: 991.98px) {
        .ai-agent-launcher {
            display: block !important; /* override theme hide on mobile */
            right: 12px !important;
            bottom: calc(12px + env(safe-area-inset-bottom, 0px)) !important;
            z-index: 1110 !important;
        }
        .ai-agent-launcher #ai-agent-trigger {
            width: 58px !important;
            height: 58px !important;
        }
    }
    .ai-msg {
        max-width: 90%;
        border-radius: 12px;
        padding: 10px 12px;
        margin-bottom: 10px;
        font-size: 13px;
        line-height: 1.45;
    }
    .ai-msg-user {
        margin-left: auto;
        background: #1d4ed8;
        color: #fff;
    }
    .ai-msg-bot {
        margin-right: auto;
        background: #fff;
        border: 1px solid #e2e8f0;
        color: #1e293b;
    }
    .ai-dots span {
        animation: aiDots 1.2s infinite;
        display: inline-block;
        opacity: .2;
    }
    .ai-dots span:nth-child(2) { animation-delay: .2s; }
    .ai-dots span:nth-child(3) { animation-delay: .4s; }
    @keyframes aiDots {
        0%, 80%, 100% { opacity: .2; transform: translateY(0); }
        40% { opacity: 1; transform: translateY(-2px); }
    }
    @keyframes aiFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }
    @keyframes aiWalk {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-1px) rotate(2deg); }
    }
</style>

<script>
    (function () {
        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function (m) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
            });
        }

        function appendMessage(type, text, isLoading) {
            const wrap = document.getElementById('ai-agent-messages');
            if (!wrap) return null;

            const div = document.createElement('div');
            div.className = 'ai-msg ' + (type === 'user' ? 'ai-msg-user' : 'ai-msg-bot');
            div.innerHTML = isLoading
                ? '<span class="ai-dots"><span>.</span><span>.</span><span>.</span></span>'
                : escapeHtml(text);
            wrap.appendChild(div);
            wrap.scrollTop = wrap.scrollHeight;
            return div;
        }

        async function runQuery() {
            const input = document.getElementById('ai-agent-input');
            const launcher = document.querySelector('.ai-agent-launcher');
            if (!input) return;

            const message = (input.value || '').trim();
            if (!message) return;

            launcher?.classList.add('ai-active');
            appendMessage('user', message);
            input.value = '';

            const loading = appendMessage('bot', '', true);

            try {
                const url = '{{ route('ai.quick-agent.query') }}' + '?message=' + encodeURIComponent(message);
                const res = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const raw = await res.text();
                let payload = null;
                try {
                    payload = JSON.parse(raw);
                } catch (_) {
                    payload = null;
                }

                if (loading) loading.remove();

                if (!res.ok) {
                    appendMessage('bot', payload?.answer || ('Unable to run that query right now. (HTTP ' + res.status + ')'));
                    return;
                }

                if (payload?.answer) {
                    appendMessage('bot', payload.answer);
                    return;
                }

                const cleaned = String(raw || '').replace(/\s+/g, ' ').trim();
                if (cleaned.toLowerCase().includes('<!doctype') || cleaned.toLowerCase().includes('<html')) {
                    appendMessage('bot', 'AI response came back as a page instead of JSON. Please refresh and try again.');
                    return;
                }

                appendMessage('bot', cleaned ? cleaned.slice(0, 220) : 'No result found.');
            } catch (e) {
                if (loading) loading.remove();
                appendMessage('bot', 'Network error while running the task.');
            } finally {
                setTimeout(() => launcher?.classList.remove('ai-active'), 900);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const sendBtn = document.getElementById('ai-agent-send');
            const input = document.getElementById('ai-agent-input');
            const offcanvasEl = document.getElementById('ai-quick-agent-offcanvas');
            const introModalEl = document.getElementById('aiAssistantIntroModal');
            const trigger = document.getElementById('ai-agent-trigger');
            const openAiChatBtn = document.getElementById('open-ai-chat-btn');
            if (sendBtn) sendBtn.addEventListener('click', runQuery);
            if (input) {
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        runQuery();
                    }
                });
            }
            if (offcanvasEl) {
                offcanvasEl.addEventListener('show.bs.offcanvas', function () {
                    document.querySelector('.ai-agent-launcher')?.classList.add('ai-active');
                });
                offcanvasEl.addEventListener('hidden.bs.offcanvas', function () {
                    document.querySelector('.ai-agent-launcher')?.classList.remove('ai-active');
                });
            }
            if (trigger) {
                trigger.addEventListener('click', function () {
                    const introModal = introModalEl ? bootstrap.Modal.getOrCreateInstance(introModalEl) : null;
                    introModal?.show();
                });
                trigger.addEventListener('mouseenter', function () {
                    document.querySelector('.ai-agent-launcher')?.classList.add('ai-active');
                });
                trigger.addEventListener('mouseleave', function () {
                    document.querySelector('.ai-agent-launcher')?.classList.remove('ai-active');
                });
            }
            if (openAiChatBtn) {
                openAiChatBtn.addEventListener('click', function () {
                    const introModal = introModalEl ? bootstrap.Modal.getOrCreateInstance(introModalEl) : null;
                    introModal?.hide();
                    const offcanvas = offcanvasEl ? bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl) : null;
                    offcanvas?.show();
                    setTimeout(() => input?.focus(), 250);
                });
            }
        });
    })();
</script>
