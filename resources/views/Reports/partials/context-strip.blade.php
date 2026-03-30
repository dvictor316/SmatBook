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
