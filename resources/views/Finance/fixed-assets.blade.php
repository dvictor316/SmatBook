<?php $page = 'finance-fixed-assets'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Fixed Assets
            @endslot
        @endcomponent

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Add Fixed Asset</h5>
                        <form action="{{ route('finance.fixed-assets.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Asset Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Office generator">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fixed Asset Account</label>
                                <select name="account_id" class="form-select">
                                    <option value="">Select asset account</option>
                                    @foreach($assetAccounts as $account)
                                        <option value="{{ $account->id }}" {{ (string) old('account_id') === (string) $account->id ? 'selected' : '' }}>
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Accumulated Depreciation Account</label>
                                <select name="depreciation_account_id" class="form-select">
                                    <option value="">Select depreciation account</option>
                                    @foreach($assetAccounts as $account)
                                        <option value="{{ $account->id }}" {{ (string) old('depreciation_account_id') === (string) $account->id ? 'selected' : '' }}>
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Depreciation Expense Account</label>
                                <select name="expense_account_id" class="form-select">
                                    <option value="">Select expense account</option>
                                    @foreach($expenseAccounts as $account)
                                        <option value="{{ $account->id }}" {{ (string) old('expense_account_id') === (string) $account->id ? 'selected' : '' }}>
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Acquired On</label>
                                    <input type="date" name="acquired_on" class="form-control" value="{{ old('acquired_on', now()->toDateString()) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Useful Life (Months)</label>
                                    <input type="number" name="useful_life_months" min="1" class="form-control" value="{{ old('useful_life_months', 36) }}">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Depreciation Timing</label>
                                    <select name="depreciation_frequency" class="form-select">
                                        <option value="monthly" {{ old('depreciation_frequency', 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="quarterly" {{ old('depreciation_frequency') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                        <option value="yearly" {{ old('depreciation_frequency') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                    </select>
                                    <small class="text-muted">Depreciation posts only when this schedule is due.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cost</label>
                                    <input type="number" name="cost" step="0.01" min="0" class="form-control" value="{{ old('cost') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Salvage Value</label>
                                    <input type="number" name="salvage_value" step="0.01" min="0" class="form-control" value="{{ old('salvage_value', 0) }}">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                            </div>
                            <input type="hidden" name="depreciation_method" value="straight_line">
                            <button type="submit" class="btn btn-primary w-100 mt-3">Add Asset</button>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h6 class="mb-3">Asset Summary</h6>
                        <div class="mb-2 d-flex justify-content-between"><span>Total Assets</span><strong>{{ number_format((int) ($summary['asset_count'] ?? 0)) }}</strong></div>
                        <div class="mb-2 d-flex justify-content-between"><span>Due for Depreciation</span><strong>{{ number_format((int) ($dueAssetsCount ?? 0)) }}</strong></div>
                        <div class="mb-2 d-flex justify-content-between"><span>Gross Cost</span><strong>₦{{ number_format((float) ($summary['gross_cost'] ?? 0), 2) }}</strong></div>
                        <div class="mb-2 d-flex justify-content-between"><span>Accum. Depreciation</span><strong>₦{{ number_format((float) ($summary['accumulated_depreciation'] ?? 0), 2) }}</strong></div>
                        <div class="d-flex justify-content-between"><span>Book Value</span><strong>₦{{ number_format((float) ($summary['book_value'] ?? 0), 2) }}</strong></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">Asset Register</h5>
                                <p class="text-muted mb-0 small">Depreciation follows each asset schedule and is also checked automatically every day.</p>
                            </div>
                            <form action="{{ route('finance.fixed-assets.depreciate-due') }}" method="POST" class="d-flex gap-2 align-items-center">
                                @csrf
                                <input type="date" name="run_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                                <button type="submit" class="btn btn-sm btn-primary">Post Due Depreciation</button>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Asset</th>
                                        <th>Schedule</th>
                                        <th>Cost</th>
                                        <th>Accum. Dep.</th>
                                        <th>Book Value</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assets as $asset)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $asset->name }}</div>
                                                <small class="text-muted">{{ $asset->asset_code }} | {{ $asset->assetAccount?->name ?? 'No asset account' }}</small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ ucwords($asset->depreciation_frequency ?? 'monthly') }}</div>
                                                <small class="text-muted">Next {{ $asset->next_due_date ? \Carbon\Carbon::parse($asset->next_due_date)->format('d M Y') : 'N/A' }}</small>
                                                <div><small class="text-muted">Due amount ₦{{ number_format((float) ($asset->scheduled_depreciation_amount ?? 0), 2) }}</small></div>
                                            </td>
                                            <td>₦{{ number_format((float) $asset->cost, 2) }}</td>
                                            <td>₦{{ number_format((float) $asset->accumulated_depreciation, 2) }}</td>
                                            <td>₦{{ number_format((float) $asset->book_value, 2) }}</td>
                                            <td>
                                                <span class="badge {{ in_array($asset->status, ['active'], true) ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ ucwords(str_replace('_', ' ', $asset->status)) }}
                                                </span>
                                                @if($asset->depreciation_is_due)
                                                    <div><span class="badge bg-warning text-dark mt-1">Due</span></div>
                                                @endif
                                                @if($asset->last_depreciated_on)
                                                    <div><small class="text-muted">Last run {{ $asset->last_depreciated_on->format('d M Y') }}</small></div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <form action="{{ route('finance.fixed-assets.depreciate', $asset->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $asset->depreciation_is_due ? 'btn-primary' : 'btn-outline-secondary' }}">
                                                        {{ $asset->depreciation_is_due ? 'Post Due' : 'Not Due' }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No fixed assets added yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $assets->links() }}</div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Recent Depreciation Runs</h5>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Asset</th>
                                        <th>Period</th>
                                        <th>Timing</th>
                                        <th>Reference</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($depreciations as $run)
                                        <tr>
                                            <td>{{ $run->run_date?->format('d M Y') ?? 'N/A' }}</td>
                                            <td>{{ $run->asset?->name ?? 'Asset #' . $run->fixed_asset_id }}</td>
                                            <td>
                                                {{ $run->period_label }}
                                                @if($run->period_start_on && $run->period_end_on)
                                                    <div><small class="text-muted">{{ $run->period_start_on->format('d M Y') }} - {{ $run->period_end_on->format('d M Y') }}</small></div>
                                                @endif
                                            </td>
                                            <td>{{ ucwords($run->depreciation_frequency ?? 'Monthly') }}</td>
                                            <td>{{ $run->reference_no }}</td>
                                            <td class="text-end">₦{{ number_format((float) $run->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No depreciation runs posted yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
