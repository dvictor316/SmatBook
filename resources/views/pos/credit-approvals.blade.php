@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">POS Credit Approvals</h1>
            <a class="btn btn-outline-primary" href="{{ route('sales.showPos') }}">Back to POS</a>
        </div>
        @foreach(['success', 'error'] as $feedback)
            @if(session($feedback))
                <div class="alert alert-{{ $feedback === 'success' ? 'success' : 'warning' }}">{{ session($feedback) }}</div>
            @endif
        @endforeach
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Request</th><th>Customer and items</th><th>Credit</th><th>Projected balance</th><th>Limit</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($approvals as $approval)
                            @php $details = json_decode($approval->details, true); @endphp
                            <tr>
                                <td>
                                    #{{ $approval->id }} · {{ $approval->cashier_name ?? 'Cashier' }}
                                    <div>{{ $approval->branch_name ?: ($approval->branch_id ?: 'Unassigned') }}</div>
                                    <small>{{ $approval->created_at }}</small>
                                </td>
                                <td>
                                    <strong>{{ $details['customer'] }}</strong>
                                    <ul class="mb-0 ps-3">
                                        @foreach($details['items'] as $item)
                                            <li>{{ $item['quantity'] }} {{ $item['unit'] }} · {{ $item['name'] }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td>{{ number_format($approval->credit_amount, 2) }}</td>
                                <td>{{ number_format($approval->projected_outstanding, 2) }}</td>
                                <td>{{ number_format($approval->credit_limit, 2) }}</td>
                                <td>
                                    @if($approval->status === 'pending')
                                        <form method="POST" action="{{ route('pos.credit-approvals.review', $approval->id) }}" class="d-flex gap-2">
                                            @csrf
                                            <button class="btn btn-sm btn-primary" name="decision" value="approved">Approve</button>
                                            <button class="btn btn-sm btn-outline-danger" name="decision" value="rejected">Reject</button>
                                        </form>
                                    @else
                                        {{ ucfirst($approval->status) }}
                                        @if($approval->sale_id)
                                            · <a href="{{ route('sales.invoice.show', $approval->sale_id) }}">Sale #{{ $approval->sale_id }}</a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4">No credit approval requests.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($approvals->hasPages())
                <div class="card-body">{{ $approvals->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
