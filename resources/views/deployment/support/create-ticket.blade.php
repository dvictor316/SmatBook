@extends('layout.mainlayout')

@section('page-title', 'Create Support Ticket')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Open Support Ticket</h4>
            <p class="text-muted mb-0">Log a deployment issue with enough detail for follow-up and escalation.</p>
        </div>
        <a href="{{ route('deployment.support.tickets') }}" class="btn btn-light border">Back to Tickets</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('deployment.support.store-ticket') }}" class="row g-3">
                @csrf
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Subject</label>
                    <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Priority</label>
                    <select name="priority" class="form-select" required>
                        @foreach(['low', 'medium', 'high', 'urgent'] as $priority)
                            <option value="{{ $priority }}" {{ old('priority') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Related Client</label>
                    <select name="company_id" class="form-select">
                        <option value="">General / No specific client</option>
                        @foreach($managedCompanies as $company)
                            <option value="{{ $company->id }}" {{ (string) old('company_id') === (string) $company->id ? 'selected' : '' }}>
                                {{ $company->name ?? $company->company_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Issue Details</label>
                    <textarea name="message" rows="6" class="form-control" required>{{ old('message') }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
