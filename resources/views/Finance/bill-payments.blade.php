<?php $page = 'bill-payments'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper"><div class="content container-fluid">
    @component('components.page-header') @slot('title') Bill Payments &amp; Commissions @endslot @endcomponent
    @unless($providerConfigured)<div class="alert alert-warning"><strong>Provider setup required.</strong> Add a valid Flutterwave secret key in Payment Settings after completing provider KYC, funding the source wallet, and whitelisting this server IP. Payments remain disabled until then.</div>@endunless

    <div class="row g-3 mb-4">
        @foreach([
            ['Transactions', number_format($summary['count']), '#2563eb'],
            ['Successful Value', \App\Support\GeoCurrency::format($summary['successful_amount']), '#0f766e'],
            ['Provider Fees', \App\Support\GeoCurrency::format($summary['fees']), '#b77900'],
            ['Commission Earned', \App\Support\GeoCurrency::format($summary['commission']), '#7c3aed'],
        ] as [$label, $value, $color])
            <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm h-100" style="border-top:4px solid {{ $color }} !important;border-radius:6px"><div class="card-body"><small class="text-uppercase text-muted fw-bold">{{ $label }}</small><div class="fs-4 fw-bold mt-2">{{ $value }}</div></div></div></div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <section class="border rounded bg-white shadow-sm p-4">
                <h4 class="mb-1">New Bill Payment</h4><p class="text-muted">Billers and products are loaded directly from the configured provider.</p>
                <form method="POST" action="{{ route('bill-payments.store') }}" id="billPaymentForm">@csrf
                    <div class="mb-3"><label class="form-label">Category *</label><select class="form-select" id="billCategory" name="category_code" required {{ $providerConfigured ? '' : 'disabled' }}><option value="">Load categories...</option></select></div>
                    <div class="mb-3"><label class="form-label">Biller *</label><select class="form-select" id="billBiller" name="biller_code" required disabled><option value="">Select category first</option></select></div>
                    <div class="mb-3"><label class="form-label">Product / Package *</label><select class="form-select" id="billItem" name="item_code" required disabled><option value="">Select biller first</option></select><input type="hidden" name="product_name" id="billProductName"></div>
                    <div class="mb-3"><label class="form-label" id="customerIdentifierLabel">Customer Identifier *</label><div class="input-group"><input class="form-control" name="customer_identifier" id="customerIdentifier" required {{ $providerConfigured ? '' : 'disabled' }}><button class="btn btn-outline-primary" type="button" id="validateBillCustomer" disabled>Validate</button></div><div class="form-text" id="validationResult"></div></div>
                    <div class="mb-4"><label class="form-label">Amount (NGN) *</label><input class="form-control" type="number" min="1" step="0.01" name="amount" id="billAmount" required {{ $providerConfigured ? '' : 'disabled' }}></div>
                    <button class="btn btn-primary w-100" id="submitBillPayment" type="submit" disabled><i class="fas fa-shield-halved me-1"></i> Submit Bill Payment</button>
                </form>
            </section>
        </div>
        <div class="col-xl-8">
            <section class="border rounded bg-white shadow-sm p-4">
                <div class="mb-3"><h4 class="mb-1">Payment Register</h4><p class="text-muted mb-0">Provider-reported commissions are recorded during reconciliation; SmartProbook does not estimate them.</p></div>
                <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Reference</th><th>Bill</th><th>Customer</th><th>Amount</th><th>Fee</th><th>Commission</th><th>Status</th><th></th></tr></thead><tbody>
                    @forelse($payments as $payment)
                        @php($statusClass = match($payment->status){'successful'=>'bg-success','failed','reversed'=>'bg-danger','processing'=>'bg-primary',default=>'bg-warning text-dark'})
                        <tr><td><strong>{{ $payment->reference }}</strong><div class="small text-muted">{{ $payment->created_at->format('d M Y H:i') }}</div></td><td>{{ $payment->product_name ?: $payment->item_code }}<div class="small text-muted">{{ $payment->biller_code }}</div></td><td>{{ $payment->customer_name ?: $payment->customer_identifier }}</td><td class="fw-bold">{{ \App\Support\GeoCurrency::format((float)$payment->amount) }}</td><td>{{ \App\Support\GeoCurrency::format((float)$payment->provider_fee) }}</td><td class="text-success fw-bold">{{ \App\Support\GeoCurrency::format((float)$payment->commission_earned) }}</td><td><span class="badge {{ $statusClass }}">{{ ucfirst($payment->status) }}</span>@if($payment->recharge_token)<div class="small text-success mt-1">Token available</div>@endif</td><td class="text-end">@unless(in_array($payment->status,['failed','reversed']))<form method="POST" action="{{ route('bill-payments.refresh', $payment) }}">@csrf<button class="btn btn-sm btn-outline-primary" title="Refresh provider status"><i class="fas fa-rotate"></i></button></form>@endunless</td></tr>
                    @empty<tr><td colspan="8" class="text-center text-muted py-5">No bill payments have been submitted.</td></tr>@endforelse
                </tbody></table></div><div class="mt-3">{{ $payments->links() }}</div>
            </section>
        </div>
    </div>
</div></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const configured = @json($providerConfigured);
    if (!configured) return;
    const category = document.getElementById('billCategory');
    const biller = document.getElementById('billBiller');
    const item = document.getElementById('billItem');
    const amount = document.getElementById('billAmount');
    const customer = document.getElementById('customerIdentifier');
    const validateButton = document.getElementById('validateBillCustomer');
    const submitButton = document.getElementById('submitBillPayment');
    const result = document.getElementById('validationResult');
    const productName = document.getElementById('billProductName');
    const csrf = document.querySelector('#billPaymentForm input[name="_token"]').value;
    const escapeText = value => String(value ?? '');
    const fill = (select, rows, valueKeys, labelKeys, placeholder) => {
        select.innerHTML = `<option value="">${placeholder}</option>`;
        rows.forEach(row => {
            const option = document.createElement('option');
            const value = valueKeys.map(key => row[key]).find(candidate => candidate !== undefined && candidate !== null && candidate !== '');
            const label = labelKeys.map(key => row[key]).find(candidate => candidate !== undefined && candidate !== null && candidate !== '');
            option.value = escapeText(value);
            option.textContent = escapeText(label || value);
            option.dataset.payload = JSON.stringify(row);
            select.appendChild(option);
        });
        select.disabled = false;
    };
    const fetchJson = async url => { const response=await fetch(url,{headers:{Accept:'application/json'}}); const json=await response.json(); if(!response.ok) throw new Error(json.message || 'Provider request failed.'); return json.data || []; };
    const setError = message => { result.className='form-text text-danger'; result.textContent=message; };
    fetchJson(@json(route('bill-payments.categories'))).then(rows => fill(category, rows, ['code', 'id'], ['name', 'label', 'code'], 'Select category')).catch(error => setError(error.message));
    category.addEventListener('change', async () => { biller.disabled=true; item.disabled=true; submitButton.disabled=true; try { const rows=await fetchJson(@json(route('bill-payments.billers'))+'?category='+encodeURIComponent(category.value)); fill(biller, Array.isArray(rows)?rows:(rows.billers||[]), ['biller_code', 'code', 'id'], ['name', 'biller_name', 'code'], 'Select biller'); } catch(error){setError(error.message);} });
    biller.addEventListener('change', async () => { item.disabled=true; submitButton.disabled=true; try { const rows=await fetchJson(@json(route('bill-payments.items'))+'?biller_code='+encodeURIComponent(biller.value)); fill(item, Array.isArray(rows)?rows:(rows.items||[]), ['item_code', 'code', 'id'], ['name', 'short_name', 'code'], 'Select product'); } catch(error){setError(error.message);} });
    item.addEventListener('change', () => { const selected=item.options[item.selectedIndex]; const row=selected?.dataset?.payload?JSON.parse(selected.dataset.payload):{}; productName.value=row.name||row.short_name||selected?.textContent||''; document.getElementById('customerIdentifierLabel').textContent=(row.label_name||'Customer Identifier')+' *'; if(Number(row.amount)>0){amount.value=Number(row.amount).toFixed(2);amount.readOnly=true;}else{amount.readOnly=false;} validateButton.disabled=!item.value||!customer.value; submitButton.disabled=true; result.textContent=''; });
    customer.addEventListener('input',()=>{validateButton.disabled=!item.value||!customer.value;submitButton.disabled=true;});
    validateButton.addEventListener('click', async () => { validateButton.disabled=true; result.className='form-text text-muted';result.textContent='Validating...'; try { const response=await fetch(@json(route('bill-payments.validate-customer')),{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({item_code:item.value,customer_identifier:customer.value})}); const json=await response.json(); if(!response.ok)throw new Error(json.message||'Validation failed.'); result.className='form-text text-success';result.textContent='Validated: '+(json.data.name||json.data.response_message||customer.value);submitButton.disabled=false; }catch(error){setError(error.message);}finally{validateButton.disabled=false;} });
});
</script>
@endpush
