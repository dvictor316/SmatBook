@php
    $isEdit = isset($estimate);
    $currencySymbol = config('app.currency_symbol', 'NGN');
    $statusOptions = ['Draft', 'Sent', 'Accepted', 'Declined', 'Expired'];
    $selectedPriceListId = old('price_list_id', $estimate->price_list_id ?? optional(($priceLists ?? collect())->firstWhere('is_default', true))->id);
    $issueDate = old('issue_date', $isEdit ? optional($estimate->issue_date)->format('Y-m-d') : now()->format('Y-m-d'));
    $expiryDate = old('expiry_date', $isEdit ? optional($estimate->expiry_date)->format('Y-m-d') : now()->addDays(14)->format('Y-m-d'));
    $statusValue = old('status', $estimate->status ?? 'Draft');
    $items = collect($estimateItems ?? [])->values();
    $productData = collect($products ?? [])->map(fn ($product) => [
        'id' => (int) $product->id,
        'name' => (string) ($product->name ?? ''),
        'sku' => (string) ($product->sku ?? ''),
        'retail' => (float) ($product->retail_price ?? $product->price ?? 0),
        'wholesale' => (float) ($product->wholesale_price ?? 0),
        'special' => (float) ($product->special_price ?? 0),
    ])->values();
@endphp

<style>
    .estimate-workspace { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 18px; }
    .estimate-panel { background: #fff; border: 1px solid #e5edf7; border-radius: 12px; box-shadow: 0 10px 26px rgba(15, 23, 42, .05); overflow: hidden; }
    .estimate-panel-head { padding: 16px 18px; border-bottom: 1px solid #edf2f7; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); }
    .estimate-panel-body { padding: 18px; }
    .estimate-items-table th { font-size: 12px; color: #475569; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; }
    .estimate-items-table td { min-width: 130px; vertical-align: middle; }
    .estimate-items-table td.product-cell { min-width: 240px; }
    .estimate-items-table td.amount-cell { min-width: 110px; }
    .estimate-total-row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid #eef2f7; }
    .estimate-total-row.total { border-bottom: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
    .price-list-note { background: #eef8ff; color: #075985; border: 1px solid #bae6fd; border-radius: 10px; padding: 10px 12px; font-size: 12px; }
    @media (max-width: 1100px) { .estimate-workspace { grid-template-columns: 1fr; } }
</style>

<form action="{{ $action }}" method="POST">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="estimate-workspace">
        <div class="estimate-panel">
            <div class="estimate-panel-head">
                <h5 class="mb-1">{{ $isEdit ? 'Edit Estimate' : 'Create Estimate' }}</h5>
                <div class="text-muted small">Use product rows, price tiers, or configured price lists for customer quotes.</div>
            </div>
            <div class="estimate-panel-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Estimate Number</label>
                        <input type="text" name="estimate_number" class="form-control" value="{{ old('estimate_number', $estimate->estimate_number ?? '') }}" required>
                        @error('estimate_number') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" id="estimate-customer" class="form-select" required>
                            <option value="">Select customer</option>
                            @foreach($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}"
                                    data-price-list-id="{{ $customer->price_list_id ?? '' }}"
                                    @selected(old('customer_id', $estimate->customer_id ?? null) == $customer->id)>
                                    {{ $customer->customer_name ?? $customer->name ?? ('Customer #' . $customer->id) }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Price List</label>
                        <select name="price_list_id" id="estimate-price-list" class="form-select">
                            <option value="">Retail / Product prices</option>
                            @foreach($priceLists ?? [] as $priceList)
                                <option value="{{ $priceList->id }}" @selected((string) $selectedPriceListId === (string) $priceList->id)>
                                    {{ $priceList->name }}{{ $priceList->currency ? ' - ' . $priceList->currency : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-muted small mt-1">Price lists are used here and on POS for live product rates.</div>
                        @error('price_list_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Issue Date</label>
                        <input type="date" name="issue_date" class="form-control" value="{{ $issueDate }}" required>
                        @error('issue_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" value="{{ $expiryDate }}" required>
                        @error('expiry_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}" @selected($statusValue === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="price-list-note mb-3">
                    Product rows default to the selected price list when one is available. If a product is not on that list, the row falls back to retail, wholesale, or special pricing.
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle estimate-items-table" id="estimate-items-table">
                        <thead class="table-light">
                            <tr>
                                <th>Product / Service</th>
                                <th>Price Source</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Discount</th>
                                <th>Tax</th>
                                <th>Amount</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                <tr>
                                    <td class="product-cell">
                                        <select name="items[{{ $index }}][product_id]" class="form-select estimate-product">
                                            <option value="">Custom item</option>
                                            @foreach($products ?? [] as $product)
                                                <option value="{{ $product->id }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>
                                                    {{ $product->name }}{{ $product->sku ? ' (' . $product->sku . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="items[{{ $index }}][name]" class="form-control mt-2 estimate-item-name" placeholder="Description" value="{{ $item['name'] ?? '' }}">
                                    </td>
                                    <td>
                                        <select name="items[{{ $index }}][price_source]" class="form-select estimate-price-source">
                                            <option value="list" @selected(($item['price_source'] ?? 'list') === 'list')>Selected price list</option>
                                            <option value="retail" @selected(($item['price_source'] ?? '') === 'retail')>Retail</option>
                                            <option value="wholesale" @selected(($item['price_source'] ?? '') === 'wholesale')>Wholesale</option>
                                            <option value="special" @selected(($item['price_source'] ?? '') === 'special')>Special</option>
                                        </select>
                                        <input type="hidden" name="items[{{ $index }}][price_list_id]" class="estimate-row-price-list-id" value="{{ $item['price_list_id'] ?? $selectedPriceListId }}">
                                    </td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][quantity]" class="form-control estimate-qty" value="{{ $item['quantity'] ?? 1 }}"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][rate]" class="form-control estimate-rate" value="{{ $item['rate'] ?? 0 }}"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][discount]" class="form-control estimate-discount" value="{{ $item['discount'] ?? 0 }}"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][tax]" class="form-control estimate-tax" value="{{ $item['tax'] ?? 0 }}"></td>
                                    <td class="amount-cell"><input type="number" step="0.01" min="0" name="items[{{ $index }}][amount]" class="form-control estimate-amount bg-light fw-semibold" value="{{ $item['amount'] ?? 0 }}" readonly></td>
                                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger estimate-remove-row">Remove</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" id="estimate-add-row">
                    <i class="fa-solid fa-plus me-1"></i>Add Line
                </button>

                <input type="hidden" name="subtotal" id="estimate-subtotal-input" value="{{ old('subtotal', $estimate->subtotal ?? 0) }}">
                <input type="hidden" name="tax" id="estimate-tax-input" value="{{ old('tax', $estimate->tax ?? 0) }}">
                <input type="hidden" name="discount" id="estimate-discount-input" value="{{ old('discount', $estimate->discount ?? 0) }}">
                <input type="hidden" name="total_amount" id="estimate-total-input" value="{{ old('total_amount', $estimate->total_amount ?? 0) }}">

                <div class="mt-4">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" class="form-control" placeholder="Add terms or internal notes...">{{ old('notes', $estimate->notes ?? '') }}</textarea>
                    @error('notes') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="estimate-panel-body border-top d-flex justify-content-end gap-2">
                <a href="{{ route('estimates.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-2"></i>{{ $isEdit ? 'Save Changes' : 'Save Estimate' }}
                </button>
            </div>
        </div>

        <aside class="estimate-panel">
            <div class="estimate-panel-head">
                <h6 class="mb-1">Estimate Totals</h6>
                <div class="text-muted small">Calculated from the item rows.</div>
            </div>
            <div class="estimate-panel-body">
                <div class="estimate-total-row"><span class="text-muted">Subtotal</span><strong><span>{{ $currencySymbol }}</span><span id="estimate-subtotal-display">0.00</span></strong></div>
                <div class="estimate-total-row"><span class="text-muted">Discount</span><strong>-<span>{{ $currencySymbol }}</span><span id="estimate-discount-display">0.00</span></strong></div>
                <div class="estimate-total-row"><span class="text-muted">Tax</span><strong><span>{{ $currencySymbol }}</span><span id="estimate-tax-display">0.00</span></strong></div>
                <div class="estimate-total-row total"><span>Total</span><span>{{ $currencySymbol }}<span id="estimate-total-display">0.00</span></span></div>
            </div>
        </aside>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = @json($productData);
    const priceLists = @json($priceListData ?? []);
    const productById = new Map(products.map(product => [String(product.id), product]));
    const priceListById = new Map(priceLists.map(list => [String(list.id), list]));
    const tableBody = document.querySelector('#estimate-items-table tbody');
    const priceListSelect = document.getElementById('estimate-price-list');
    const customerSelect = document.getElementById('estimate-customer');
    let rowIndex = tableBody.querySelectorAll('tr').length;

    const toNumber = (value) => {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const money = (value) => Math.max(0, toNumber(value)).toFixed(2);

    function productOptions() {
        return ['<option value="">Custom item</option>']
            .concat(products.map(product => `<option value="${product.id}">${product.name}${product.sku ? ' (' + product.sku + ')' : ''}</option>`))
            .join('');
    }

    function listPriceForProduct(listId, productId, quantity) {
        const list = priceListById.get(String(listId));
        if (!list || !list.items || !list.items[String(productId)]) {
            return null;
        }

        const rows = list.items[String(productId)];
        let match = rows[0] || null;
        rows.forEach(row => {
            if (quantity >= toNumber(row.min_quantity)) {
                match = row;
            }
        });

        return match ? toNumber(match.price) : null;
    }

    function resolveRate(row) {
        const productId = row.querySelector('.estimate-product')?.value || '';
        const product = productById.get(String(productId));
        if (!product) {
            return;
        }

        const quantity = toNumber(row.querySelector('.estimate-qty')?.value || 1);
        const source = row.querySelector('.estimate-price-source')?.value || 'list';
        const selectedListId = priceListSelect.value || '';
        row.querySelector('.estimate-row-price-list-id').value = selectedListId;

        let rate = null;
        if (source === 'list' && selectedListId) {
            rate = listPriceForProduct(selectedListId, productId, quantity);
        }
        if (rate === null && source === 'wholesale') {
            rate = toNumber(product.wholesale) || toNumber(product.retail);
        }
        if (rate === null && source === 'special') {
            rate = toNumber(product.special) || toNumber(product.retail);
        }
        if (rate === null) {
            rate = toNumber(product.retail);
        }

        row.querySelector('.estimate-rate').value = money(rate);
        const nameInput = row.querySelector('.estimate-item-name');
        if (nameInput && !nameInput.value) {
            nameInput.value = product.name;
        }
    }

    function recalculateRow(row) {
        const quantity = toNumber(row.querySelector('.estimate-qty')?.value);
        const rate = toNumber(row.querySelector('.estimate-rate')?.value);
        const discount = toNumber(row.querySelector('.estimate-discount')?.value);
        const tax = toNumber(row.querySelector('.estimate-tax')?.value);
        const amount = Math.max(0, (quantity * rate) - discount + tax);
        row.querySelector('.estimate-amount').value = amount.toFixed(2);
    }

    function recalculateTotals() {
        let subtotal = 0;
        let discount = 0;
        let tax = 0;
        let total = 0;

        tableBody.querySelectorAll('tr').forEach(row => {
            const quantity = toNumber(row.querySelector('.estimate-qty')?.value);
            const rate = toNumber(row.querySelector('.estimate-rate')?.value);
            subtotal += quantity * rate;
            discount += toNumber(row.querySelector('.estimate-discount')?.value);
            tax += toNumber(row.querySelector('.estimate-tax')?.value);
            total += toNumber(row.querySelector('.estimate-amount')?.value);
        });

        document.getElementById('estimate-subtotal-input').value = subtotal.toFixed(2);
        document.getElementById('estimate-discount-input').value = discount.toFixed(2);
        document.getElementById('estimate-tax-input').value = tax.toFixed(2);
        document.getElementById('estimate-total-input').value = total.toFixed(2);
        document.getElementById('estimate-subtotal-display').textContent = subtotal.toFixed(2);
        document.getElementById('estimate-discount-display').textContent = discount.toFixed(2);
        document.getElementById('estimate-tax-display').textContent = tax.toFixed(2);
        document.getElementById('estimate-total-display').textContent = total.toFixed(2);
    }

    function refreshRow(row, shouldResolveRate = false) {
        if (shouldResolveRate) {
            resolveRate(row);
        }
        recalculateRow(row);
        recalculateTotals();
    }

    function bindInitialRows() {
        tableBody.querySelectorAll('tr').forEach(row => refreshRow(row, false));
    }

    document.getElementById('estimate-add-row').addEventListener('click', function () {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="product-cell">
                <select name="items[${rowIndex}][product_id]" class="form-select estimate-product">${productOptions()}</select>
                <input type="text" name="items[${rowIndex}][name]" class="form-control mt-2 estimate-item-name" placeholder="Description">
            </td>
            <td>
                <select name="items[${rowIndex}][price_source]" class="form-select estimate-price-source">
                    <option value="list">Selected price list</option>
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                    <option value="special">Special</option>
                </select>
                <input type="hidden" name="items[${rowIndex}][price_list_id]" class="estimate-row-price-list-id" value="${priceListSelect.value || ''}">
            </td>
            <td><input type="number" step="0.01" min="0" name="items[${rowIndex}][quantity]" class="form-control estimate-qty" value="1"></td>
            <td><input type="number" step="0.01" min="0" name="items[${rowIndex}][rate]" class="form-control estimate-rate" value="0"></td>
            <td><input type="number" step="0.01" min="0" name="items[${rowIndex}][discount]" class="form-control estimate-discount" value="0"></td>
            <td><input type="number" step="0.01" min="0" name="items[${rowIndex}][tax]" class="form-control estimate-tax" value="0"></td>
            <td class="amount-cell"><input type="number" step="0.01" min="0" name="items[${rowIndex}][amount]" class="form-control estimate-amount bg-light fw-semibold" value="0" readonly></td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger estimate-remove-row">Remove</button></td>
        `;
        tableBody.appendChild(row);
        rowIndex++;
        recalculateTotals();
    });

    tableBody.addEventListener('change', function (event) {
        const row = event.target.closest('tr');
        if (!row) return;
        if (event.target.classList.contains('estimate-product') || event.target.classList.contains('estimate-price-source')) {
            refreshRow(row, true);
            return;
        }
        refreshRow(row, event.target.classList.contains('estimate-qty') && row.querySelector('.estimate-price-source')?.value === 'list');
    });

    tableBody.addEventListener('input', function (event) {
        const row = event.target.closest('tr');
        if (!row) return;
        if (event.target.classList.contains('estimate-qty') && row.querySelector('.estimate-price-source')?.value === 'list') {
            resolveRate(row);
        }
        recalculateRow(row);
        recalculateTotals();
    });

    tableBody.addEventListener('click', function (event) {
        if (!event.target.classList.contains('estimate-remove-row')) return;
        if (tableBody.querySelectorAll('tr').length <= 1) return;
        event.target.closest('tr').remove();
        recalculateTotals();
    });

    priceListSelect.addEventListener('change', function () {
        tableBody.querySelectorAll('tr').forEach(row => {
            if ((row.querySelector('.estimate-price-source')?.value || 'list') === 'list') {
                refreshRow(row, true);
            }
        });
    });

    customerSelect.addEventListener('change', function () {
        const selected = customerSelect.options[customerSelect.selectedIndex];
        const customerPriceListId = selected?.dataset?.priceListId || '';
        if (customerPriceListId && priceListById.has(String(customerPriceListId))) {
            priceListSelect.value = customerPriceListId;
            priceListSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    bindInitialRows();
});
</script>
@endpush
