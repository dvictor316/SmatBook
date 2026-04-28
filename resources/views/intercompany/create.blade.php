@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">New Intercompany Transaction</h5>
                    <p class="text-muted mb-0">Create a transaction between the active company and a related counter-party.</p>
                </div>
                <div>
                    <a href="{{ route('intercompany.index') }}" class="btn btn-outline-primary">Back to Transactions</a>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('intercompany.store') }}" class="row g-3">
                    @csrf
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Counter-party Company</label>
                        <div class="d-flex align-items-stretch gap-2">
                            <select name="counterparty_company_id" class="form-select" @if($companies->isNotEmpty()) required @endif>
                                <option value="">Select company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" @selected(old('counterparty_company_id') == $company->id)>{{ $company->name ?? $company->company_name ?? ('Company #' . $company->id) }}</option>
                                @endforeach
                            </select>
                            <a href="{{ url('/superadmin/companies/create') }}" class="btn btn-outline-primary flex-shrink-0 px-3" title="Add company" aria-label="Add company">+</a>
                        </div>
                        @if($companies->isEmpty())
                            <div class="form-text text-danger">No counter-party companies are available yet. Add one to continue.</div>
                            <div class="mt-2">
                                <a href="{{ url('/superadmin/companies/create') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fe fe-plus me-1"></i> Add Company
                                </a>
                            </div>
                        @endif
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Transaction Type</label>
                        <select name="transaction_type" class="form-select" required>
                            @foreach(['loan', 'purchase', 'sale', 'allocation', 'management_fee', 'dividend', 'transfer'] as $type)
                                <option value="{{ $type }}" @selected(old('transaction_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Transaction Date</label>
                        <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency" class="form-control" maxlength="3" value="{{ old('currency', 'NGN') }}" required>
                    </div>
                    <div class="col-xl-6 col-md-12">
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}" placeholder="Optional reference">
                    </div>
                    <div class="col-xl-6 col-md-6">
                        <label class="form-label">Source Account</label>
                        <select name="source_account_id" class="form-select">
                            <option value="">Select source account</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected(old('source_account_id') == $account->id)>{{ trim(($account->code ? $account->code . ' - ' : '') . $account->name) }}</option>
                            @endforeach
                        </select>
                        @if($accounts->isEmpty())
                            <div class="form-text text-warning">No accounts are available yet.</div>
                        @endif
                    </div>
                    <div class="col-xl-6 col-md-6">
                        <label class="form-label">Target Account</label>
                        <select name="target_account_id" class="form-select">
                            <option value="">Select target account</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected(old('target_account_id') == $account->id)>{{ trim(($account->code ? $account->code . ' - ' : '') . $account->name) }}</option>
                            @endforeach
                        </select>
                        @if($accounts->isEmpty())
                            <div class="form-text text-warning">No accounts are available yet.</div>
                        @endif
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" @disabled($companies->isEmpty())>Create Transaction</button>
                        <a href="{{ route('intercompany.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
