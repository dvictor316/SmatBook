@php
    $supplier = $supplier ?? null;
    $editing = $supplier !== null;
    $value = fn ($field, $default = '') => old($field, $supplier?->{$field} ?? $default);
@endphp
<style>
    .supplier-editor { background: #f6f8fc; min-height: 100vh; }
    .supplier-editor .content { max-width: 1240px; margin: auto; }
    .supplier-editor .supplier-banner { background: linear-gradient(120deg, #10264f, #1e40af); color: white; border-radius: 18px; padding: 28px; }
    .supplier-editor .supplier-banner h3 { color: white; }
    .supplier-editor .supplier-banner p { color: #dbeafe; max-width: 650px; }
    .supplier-editor .supplier-section { border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 18px #10264f05; }
    .supplier-editor .supplier-section .card-header { background: transparent; padding: 20px 24px; border-bottom: 1px solid #eef2f7; }
    .supplier-editor .supplier-section .card-body { padding: 24px; }
    .supplier-editor .section-number { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 9px; background: #eff6ff; color: #1e40af; margin-right: 8px; }
    .supplier-editor .form-label { font-weight: 600; font-size: 13px; }
    .supplier-editor .form-control { min-height: 44px; border-radius: 9px; }
    .supplier-editor .form-text { line-height: 1.5; }
    .supplier-editor summary { cursor: pointer; font-weight: 600; color: #10264f; }
    .supplier-editor .supplier-actions { border-top: 1px solid #e2e8f0; padding-top: 20px; }
</style>
<div class="page-wrapper supplier-editor">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <a href="{{ route('suppliers.index') }}" class="text-muted"><i class="fas fa-arrow-left me-2"></i>Suppliers</a>
            <span class="small text-muted">Only the supplier name is required</span>
        </div>
        <div class="supplier-banner mb-4">
            <span class="small text-uppercase fw-semibold">Purchasing · Supplier directory</span>
            <h3 class="mt-2 mb-2">{{ $editing ? 'Edit supplier' : 'Add a supplier' }}</h3>
            <p class="mb-0">Keep the right contact, payment details, and purchasing information together for your next order.</p>
        </div>
        @if($errors->any())
            <div class="alert alert-danger" role="alert"><strong>Please correct the following:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form action="{{ $editing ? route('suppliers.update', $supplier->id) : route('suppliers.store') }}" method="POST">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="row g-4">
                <div class="col-lg-8">
                    <section class="card supplier-section">
                        <div class="card-header"><h5 class="mb-0"><span class="section-number">1</span>Business & contact</h5></div>
                        <div class="card-body row g-3">
                            @foreach(['name' => ['Supplier / Business Name', 'text', 'e.g. Sunrise Foods Ltd'], 'contact' => ['Contact Person', 'text', 'Who handles your orders?'], 'email' => ['Email Address', 'email', 'orders@example.com'], 'phone' => ['Phone Number', 'tel', 'Primary contact number'], 'alternate_phone' => ['Alternate Phone / WhatsApp', 'tel', 'Optional second number'], 'website' => ['Website', 'url', 'https://example.com']] as $field => [$label, $type, $placeholder])
                                <div class="col-md-6">
                                    <label for="supplier_{{ $field }}" class="form-label">{{ $label }} @if($field === 'name')<span class="text-danger">*</span>@endif</label>
                                    <input id="supplier_{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="form-control @error($field) is-invalid @enderror" value="{{ $field === 'name' ? old('name', $supplier?->name ?? $supplier?->supplier_name ?? $supplier?->company_name ?? '') : $value($field) }}" placeholder="{{ $placeholder }}" maxlength="{{ $field === 'website' ? 255 : ($field === 'alternate_phone' ? 50 : 191) }}" @required($field === 'name')>
                                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>
                    </section>
                    <section class="card supplier-section">
                        <div class="card-header"><h5 class="mb-0"><span class="section-number">2</span>Business address</h5></div>
                        <div class="card-body row g-3">
                            <div class="col-12"><label for="supplier_address" class="form-label">Street Address</label><textarea id="supplier_address" name="address" rows="2" maxlength="255" class="form-control @error('address') is-invalid @enderror" placeholder="Office, warehouse, or delivery address">{{ $value('address') }}</textarea>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            @foreach(['city' => 'City / Town', 'state' => 'State / Region', 'country' => 'Country'] as $field => $label)
                                <div class="col-md-4"><label for="supplier_{{ $field }}" class="form-label">{{ $label }}</label><input id="supplier_{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" maxlength="100" value="{{ $value($field) }}">@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            @endforeach
                        </div>
                    </section>
                    <section class="card supplier-section">
                        <div class="card-header"><h5 class="mb-0"><span class="section-number">3</span>Purchasing information</h5></div>
                        <div class="card-body row g-3">
                            <div class="col-md-6"><label for="supplier_tax_number" class="form-label">Tax / Registration Number</label><input id="supplier_tax_number" name="tax_number" maxlength="100" class="form-control @error('tax_number') is-invalid @enderror" value="{{ $value('tax_number') }}">@error('tax_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label for="supplier_payment_terms_days" class="form-label">Agreed Payment Terms (days)</label><input type="number" min="0" max="3650" step="1" id="supplier_payment_terms_days" name="payment_terms_days" class="form-control @error('payment_terms_days') is-invalid @enderror" value="{{ $value('payment_terms_days') }}" placeholder="e.g. 30"><div class="form-text">0 = payment on delivery. Reference for your purchase agreements.</div>@error('payment_terms_days')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><label for="supplier_supply_description" class="form-label">Goods / Services Supplied</label><textarea id="supplier_supply_description" name="supply_description" maxlength="1000" rows="2" class="form-control @error('supply_description') is-invalid @enderror" placeholder="e.g. Rice, cooking oil, and packaged groceries">{{ $value('supply_description') }}</textarea>@error('supply_description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><label for="supplier_notes" class="form-label">Internal Notes</label><textarea id="supplier_notes" name="notes" maxlength="3000" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="Ordering instructions, delivery days, or other useful details">{{ $value('notes') }}</textarea>@error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        </div>
                    </section>
                </div>
                <div class="col-lg-4">
                    <section class="card supplier-section">
                        <div class="card-header"><h5 class="mb-0">Payment details</h5><p class="small text-muted mb-0 mt-2">Optional bank details for supplier payments.</p></div>
                        <div class="card-body row g-3">
                            @foreach(['bank_name' => 'Bank Name', 'account_name' => 'Account Holder Name', 'account_number' => 'Account Number'] as $field => $label)
                                <div class="col-12"><label for="supplier_{{ $field }}" class="form-label">{{ $label }}</label><input type="text" id="supplier_{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" maxlength="{{ $field === 'account_number' ? 50 : 191 }}" value="{{ $value($field) }}">@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            @endforeach
                        </div>
                    </section>
                    <section class="card supplier-section">
                        <div class="card-header"><h5 class="mb-0">Opening balance</h5><p class="small text-muted mb-0 mt-2">Amount already owed before recording purchases here.</p></div>
                        <div class="card-body row g-3">
                            <div class="col-12"><label for="supplier_opening_balance" class="form-label">Amount Owed</label><input type="number" step="0.01" id="supplier_opening_balance" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ $value('opening_balance') }}" placeholder="0.00">@error('opening_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><label for="supplier_opening_balance_date" class="form-label">Balance As Of</label><input type="date" id="supplier_opening_balance_date" name="opening_balance_date" class="form-control @error('opening_balance_date') is-invalid @enderror" value="{{ $value('opening_balance_date') }}">@error('opening_balance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><p class="small text-muted mb-0">Leave the amount blank or enter 0 for a new supplier with no previous debt.</p></div>
                        </div>
                    </section>
                </div>
            </div>
            <div class="supplier-actions d-flex align-items-center justify-content-end gap-2 mb-4">
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">{{ $editing ? 'Update Supplier' : 'Save Supplier' }}</button>
            </div>
        </form>
    </div>
</div>
