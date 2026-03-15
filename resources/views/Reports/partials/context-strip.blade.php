@php
    $stripCompany = auth()->user()?->company;
    $stripCompanyName = $stripCompany?->company_name
        ?? $stripCompany?->name
        ?? \App\Models\Setting::where('key', 'company_name')->value('value')
        ?? 'SmartProbook';
    $stripBranchName = $activeBranch['name'] ?? session('active_branch_name') ?? null;
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
    </div>
</div>

<style>
    .report-context-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.9rem 1rem;
        background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
        border: 1px solid #dbeafe;
        border-radius: 14px;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
    }
    .report-context-kicker {
        display: inline-block;
        margin-bottom: 0.2rem;
        color: #2563eb;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .report-context-company {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }
    .report-context-meta {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.55rem;
    }
    .report-context-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 0.8rem;
        border-radius: 999px;
        background: #2563eb;
        color: #fff;
        font-size: 0.74rem;
        font-weight: 700;
        box-shadow: 0 8px 14px rgba(37, 99, 235, 0.16);
    }
    .report-context-pill--light {
        background: #fff;
        color: #334155;
        border: 1px solid #dbeafe;
        box-shadow: none;
    }
    @media (max-width: 767.98px) {
        .report-context-strip {
            flex-direction: column;
            align-items: flex-start;
        }
        .report-context-meta {
            justify-content: flex-start;
        }
    }
</style>
