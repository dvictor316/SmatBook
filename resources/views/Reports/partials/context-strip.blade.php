@php
    $stripCompany = auth()->user()?->company;
    $stripCompanyName = $stripCompany?->company_name
        ?? $stripCompany?->name
        ?? \App\Models\Setting::where('key', 'company_name')->value('value')
        ?? 'SmartProbook';
    $activeBranch = $activeBranch ?? [];
    $stripBranchName = data_get($activeBranch, 'name') ?? session('active_branch_name') ?? null;
    $stripReportLabel = $reportLabel ?? 'Business Report';
    $stripPeriodLabel = $periodLabel ?? null;
@endphp

<div class="report-context-strip mb-3">
    <div class="report-context-main">
        <span class="report-context-kicker">{{ $stripReportLabel }}</span>
        <h6 class="report-context-company mb-0">{{ $stripCompanyName }}</h6>
    </div>
    <div class="report-context-meta">
        @if($stripBranchName)
            <span class="report-context-pill">
                <i class="fas fa-code-branch me-2"></i>Branch: {{ $stripBranchName }}
            </span>
        @endif
        @if($stripPeriodLabel)
            <span class="report-context-pill report-context-pill--light">{{ $stripPeriodLabel }}</span>
        @endif
        <button type="button"
                class="report-context-pill report-context-pill--email d-print-none"
                data-bs-toggle="modal"
                data-bs-target="#emailReportModal"
                data-report-label="{{ $stripReportLabel }}"
                data-report-url="{{ url()->full() }}">
            <i class="fas fa-envelope me-1"></i> Email Report
        </button>
    </div>
</div>

@once
<style>
    .report-context-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.1rem;
        background: linear-gradient(135deg, #f3f8ff 0%, #ffffff 60%, #fffaf0 100%);
        border: 1px solid #dbe6f4;
        border-radius: 20px;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
    }
    .report-context-kicker {
        display: inline-block;
        margin-bottom: 0.28rem;
        color: #2563eb;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }
    .report-context-company {
        color: #102a5a;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    .report-context-meta {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.6rem;
    }
    .report-context-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.58rem 0.9rem;
        border-radius: 999px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
        font-size: 0.74rem;
        font-weight: 700;
        box-shadow: 0 10px 18px rgba(37, 99, 235, 0.15);
    }
    .report-context-pill--light {
        background: #fffdf8;
        color: #7a5a1d;
        border: 1px solid #f1dfb4;
        box-shadow: none;
    }
    .report-context-pill--email {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        border: none;
        cursor: pointer;
        box-shadow: 0 10px 18px rgba(5, 150, 105, 0.18);
    }
    .report-context-pill--email:hover {
        background: linear-gradient(135deg, #047857 0%, #065f46 100%);
    }
    @media (max-width: 767.98px) {
        .report-context-strip {
            flex-direction: column;
            align-items: flex-start;
            border-radius: 18px;
        }
        .report-context-meta {
            justify-content: flex-start;
        }
    }
</style>
@endonce

@once
{{-- ============ EMAIL REPORT MODAL (rendered once per page) ============ --}}
<div class="modal fade" id="emailReportModal" tabindex="-1" aria-labelledby="emailReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background:linear-gradient(135deg,#059669,#047857);color:#fff;">
                <h5 class="modal-title" id="emailReportModalLabel"><i class="fas fa-envelope me-2"></i>Email Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="emailReportAlert" class="alert d-none py-2 small mb-3"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Recipient Email</label>
                    <input type="email" class="form-control" id="emailReportRecipient"
                        placeholder="{{ auth()->user()?->email ?? 'recipient@example.com' }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Subject</label>
                    <input type="text" class="form-control" id="emailReportSubject" value="">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea class="form-control" id="emailReportBody" rows="3" placeholder="Add a note to the recipient…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4" id="emailReportSendBtn">
                    <i class="fas fa-paper-plane me-1"></i> Send Report
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function initEmailReportModal() {
        const modal = document.getElementById('emailReportModal');
        if (!modal) return;

        // Pre-fill subject when modal opens
        modal.addEventListener('show.bs.modal', function (e) {
            const triggerBtn = e.relatedTarget;
            if (!triggerBtn) return;
            const label = triggerBtn.getAttribute('data-report-label') || 'Business Report';
            const subjectInput = document.getElementById('emailReportSubject');
            if (subjectInput) subjectInput.value = label + ' — ' + new Date().toLocaleDateString();
        });

        document.getElementById('emailReportSendBtn')?.addEventListener('click', function () {
            const alertDiv = document.getElementById('emailReportAlert');
            const recipientInput = document.getElementById('emailReportRecipient');
            const recipient = recipientInput ? recipientInput.value.trim() : '';
            const subject = (document.getElementById('emailReportSubject')?.value || '').trim();
            const body = (document.getElementById('emailReportBody')?.value || '').trim();
            const btn = this;

            if (!recipient) {
                alertDiv.className = 'alert alert-danger py-2 small mb-3';
                alertDiv.textContent = 'Please enter a recipient email address.';
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending…';
            alertDiv.className = 'alert d-none py-2 small mb-3';

            fetch('{{ route("reports.email-report") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ subject, body, recipient }),
            })
            .then(r => r.json())
            .then(data => {
                alertDiv.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger') + ' py-2 small mb-3';
                alertDiv.textContent = data.message;
                if (data.success) {
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(modal)?.hide();
                        alertDiv.className = 'alert d-none py-2 small mb-3';
                    }, 2000);
                }
            })
            .catch(() => {
                alertDiv.className = 'alert alert-danger py-2 small mb-3';
                alertDiv.textContent = 'An unexpected error occurred. Please try again.';
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send Report';
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmailReportModal);
    } else {
        initEmailReportModal();
    }
})();
</script>
@endonce
