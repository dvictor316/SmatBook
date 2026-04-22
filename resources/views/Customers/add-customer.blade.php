<?php $page = 'add-customer'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="card mb-0">
            <div class="card-body">
                <div class="page-header">
                    <div class="content-page-header">
                        <h5>Add New Customer</h5>
                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importCustomersModal">
                                <i class="fas fa-file-upload me-1"></i> Import Customers
                            </button>
                            <a href="{{ route('customers.import.template') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-file-download me-1"></i> Download Template
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <form action="{{ route('customers.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="form-group-item">
                                <h5 class="form-title">Basic Details</h5>

                                <div class="profile-picture">
                                    <div class="upload-profile">
                                        <div class="profile-img">
                                            <img id="blah" class="avatar" src="{{ URL::asset('/assets/img/profiles/avatar-placeholder.jpg') }}" alt="preview">
                                        </div>
                                        <div class="add-profile">
                                            <h5>Customer Photo</h5>
                                            <span id="file-name-display">No file selected</span>
                                        </div>
                                    </div>
                                    <div class="img-upload">
                                        <label class="btn btn-upload">
                                            Upload <input type="file" name="image" onchange="updatePreview(this)">
                                        </label>
                                        <button type="button" class="btn btn-remove" onclick="resetPreview()">Remove</button>
                                    </div>
                                    @error('image') <p class="text-danger small">{{ $message }}</p> @enderror
                                </div>

                                <div class="row">
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Customer Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('customer_name') is-invalid @enderror" 
                                                   placeholder="Full Name" name="customer_name" value="{{ old('customer_name') }}" required>
                                            @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Email Address</label>
                                            <input type="email" class="form-control" placeholder="Email" name="email" value="{{ old('email') }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Phone Number</label>
                                            <input type="text" class="form-control" placeholder="Phone" name="phone" value="{{ old('phone') }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Currency</label>
                                            <select name="currency" class="select form-control">
                                                <option value="₦">₦ (Naira)</option>
                                                <option value="$">$ (USD)</option>
                                                <option value="£">£ (GBP)</option>
                                                <option value="€">€ (EUR)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Website</label>
                                            <input type="text" class="form-control" name="website" placeholder="https://example.com" value="{{ old('website') }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Notes</label>
                                            <input type="text" class="form-control" name="notes" placeholder="Internal remarks" value="{{ old('notes') }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Opening Balance</label>
                                            <input type="number" step="0.01" min="0" class="form-control @error('balance') is-invalid @enderror" name="balance" placeholder="0.00" value="{{ old('balance', 0) }}">
                                            <small class="text-muted">This opening balance will reflect on the customer credit report.</small>
                                            @error('balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Opening Balance Date</label>
                                            <input type="date" class="form-control @error('opening_balance_date') is-invalid @enderror" name="opening_balance_date" value="{{ old('opening_balance_date') }}">
                                            @error('opening_balance_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Credit Limit</label>
                                            <input type="number" step="0.01" min="0" class="form-control @error('credit_limit') is-invalid @enderror" name="credit_limit" placeholder="0.00" value="{{ old('credit_limit', 0) }}">
                                            <small class="text-muted">Set the maximum credit exposure allowed for this customer.</small>
                                            @error('credit_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    @if(!empty($availableBranches))
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Branch</label>
                                            <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror">
                                                <option value="">Use Active Branch</option>
                                                @foreach($availableBranches as $branch)
                                                    <option value="{{ $branch['id'] }}" @selected((string) old('branch_id') === (string) ($branch['id'] ?? ''))>{{ $branch['name'] }}</option>
                                                @endforeach
                                            </select>
                                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group-item">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="form-title">Billing Address</h5>
                                        <div class="input-block mb-3"><label>Name</label><input type="text" name="billing_name" id="b_name" class="form-control" value="{{ old('billing_name') }}"></div>
                                        <div class="input-block mb-3"><label>Address Line 1</label><input type="text" name="billing_address_line1" id="b_addr1" class="form-control" value="{{ old('billing_address_line1') }}"></div>
                                        <div class="row">
                                            <div class="col-lg-6"><div class="input-block mb-3"><label>City</label><input type="text" name="billing_city" id="b_city" class="form-control" value="{{ old('billing_city') }}"></div></div>
                                            <div class="col-lg-6"><div class="input-block mb-3"><label>State</label><input type="text" name="billing_state" id="b_state" class="form-control" value="{{ old('billing_state') }}"></div></div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="billing-btn">
                                            <h5 class="form-title">Shipping Address</h5>
                                            <a href="javascript:void(0);" onclick="copyBilling()" class="btn btn-primary btn-sm">Copy from Billing</a>
                                        </div>
                                        <div class="input-block mb-3"><label>Name</label><input type="text" name="shipping_name" id="s_name" class="form-control"></div>
                                        <div class="input-block mb-3"><label>Address Line 1</label><input type="text" name="shipping_address_line1" id="s_addr1" class="form-control"></div>
                                        <div class="row">
                                            <div class="col-lg-6"><div class="input-block mb-3"><label>City</label><input type="text" name="shipping_city" id="s_city" class="form-control"></div></div>
                                            <div class="col-lg-6"><div class="input-block mb-3"><label>State</label><input type="text" name="shipping_state" id="s_state" class="form-control"></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group-customer">
                                <h5 class="form-title">Bank Details</h5>
                                <div class="row">
                                    <div class="col-lg-4"><div class="input-block mb-3"><label>Bank Name</label><input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}"></div></div>
                                    <div class="col-lg-4"><div class="input-block mb-3"><label>Account Number</label><input type="text" name="account_number" class="form-control" value="{{ old('account_number') }}"></div></div>
                                    <div class="col-lg-4"><div class="input-block mb-3"><label>Branch / IFSC</label><input type="text" name="branch" class="form-control" value="{{ old('branch') }}"></div></div>
                                </div>
                            </div>

                            <div class="add-customer-btns text-end">
                                <a href="{{ route('customers.index') }}" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Customer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importCustomersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Customers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Upload CSV or Excel files with customer opening balances. Imported balances will reflect on customer credit reporting.</p>
                    <div class="mb-3">
                        <label class="form-label">Spreadsheet File</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv,.txt,.xls,.xlsx,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            <input type="checkbox" name="update_existing" value="1">
                            <span>Update existing customers when duplicates are found</span>
                        </label>
                        <small class="text-muted">When enabled, imports will update matching customers instead of skipping them.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import Customers</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updatePreview(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('blah').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
        document.getElementById('file-name-display').innerText = input.files[0].name;
    }
}

function resetPreview() {
    document.getElementById('blah').src = "{{ URL::asset('/assets/img/profiles/avatar-placeholder.jpg') }}";
    document.getElementById('file-name-display').innerText = "No file selected";
    document.querySelector('input[type="file"]').value = "";
}

function copyBilling() {
    document.getElementById('s_name').value = document.getElementById('b_name').value;
    document.getElementById('s_addr1').value = document.getElementById('b_addr1').value;
    document.getElementById('s_city').value = document.getElementById('b_city').value;
    document.getElementById('s_state').value = document.getElementById('b_state').value;
}
</script>
@endsection
