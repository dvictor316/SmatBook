@extends('layout.mainlayout')

@section('title', 'Platform Payout History')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-vault me-2 text-warning"></i>Platform Payout History</h4>
            <small class="text-muted">All recorded investor dividends, commissions and payouts</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-warning btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#recordPayoutModal">
                <i class="fas fa-plus me-1"></i> Record Payout
            </button>
            <a href="{{ route('super_admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    @if(session('payout_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('payout_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('super_admin.platform_payouts.index') }}" class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">From Date</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">To Date</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Payout Type</label>
                    <select name="payout_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach(['dividend','commission','salary','refund','other'] as $t)
                            <option value="{{ $t }}" {{ request('payout_type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Recipient</label>
                    <input type="text" name="recipient" class="form-control form-control-sm" placeholder="Search name..." value="{{ request('recipient') }}">
                </div>
                <div class="col-sm-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter"></i></button>
                    <a href="{{ route('super_admin.platform_payouts.index') }}" class="btn btn-outline-secondary btn-sm w-100" title="Clear"><i class="fas fa-times"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-muted small mb-1">
                    @if(request()->hasAny(['from','to','payout_type','recipient']))
                        Total for Filtered Period
                    @else
                        Total Paid Out (All Time)
                    @endif
                </div>
                <div class="fw-bold fs-4 text-danger">₦{{ number_format($totalPayouts, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($payouts->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">No payouts recorded yet.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Recipient</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Payment Date</th>
                                <th>Recorded By</th>
                                <th>Recorded At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payouts as $payout)
                                <tr>
                                    <td class="text-muted small">{{ $loop->iteration + ($payouts->currentPage() - 1) * $payouts->perPage() }}</td>
                                    <td class="fw-semibold">{{ $payout->recipient_name }}</td>
                                    <td><span class="badge bg-secondary text-capitalize">{{ $payout->payout_type }}</span></td>
                                    <td class="text-danger fw-semibold">₦{{ number_format($payout->amount, 2) }}</td>
                                    <td class="text-muted small">{{ $payout->description ?: '—' }}</td>
                                    <td class="text-muted small">{{ $payout->paid_at ? $payout->paid_at->format('d M Y') : '—' }}</td>
                                    <td class="text-muted small">
                                        @if($payout->recorder)
                                            {{ $payout->recorder->name }}
                                        @else
                                            System
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $payout->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                @if($payout->notes)
                                    <tr class="bg-light">
                                        <td colspan="8" class="small text-muted ps-5 py-1">
                                            <i class="fas fa-sticky-note me-1"></i>{{ $payout->notes }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">
                    {{ $payouts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>{{-- /.content.container-fluid --}}
</div>{{-- /.page-wrapper --}}

{{-- Record Payout Modal --}}
<div class="modal fade" id="recordPayoutModal" tabindex="-1" aria-labelledby="recordPayoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('super_admin.platform_payouts.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="recordPayoutModalLabel"><i class="fas fa-money-bill-wave me-2 text-warning"></i>Record Payout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Recipient Name <span class="text-danger">*</span></label>
                        <input type="text" name="recipient_name" class="form-control" placeholder="e.g. John Investor" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount (₦) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payout Type <span class="text-danger">*</span></label>
                        <select name="payout_type" class="form-select" required>
                            <option value="dividend">Dividend</option>
                            <option value="commission">Commission</option>
                            <option value="salary">Salary</option>
                            <option value="refund">Refund</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Brief description (optional)" maxlength="500">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Date</label>
                        <input type="date" name="paid_at" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes (optional)" maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-semibold">
                        <i class="fas fa-save me-1"></i> Record Payout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
