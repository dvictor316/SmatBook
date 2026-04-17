@extends('layout.mainlayout')

@section('content')

<div class="sb-shell page-content-wrapper dm-main-content">

    <button onclick="toggleSidebar()" class="btn btn-light border mb-3 d-lg-none">
        <i class="fas fa-bars"></i> Menu
    </button>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-dark">Subscription Renewals</h5>
                <small class="text-muted">Manage expiring company licenses</small>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">
                <i class="fas fa-print me-1"></i> Print
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Company</th>
                            <th>Plan</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($renewals as $renewal)
                            @php
                                // Ensure date parsing is robust
                                $expiry = !empty($renewal->end_date) ? \Carbon\Carbon::parse($renewal->end_date) : null;
                                $isExpired = $expiry ? $expiry->isPast() : false;
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" 
                                             style="width: 40px; height: 40px; color: #4361ee; font-weight: bold;">
                                            {{ strtoupper(substr($renewal->company->name ?? 'C', 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong class="d-block text-dark">{{ $renewal->company->name ?? 'N/A' }}</strong>
                                            <small class="text-muted" style="font-size: 0.85em;">{{ $renewal->company->subdomain ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10">
                                        {{ $renewal->plan_name ?? $renewal->plan ?? 'Basic' }}
                                    </span>
                                </td>
                                <td>
                                    @if($expiry)
                                        <div class="d-flex flex-column">
                                            <span>{{ $expiry->format('d M, Y') }}</span>
                                            @if(!$isExpired && $expiry->diffInDays(now()) < 30)
                                                <small class="text-warning fw-bold" style="font-size: 0.75rem;">
                                                    Expiring in {{ $expiry->diffInDays(now()) }} days
                                                </small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isExpired)
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">
                                            Expired
                                        </span>
                                    @else
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-bell me-2"></i>Send Reminder</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-sync me-2"></i>Renew Manually</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-check-circle fa-2x mb-3 opacity-25"></i>
                                    <p>No pending renewals found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end p-3 border-top">
                {{ $renewals->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
