@extends('layout.app')

@section('title', 'Cheque #' . $cheque->cheque_number)

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Cheque #{{ $cheque->cheque_number }}</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('cheques.index') }}">Cheques</a></li>
                    <li class="breadcrumb-item active">#{{ $cheque->cheque_number }}</li>
                </ul>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="{{ route('cheques.edit', $cheque) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                <form action="{{ route('cheques.destroy', $cheque) }}" method="POST" onsubmit="return confirm('Delete this cheque?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Cheque Details</h5></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th width="40%">Cheque Number</th>
                            <td>{{ $cheque->cheque_number }}</td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td><span class="badge bg-{{ $cheque->type === 'issue' ? 'warning' : 'info' }}">{{ ucfirst($cheque->type) }}</span></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @php
                                    $statusColor = match($cheque->status) {
                                        'cleared', 'deposited' => 'success',
                                        'pending' => 'warning',
                                        'bounced' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusColor }}">{{ ucfirst($cheque->status) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Bank</th>
                            <td>{{ $cheque->bank?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Payee / Payer</th>
                            <td>{{ $cheque->payee_name }}</td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td><strong>{{ number_format($cheque->amount, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Cheque Date</th>
                            <td>{{ $cheque->cheque_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Due / Clearing Date</th>
                            <td>{{ $cheque->due_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        @if($cheque->supplier)
                        <tr>
                            <th>Supplier</th>
                            <td>{{ $cheque->supplier->name }}</td>
                        </tr>
                        @endif
                        @if($cheque->customer)
                        <tr>
                            <th>Customer</th>
                            <td>{{ $cheque->customer->name }}</td>
                        </tr>
                        @endif
                        @if($cheque->notes)
                        <tr>
                            <th>Notes</th>
                            <td>{{ $cheque->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Update Status</h5></div>
                <div class="card-body">
                    <form action="{{ route('cheques.status', $cheque) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Status</label>
                            <select name="status" class="form-select" required>
                                @foreach(['pending','cleared','bounced','cancelled','voided','deposited'] as $s)
                                    <option value="{{ $s }}" @selected($cheque->status === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Reason for status change..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
