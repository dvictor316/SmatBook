@php
    $aiUser = auth()->user();
    $aiRole = strtolower((string) ($aiUser->role ?? ''));
    $aiIsAdmin = in_array($aiRole, ['super_admin', 'superadmin', 'administrator', 'admin'], true)
        || ($aiUser && method_exists($aiUser, 'hasRole') && ($aiUser->hasRole('super_admin') || $aiUser->hasRole('administrator') || $aiUser->hasRole('admin')));
    $aiCan = function (array|string $permissions) use ($aiUser, $aiIsAdmin): bool {
        if ($aiIsAdmin) {
            return true;
        }

        if (!$aiUser || !method_exists($aiUser, 'hasPermissionTo')) {
            return false;
        }

        foreach ((array) $permissions as $permission) {
            if ($aiUser->hasPermissionTo((string) $permission)) {
                return true;
            }
        }

        return false;
    };
    $aiPermissions = [
        'sales' => $aiCan(['sales.invoices.view', 'sales.invoices.view_all', 'sales.invoices.view_own', 'sales.sales.view_all', 'sales.sales.view_own', 'sales.invoices.create']),
        'customers' => $aiCan(['customers.customers.view', 'customers.customers.view_all', 'customers.customers.view_own']),
        'payments' => $aiCan(['finance.payments.view']),
        'products' => $aiCan(['inventory.products.view', 'inventory.stock.view']),
        'reports' => $aiCan(['reports.reports.view']),
    ];
@endphp

<div class="settings-icon ai-agent-launcher">
    <span id="ai-agent-trigger" aria-controls="ai-quick-agent-offcanvas" title="AI Assistant">
        <span class="ai-badge-text" aria-hidden="true">AI</span>
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
                        <div class="small text-muted">Professional business assistant</div>
                    </div>
                </div>
                <h5 id="aiAssistantIntroTitle" class="fw-bold mb-2">I am your SmartProbook AI assistant.</h5>
                <p class="mb-0 text-muted">Ask detailed questions about your reports, accounting figures, workflows, and business performance.</p>
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
            <div class="small text-muted mb-2">Try: <b>total sales yesterday</b>, <b>explain my trial balance this month</b>, <b>review lead management for my workspace</b>, <b>run anomaly detection for this month</b></div>
            <div class="d-flex flex-wrap gap-2 mb-3" id="ai-agent-starters">
                @if($aiPermissions['sales'])
                    <button class="btn btn-sm btn-light border ai-starter-chip" type="button" data-prompt="total sales today">Sales Today</button>
                    <button class="btn btn-sm btn-light border ai-starter-chip" type="button" data-prompt="invoices due today">Invoices Due</button>
                @endif
                @if($aiPermissions['reports'])
                    <button class="btn btn-sm btn-light border ai-starter-chip" type="button" data-prompt="review lead management for my workspace">Lead Review</button>
                    <button class="btn btn-sm btn-light border ai-starter-chip" type="button" data-prompt="run project management ai for my workspace">Project Risks</button>
                @endif
            </div>
        </div>
        <div class="border-top p-3">
            <div class="input-group">
                <input type="text" id="ai-agent-input" class="form-control" placeholder="Ask a detailed question about reports, sales, invoices, accounting, operations, or business workflows...">
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
        width: 52px !important;
        height: 52px !important;
        border-radius: 50% !important;
        background: #020b24 !important;
        background-image: none !important;
        border: 1px solid #d4af37 !important;
        box-shadow: 0 10px 22px rgba(1, 8, 24, 0.34) !important;
    }
    .ai-agent-launcher #ai-agent-trigger {
        position: relative;
        overflow: hidden;
        animation: aiFloat 2.4s ease-in-out infinite;
        box-shadow: none !important;
        background: transparent !important;
        background-image: none !important;
        border: 0 !important;
        width: 100% !important;
        height: 100% !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer;
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
    .ai-badge-text {
        background: transparent !important;
        background-image: none !important;
        color: #ffffff !important;
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        line-height: 1;
        text-shadow: none;
        transform: translateX(1px);
    }
    .ai-agent-launcher.ai-active #ai-agent-trigger {
        box-shadow: none !important;
    }
    .ai-agent-launcher.ai-active {
        box-shadow: 0 12px 26px rgba(3, 18, 55, 0.38), 0 0 0 4px rgba(214, 169, 0, 0.14) !important;
    }
    @media (max-width: 991.98px) {
        .ai-agent-launcher {
            display: block !important; /* override theme hide on mobile */
            right: 12px !important;
            bottom: calc(12px + env(safe-area-inset-bottom, 0px)) !important;
            z-index: 1110 !important;
        }
        .ai-agent-launcher #ai-agent-trigger {
            width: 100% !important;
            height: 100% !important;
        }
    }
    .ai-msg {
        max-width: 90%;
        border-radius: 12px;
        padding: 10px 12px;
        margin-bottom: 10px;
        font-size: 13px;
        line-height: 1.45;
        white-space: pre-wrap;
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
    .ai-starter-chip {
        border-radius: 999px;
        color: #312e81;
        background: #fff;
        font-weight: 600;
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
</style>

<script>
    window.SPB_AI_PERMISSIONS = @json($aiPermissions);

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
            document.addEventListener('click', function (event) {
                const chip = event.target.closest('.ai-starter-chip');
                if (!chip || !input) return;
                input.value = chip.getAttribute('data-prompt') || '';
                input.focus();
                runQuery();
            });
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
            window.openSmartProbookAiAssistant = function () {
                const introModal = introModalEl ? bootstrap.Modal.getOrCreateInstance(introModalEl) : null;
                introModal?.hide();
                const offcanvas = offcanvasEl ? bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl) : null;
                offcanvas?.show();
                setTimeout(() => input?.focus(), 250);
            };
        });
    })();
</script>
