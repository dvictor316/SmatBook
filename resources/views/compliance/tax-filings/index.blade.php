@extends('layout.mainlayout')

@section('page-title', 'Tax Filings')

@section('content')
<style>
    :root {
        --sidebar-w: 270px;
        --sidebar-collapsed: 80px;
        --compliance-page-bg: #f8fafc;
        --compliance-card-bg: #ffffff;
        --compliance-card-border: #dbe4ee;
        --compliance-text: #0f172a;
        --compliance-muted: #475569;
        --compliance-input-bg: #ffffff;
        --compliance-input-border: #cbd5e1;
    }
    #tax-filings-wrapper {
        margin-left: var(--sidebar-w);
        width: calc(100% - var(--sidebar-w));
        padding: 100px 1.5rem 2rem;
        min-height: 100vh;
        background: var(--compliance-page-bg);
        color: var(--compliance-text);
        transition: margin-left .3s, width .3s;
    }
    body.sidebar-icon-only #tax-filings-wrapper,
    body.mini-sidebar #tax-filings-wrapper {
        margin-left: var(--sidebar-collapsed);
        width: calc(100% - var(--sidebar-collapsed));
    }
    @media (max-width: 991.98px) {
        #tax-filings-wrapper { margin-left: 0; width: 100%; }
    }
    #tax-filings-wrapper .text-muted { color: var(--compliance-muted) !important; }
    #tax-filings-wrapper .card,
    #tax-filings-wrapper .table,
    #tax-filings-wrapper .table th,
    #tax-filings-wrapper .table td,
    #tax-filings-wrapper .card-header,
    #tax-filings-wrapper .card-body,
    #tax-filings-wrapper .card-footer,
    #tax-filings-wrapper h4,
    #tax-filings-wrapper p {
        color: var(--compliance-text);
    }
    #tax-filings-wrapper .card {
        background: var(--compliance-card-bg);
        border: 1px solid var(--compliance-card-border);
    }
    #tax-filings-wrapper .bg-white {
        background: #ffffff !important;
        color: var(--compliance-text) !important;
    }
</style>

<div id="tax-filings-wrapper">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Tax Filings</h4>
            <p class="text-muted mb-0 small">Track draft and submitted tax filings.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('compliance.tax-center.index') }}" class="btn btn-outline-secondary btn-sm">Tax Center</a>
            <a href="{{ route('compliance.tax-filings.create') }}" class="btn btn-primary btn-sm text-white">Create Filing</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(!empty($taxSetupMissing))
        <div class="alert alert-warning">
            Taxation module tables are not available yet. Run <code>php artisan migrate</code> and reload this page.
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Jurisdiction</th>
                        <th>Period</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Total Taxable</th>
                        <th>Total Tax</th>
                        <th>Reference</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($filings as $filing)
                        <tr>
                            <td>{{ $filing->name }}</td>
                            <td>{{ $filing->jurisdiction?->name }}</td>
                            <td>{{ $filing->period_start?->format('Y-m-d') }} to {{ $filing->period_end?->format('Y-m-d') }}</td>
                            <td>{{ $filing->due_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $filing->status === 'submitted' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($filing->status) }}
                                </span>
                            </td>
                            <td>{{ number_format((float)$filing->total_taxable, 2) }}</td>
                            <td>{{ number_format((float)$filing->total_tax, 2) }}</td>
                            <td>{{ $filing->reference_no ?? '-' }}</td>
                            <td class="text-end">
                                @if($filing->status !== 'submitted')
                                    <a href="{{ route('compliance.tax-filings.edit', $filing->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('compliance.tax-filings.submit', $filing->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-outline-success btn-sm">Submit</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('compliance.tax-filings.destroy', $filing->id) }}" class="d-inline" onsubmit="return confirm('Delete this filing?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No filings found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($filings, 'links'))
            <div class="card-footer bg-white">{{ $filings->links() }}</div>
        @endif
    </div>
</div>
@endsection
