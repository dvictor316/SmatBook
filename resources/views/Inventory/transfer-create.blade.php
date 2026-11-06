<?php $page = 'transfer-create'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @php($transferErrors = isset($errors) ? $errors : session('errors'))
        @component('components.page-header')
            @slot('title')
                New stock transfer
            @endslot
        @endcomponent

        @if($transferErrors && $transferErrors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Review the transfer details:</div>
                <ul class="mb-0">
                    @foreach($transferErrors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('inventory.transfer.store') }}" class="card border-0 shadow-sm">
            @csrf
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label" for="transfer-date">Transfer date</label>
                        <input id="transfer-date" type="datetime-local" name="transfer_date" value="{{ $defaultTransferDate }}" class="form-control" required>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label" for="transfer-reference">Reference <span class="text-muted">(optional)</span></label>
                        <input id="transfer-reference" type="text" name="reference" value="{{ old('reference') }}" class="form-control" maxlength="80" placeholder="Auto-generated if blank">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label" for="transfer-source">From branch</label>
                        <select id="transfer-source" name="from_branch_id" class="form-select" required>
                            <option value="">Select source</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch['id'] }}" @selected($sourceBranchId === (string) $branch['id'])>{{ $branch['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label" for="transfer-destination">To branch</label>
                        <select id="transfer-destination" name="to_branch_id" class="form-select" required>
                            <option value="">Select destination</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch['id'] }}" @selected($destinationBranchId === (string) $branch['id'])>{{ $branch['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="transfer-notes">Notes <span class="text-muted">(optional)</span></label>
                        <textarea id="transfer-notes" name="notes" class="form-control" rows="2" maxlength="2000" placeholder="Add a reason or handling note">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
                    <div>
                        <h5 class="mb-1">Products and quantities</h5>
                        <div class="text-muted small">Stock moves immediately when you post. The total business stock does not change.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <input id="transfer-product-search" type="search" class="form-control" style="min-width:250px" placeholder="Search product name or SKU" autocomplete="off">
                        <button id="transfer-add-product" class="btn btn-outline-primary" type="button">Add product</button>
                    </div>
                </div>
                <div id="transfer-search-results" class="list-group mb-3" role="listbox" aria-label="Product search results"></div>
                <div id="transfer-product-error" class="text-danger small mb-3" role="status" aria-live="polite"></div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width:220px">Quantity to move</th>
                                <th style="width:90px" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="transfer-items">
                            @foreach(old('items', []) as $index => $line)
                                @php($product = $selectedProducts->get((int) ($line['product_id'] ?? 0)))
                                @if($product)
                                    <tr data-product-id="{{ $product->id }}">
                                        <td>
                                            <div class="fw-semibold">{{ $product->name }}</div>
                                            <small class="text-muted">{{ $product->sku ?: 'No SKU' }}</small>
                                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $product->id }}">
                                        </td>
                                        <td><input type="number" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" class="form-control" min="0.01" step="0.01" required></td>
                                        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger transfer-remove-item">Remove</button></td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="transfer-empty-state" class="text-center text-muted border rounded py-4 {{ count(old('items', [])) ? 'd-none' : '' }}">
                    Search for a product and add it to this transfer.
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between flex-wrap gap-2">
                <a href="{{ route('inventory.transfer-audit') }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary" id="transfer-submit">Post transfer</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('transfer-product-search');
    const searchButton = document.getElementById('transfer-add-product');
    const results = document.getElementById('transfer-search-results');
    const items = document.getElementById('transfer-items');
    const emptyState = document.getElementById('transfer-empty-state');
    const error = document.getElementById('transfer-product-error');
    const source = document.getElementById('transfer-source');
    const endpoint = @json($transferProductsSearchUrl);
    let nextIndex = {{ count(old('items', [])) }};
    let searchTimer;

    const refreshEmptyState = () => {
        emptyState.classList.toggle('d-none', items.children.length > 0);
    };

    const addProduct = (product) => {
        if (items.querySelector(`[data-product-id="${Number(product.id)}"]`)) {
            error.textContent = 'This product is already in the transfer.';
            return;
        }
        error.textContent = '';
        const row = document.createElement('tr');
        row.dataset.productId = String(product.id);
        const nameCell = document.createElement('td');
        const name = document.createElement('div');
        name.className = 'fw-semibold';
        name.textContent = product.name;
        const sku = document.createElement('small');
        sku.className = 'text-muted';
        sku.textContent = product.sku || 'No SKU';
        const productId = document.createElement('input');
        productId.type = 'hidden';
        productId.name = `items[${nextIndex}][product_id]`;
        productId.value = product.id;
        nameCell.append(name, sku, productId);

        const quantityCell = document.createElement('td');
        const quantity = document.createElement('input');
        quantity.type = 'number';
        quantity.name = `items[${nextIndex}][quantity]`;
        quantity.className = 'form-control';
        quantity.min = '0.01';
        quantity.step = '0.01';
        quantity.required = true;
        quantity.placeholder = 'Enter quantity';
        quantityCell.append(quantity);

        const actionCell = document.createElement('td');
        actionCell.className = 'text-end';
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-danger transfer-remove-item';
        remove.textContent = 'Remove';
        actionCell.append(remove);
        row.append(nameCell, quantityCell, actionCell);
        items.append(row);
        nextIndex++;
        refreshEmptyState();
    };

    const searchProducts = async () => {
        const query = searchInput.value.trim();
        const params = new URLSearchParams({q: query});
        if (source.value) params.set('branch_id', source.value);
        searchButton.disabled = true;
        error.textContent = '';
        try {
            const response = await fetch(`${endpoint}?${params.toString()}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Product search could not be completed.');
            const payload = await response.json();
            results.replaceChildren();
            for (const product of (payload.data || [])) {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                const label = document.createElement('span');
                label.textContent = `${product.name} · ${product.sku || 'No SKU'}`;
                const available = document.createElement('small');
                available.className = 'text-muted ms-3';
                available.textContent = product.available_stock === undefined ? '' : `Available: ${product.available_stock}`;
                option.append(label, available);
                option.addEventListener('click', () => addProduct(product));
                results.append(option);
            }
            if (!payload.data?.length) {
                const message = document.createElement('div');
                message.className = 'list-group-item text-muted';
                message.textContent = 'No matching products found.';
                results.append(message);
            }
        } catch (requestError) {
            error.textContent = requestError.message || 'Product search could not be completed.';
        } finally {
            searchButton.disabled = false;
        }
    };

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(searchProducts, 250);
    });
    searchButton.addEventListener('click', searchProducts);
    source.addEventListener('change', () => {
        if (searchInput.value.trim() !== '') searchProducts();
    });
    items.addEventListener('click', (event) => {
        const button = event.target.closest('.transfer-remove-item');
        if (button) {
            button.closest('tr').remove();
            refreshEmptyState();
        }
    });
    refreshEmptyState();
});
</script>
@endsection
