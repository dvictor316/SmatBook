@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row">

            <div class="col-lg-9">
                @include('Sales.Invoices.invoice-details') 
            </div>

            <div class="col-lg-3 d-print-none">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Invoice Status</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('invoices.update-status', $sale->id) }}" method="POST">
                            @csrf
                            <select name="status" class="form-control mb-3" onchange="this.form.submit()">
                                <option value="Unpaid" {{ $sale->status == 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="Partially Paid" {{ $sale->status == 'Partially Paid' ? 'selected' : '' }}>Partially Paid</option>
                                <option value="Paid" {{ $sale->status == 'Paid' ? 'selected' : '' }}>Paid</option>
                                <option value="Overdue" {{ $sale->status == 'Overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Activity Timeline</h5>
                    </div>
                    <div class="card-body">
                        <ul class="activity-feed">
                            @forelse($sale->activities as $activity)
                                <li class="feed-item">
                                    <span class="date">{{ $activity->created_at->format('d M') }}</span>
                                    <span class="text">
                                        <strong>{{ $activity->user->name ?? 'System' }}</strong> 
                                        {{ $activity->description }}
                                    </span>
                                </li>
                            @empty
                                <li class="text-muted">No activity recorded.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function printInvoice() {
        window.print();
    }
</script>
@endsection