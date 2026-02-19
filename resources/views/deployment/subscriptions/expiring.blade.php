@extends('layout.mainlayout')

@section('content')
{{-- Custom CSS to handle the 270px Sidebar --}}
<style>
    .page-wrapper {
        margin-left: 270px; /* Space for the sidebar */
        width: calc(100% - 270px); /* Adjust width to prevent horizontal scroll */
        transition: all 0.3s ease;
    }

    /* Responsive: Remove margin on smaller screens (Mobile/Tablet) */
    @media only screen and (max-width: 991px) {
        .page-wrapper {
            margin-left: 0;
            width: 100%;
        }
    }
</style>

<div class="page-wrapper page-content-wrapper">
    <div class="content container-fluid">

        {{-- Page Header --}}
        <div class="page-header">
            <div class="content-page-header">
                <h5>Expiring Subscriptions</h5>
                <div class="list-btn">
                    <ul class="filter-list">
                        <li>
                            <a class="btn btn-filters w-auto" href="javascript:void(0);">
                                <span class="me-2"><i class="fe fe-filter"></i></span>Filter 
                            </a>
                        </li>
                        <li>
                            <a class="btn btn-primary" href="javascript:void(0);" onclick="window.print()">
                                <i class="fa fa-print me-2"></i>Print Report
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Subscription Stats --}}
        <div class="row">
            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card subscribers-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stats-info">
                                <h6>Total Expiring</h6>
                                <h4>{{ $renewals->total() }}</h4>
                            </div>
                            <div class="stats-icon bg-soft-danger">
                                <i class="fas fa-exclamation-triangle text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Section --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="card-table">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-center table-hover datatable">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Subscriber</th>
                                        <th>Plan</th>
                                        <th>Amount</th>
                                        <th>Expiry Date</th>
                                        <th>Days Remaining</th>
                                        <th>Status</th>
                                        <th class="no-print">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($renewals as $key => $subscription)
                                        <tr>
                                            <td>{{ $renewals->firstItem() + $key }}</td>
                                            <td>
                                                <h2 class="table-avatar">
                                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                                        <img class="avatar-img rounded-circle" src="{{ asset('assets/img/profiles/avatar-01.jpg') }}" alt="User Image">
                                                    </a>
                                                    <a href="javascript:void(0);">{{ $subscription->user->name ?? 'Unknown User' }} <span>{{ $subscription->user->email ?? '' }}</span></a>
                                                </h2>
                                            </td>
                                            <td>{{ $subscription->plan_name ?? $subscription->plan ?? 'N/A' }}</td>
                                            <td>{{ number_format($subscription->amount, 2) }}</td>
                                            @php $expiryDate = !empty($subscription->end_date) ? \Carbon\Carbon::parse($subscription->end_date) : null; @endphp
                                            <td>{{ $expiryDate ? $expiryDate->format('d M Y') : 'N/A' }}</td>
                                            <td>
                                                @php
                                                    $daysLeft = $expiryDate ? now()->diffInDays($expiryDate, false) : 0;
                                                @endphp
                                                <span class="badge {{ $daysLeft <= 7 ? 'bg-danger' : 'bg-warning' }}">
                                                    {{ $daysLeft }} Days Left
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-pill bg-soft-info text-info">Expiring Soon</span>
                                            </td>
                                            <td class="no-print">
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-v"></i></a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <form action="{{ route('deployment.subscription.renew', $subscription->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item"><i class="far fa-edit me-2"></i>Extend</button>
                                                        </form>
                                                        <a class="dropdown-item" href="javascript:void(0);"><i class="far fa-paper-plane me-2"></i>Send Reminder</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No subscriptions expiring soon.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="row align-items-center">
            <div class="col-sm-12 col-md-5">
                <div class="dataTables_info">
                    Showing {{ $renewals->firstItem() }} to {{ $renewals->lastItem() }} of {{ $renewals->total() }} entries
                </div>
            </div>
            <div class="col-sm-12 col-md-7">
                <div class="pagination-tab">
                    {{ $renewals->links() }}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Specific logic for printing renewals from the Deployment domain
    window.onbeforeprint = function() {
        console.log("Generating Subscription Expiry Report for {{ env('SESSION_DOMAIN') }}");
    };
</script>
@endpush
