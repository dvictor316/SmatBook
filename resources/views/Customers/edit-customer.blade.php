<?php $page = 'edit-customer'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="card mb-0">
            <div class="card-body">
                <div class="page-header">
                    <div class="content-page-header">
                        <h5>Edit Customer: {{ $customer->customer_name }}</h5>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <form action="{{ route('customers.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <input type="hidden" name="status" value="{{ $customer->status ?? 'active' }}">

                    <div class="form-group-item">
                        <h5 class="form-title">Basic Details</h5>
                        <div class="profile-picture">
                            <div class="upload-profile">
                                <div class="profile-img">
                                    <img id="preview_img" class="avatar" 
                                         src="{{ $customer->image ? asset('storage/'.$customer->image) : asset('/assets/img/profiles/avatar-placeholder.jpg') }}" alt="profile">
                                </div>
                                <div class="add-profile">
                                    <h5>Upload a New Photo</h5>
                                    <input type="file" name="image" class="form-control mt-2" id="imgInput">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Name <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $customer->customer_name) }}" required>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Phone</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Currency</label>
                                    <select name="currency" class="select form-control">
                                        <option value="₦" {{ old('currency', $customer->currency) == '₦' ? 'selected' : '' }}>₦ (NGN)</option>
                                        <option value="$" {{ old('currency', $customer->currency) == '$' ? 'selected' : '' }}>$ (USD)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Website</label>
                                    <input type="text" name="website" class="form-control" value="{{ old('website', $customer->website) }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Notes</label>
                                    <input type="text" name="notes" class="form-control" value="{{ old('notes', $customer->notes) }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Opening Balance</label>
                                    <input type="number" step="0.01" min="0" name="balance" class="form-control" value="{{ old('balance', $customer->balance) }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Opening Balance Date</label>
                                    <input type="date" name="opening_balance_date" class="form-control" value="{{ old('opening_balance_date', $customer->opening_balance_date ?? '') }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Credit Limit</label>
                                    <input type="number" step="0.01" min="0" name="credit_limit" class="form-control" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-item">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="form-title">Billing Address</h5>
                                <div class="input-block mb-3"><label>Name</label><input type="text" name="billing_name" id="b_name" class="form-control" value="{{ old('billing_name', $customer->billing_name) }}"></div>
                                <div class="input-block mb-3"><label>Address Line 1</label><input type="text" name="billing_address_line1" id="b_addr1" class="form-control" value="{{ old('billing_address_line1', $customer->billing_address_line1) }}"></div>
                                <div class="row">
                                    <div class="col-lg-6"><div class="input-block mb-3"><label>City</label><input type="text" name="billing_city" id="b_city" class="form-control" value="{{ old('billing_city', $customer->billing_city) }}"></div></div>
                                    <div class="col-lg-6"><div class="input-block mb-3"><label>State</label><input type="text" name="billing_state" id="b_state" class="form-control" value="{{ old('billing_state', $customer->billing_state) }}"></div></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="billing-btn">
                                    <h5 class="form-title">Shipping Address</h5>
                                    <a href="javascript:void(0);" onclick="copyBilling()" class="btn btn-primary btn-sm">Copy from Billing</a>
                                </div>
                                <div class="input-block mb-3"><label>Name</label><input type="text" name="shipping_name" id="s_name" class="form-control" value="{{ old('shipping_name', $customer->shipping_name) }}"></div>
                                <div class="input-block mb-3"><label>Address Line 1</label><input type="text" name="shipping_address_line1" id="s_addr1" class="form-control" value="{{ old('shipping_address_line1', $customer->shipping_address_line1) }}"></div>
                                <div class="row">
                                    <div class="col-lg-6"><div class="input-block mb-3"><label>City</label><input type="text" name="shipping_city" id="s_city" class="form-control" value="{{ old('shipping_city', $customer->shipping_city) }}"></div></div>
                                    <div class="col-lg-6"><div class="input-block mb-3"><label>State</label><input type="text" name="shipping_state" id="s_state" class="form-control" value="{{ old('shipping_state', $customer->shipping_state) }}"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-customer">
                        <h5 class="form-title">Bank Details</h5>
                        <div class="row">
                            <div class="col-lg-4"><div class="input-block mb-3"><label>Bank Name</label><input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $customer->bank_name) }}"></div></div>
                            <div class="col-lg-4"><div class="input-block mb-3"><label>Account Holder</label><input type="text" name="account_holder" class="form-control" value="{{ old('account_holder', $customer->account_holder) }}"></div></div>
                            <div class="col-lg-4"><div class="input-block mb-3"><label>Account Number</label><input type="text" name="account_number" class="form-control" value="{{ old('account_number', $customer->account_number) }}"></div></div>
                            <div class="col-lg-4"><div class="input-block mb-3"><label>IFSC / Sort Code</label><input type="text" name="ifsc" class="form-control" value="{{ old('ifsc', $customer->ifsc) }}"></div></div>
                            <div class="col-lg-4"><div class="input-block mb-3"><label>Branch</label><input type="text" name="branch" class="form-control" value="{{ old('branch', $customer->branch) }}"></div></div>
                        </div>
                    </div>

                    <div class="add-customer-btns text-end">
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('imgInput').onchange = function (evt) {
        const [file] = this.files
        if (file) { document.getElementById('preview_img').src = URL.createObjectURL(file) }
    }

    function copyBilling() {
        document.getElementById('s_name').value = document.getElementById('b_name').value;
        document.getElementById('s_addr1').value = document.getElementById('b_addr1').value;
        document.getElementById('s_city').value = document.getElementById('b_city').value;
        document.getElementById('s_state').value = document.getElementById('b_state').value;
    }
</script>
@endsection
