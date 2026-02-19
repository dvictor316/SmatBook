<?php $page = 'edit-quotations'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="card mb-0">
            <div class="card-body">
                <div class="page-header">
                    <div class="content-page-header">
                        <h5>Edit Quotation</h5>
                    </div>
                </div>

                @if(empty($quotation))
                    <div class="alert alert-warning">No quotation found to edit.</div>
                @else
                    <form action="{{ route('quotations.update', $quotation->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Quotation ID</label>
                                <input type="text" name="quotation_id" class="form-control" value="{{ old('quotation_id', $quotation->quotation_id) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Customer</label>
                                <select name="customer_id" class="form-control">
                                    <option value="">Walk-in Customer</option>
                                    @foreach(($customers ?? collect()) as $customer)
                                        <option value="{{ $customer->id }}" {{ (string) old('customer_id', $quotation->customer_id) === (string) $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name ?? $customer->customer_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    @php $status = old('status', $quotation->status); @endphp
                                    <option value="Pending" {{ $status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="Sent" {{ $status === 'Sent' ? 'selected' : '' }}>Sent</option>
                                    <option value="Approved" {{ $status === 'Approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="Rejected" {{ $status === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Total Amount</label>
                                <input type="number" step="0.01" min="0" name="total" class="form-control" value="{{ old('total', $quotation->total) }}" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Note</label>
                                <textarea name="note" rows="4" class="form-control">{{ old('note', $quotation->note) }}</textarea>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('quotations') }}" class="btn btn-primary cancel me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Quotation</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
