@extends('layout.mainlayout')

@section('page-title', 'SalesInvoicesadd Invoice')

@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h4 class="mb-2">SalesInvoicesadd Invoice</h4>
            <p class="text-muted mb-3">This module page is now wired and reachable. Replace content with final business UI as needed.</p>
            <div class="small text-muted">View file: <code>Sales/Invoices/add-invoice.blade.php</code></div>
            <div class="mt-3">
                <a href="{{ url()->previous() }}" class="btn btn-light border">Go Back</a>
                <a href="{{ route('home') }}" class="btn btn-primary">Home</a>
            </div>
        </div>
    </div>
</div>
@endsection
