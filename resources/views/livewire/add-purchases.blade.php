<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Purchase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Create Purchase</h1>
            <a href="{{ route('purchases.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('purchases.store') }}" method="POST" enctype="multipart/form-data" id="purchaseForm">
            @csrf
            
            <!-- Purchase Details Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card p-3">
                        <h5 class="card-title">Purchase Details</h5>
                        <div class="row g-3">
                            <!-- Purchase ID (Auto-generated or manual) -->
                            <div class="col-md-4">
                                <label for="purchase_id" class="form-label">Purchase ID</label>
                                <input type="text" id="purchase_id" name="purchase_id" 
                                       value="{{ old('purchase_id', 'PUR-' . strtoupper(uniqid())) }}" 
                                       class="form-control" readonly>
                            </div>

                            <!-- Select Supplier -->
                            <div class="col-md-4">
                                <label for="vendor_id" class="form-label">Select Supplier *</label>
                                <div class="input-group">
                                    <select id="vendor_id" name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required>
                                        <option value="">Choose Supplier</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" 
                                                {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <a class="btn btn-primary" href="{{ route('vendors.create') }}" title="Add Supplier">
                                        <i class="fas fa-plus-circle"></i>
                                    </a>
                                </div>
                                @error('vendor_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Purchase Date -->
                            <div class="col-md-4">
                                <label for="purchase_date" class="form-label">Purchase Date *</label>
                                <input type="date" id="purchase_date" name="purchase_date" 
                                       value="{{ old('purchase_date', date('Y-m-d')) }}" 
                                       class="form-control @error('purchase_date') is-invalid @enderror" required>
                                @error('purchase_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Due Date -->
                            <div class="col-md-4">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" id="due_date" name="due_date" 
                                       value="{{ old('due_date') }}" 
                                       class="form-control @error('due_date') is-invalid @enderror">
                                @error('due_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Reference No -->
                            <div class="col-md-4">
                                <label for="reference_no" class="form-label">Reference No</label>
                                <input type="text" id="reference_no" name="reference_no" 
                                       value="{{ old('reference_no') }}" 
                                       class="form-control @error('reference_no') is-invalid @enderror" 
                                       placeholder="Enter Reference Number">
                                @error('reference_no')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Supplier Invoice Serial No -->
                            <div class="col-md-4">
                                <label for="invoice_serial_no" class="form-label">Supplier Invoice Serial No</label>
                                <input type="text" id="invoice_serial_no" name="invoice_serial_no" 
                                       value="{{ old('invoice_serial_no') }}" 
                                       class="form-control @error('invoice_serial_no') is-invalid @enderror" 
                                       placeholder="Enter Serial No">
                                @error('invoice_serial_no')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Selection & Table -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Products</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="addProductBtn">
                                <i class="fas fa-plus"></i> Add Product
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="productsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product / Service *</th>
                                            <th>Quantity *</th>
                                            <th>Unit</th>
                                            <th>Rate *</th>
                                            <th>Discount</th>
                                            <th>Tax</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Discount & Tax -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card p-3">
                        <h5 class="card-title">Discount & Tax</h5>
                        <div class="row g-3 align-items-center">
                            <!-- Discount Type -->
                            <div class="col-md-4">
                                <label for="discount_type" class="form-label">Discount Type</label>
                                <select id="discount_type" name="discount_type" class="form-select">
                                    <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage(%)</option>
                                    <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>Fixed</option>
                                </select>
                            </div>

                            <!-- Discount Value -->
                            <div class="col-md-4">
                                <label for="discount_value" class="form-label">Discount</label>
                                <input type="number" id="discount_value" name="discount_value" 
                                       value="{{ old('discount_value', 0) }}" 
                                       class="form-control" step="0.01" min="0">
                            </div>

                            <!-- Tax Selection -->
                            <div class="col-md-4">
                                <label for="tax_id" class="form-label">Tax</label>
                                <select id="tax_id" name="tax_id" class="form-select">
                                    <option value="">Select Tax</option>
                                    @foreach($taxOptions as $tax)
                                        <option value="{{ $tax->id }}" 
                                            {{ old('tax_id') == $tax->id ? 'selected' : '' }}>
                                            {{ $tax->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Details & Totals -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card p-3">
                        <div class="row g-3">
                            <!-- Bank & Notes -->
                            <div class="col-md-6">
                                <h5>Bank Details</h5>
                                
                                <!-- Select Bank -->
                                <div class="mb-3">
                                    <label for="bank_id" class="form-label">Select Bank</label>
                                    <div class="input-group">
                                        <select id="bank_id" name="bank_id" class="form-select">
                                            <option value="">Select Bank</option>
                                            @foreach($banks as $bank)
                                                <option value="{{ $bank->id }}" 
                                                    {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                                                    {{ $bank->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <a class="btn btn-primary" href="{{ route('bank-account') }}" title="Add Bank">
                                            <i class="fas fa-plus-circle"></i>
                                        </a>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="3" 
                                              placeholder="Enter Notes">{{ old('notes') }}</textarea>
                                </div>

                                <!-- Terms & Conditions -->
                                <div class="mb-3">
                                    <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                    <textarea id="terms_conditions" name="terms_conditions" class="form-control" rows="3" 
                                              placeholder="Enter Terms and Conditions">{{ old('terms_conditions') }}</textarea>
                                </div>
                            </div>

                            <!-- Totals & Signature -->
                            <div class="col-md-6">
                                <h5>Totals & Signature</h5>
                                <div class="mb-3">
                                    <p>Taxable Amount: <strong id="taxableAmount">0.00</strong></p>
                                    <p>Discount: <strong id="totalDiscount">0.00</strong></p>
                                    <p>VAT: <strong id="vatAmount">0.00</strong></p>
                                    <div class="form-check form-switch align-items-center mb-2">
                                        <input class="form-check-input" type="checkbox" id="round_off" name="round_off" 
                                               {{ old('round_off') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="round_off">Round Off</label>
                                        <span class="ms-2" id="roundOffAmount">0.00</span>
                                    </div>
                                    <hr>
                                    <h4>Total Amount: <span id="totalAmount">0.00</span></h4>
                                </div>

                                <!-- Signature Name -->
                                <div class="mb-3">
                                    <label for="signature_name" class="form-label">Signature Name</label>
                                    <input type="text" id="signature_name" name="signature_name" 
                                           value="{{ old('signature_name') }}" 
                                           class="form-control" placeholder="Enter Signature Name">
                                </div>

                                <!-- Signature Upload -->
                                <div class="mb-3">
                                    <label for="signature_image" class="form-label">Upload Signature</label>
                                    <input type="file" id="signature_image" name="signature_image" 
                                           class="form-control" accept="image/*">
                                    @error('signature_image')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden fields for products -->
            <div id="productsData"></div>

            <!-- Submit Buttons -->
            <div class="row">
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary">Save Purchase</button>
                </div>
            </div>
        </form>
    </div>

    <!-- JavaScript for dynamic product handling -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const products = @json($products);
            let productCounter = 0;

            const tableBody = document.getElementById('productsTableBody');
            const addBtn = document.getElementById('addProductBtn');

            function buildProductOptions() {
                const options = ['<option value="">Select Product</option>'];
                products.forEach((product) => {
                    const unit = product.unit ?? 'pcs';
                    const price = product.price ?? 0;
                    options.push(
                        `<option value="${product.id}" data-name="${escapeHtml(product.name)}" data-unit="${escapeHtml(unit)}" data-price="${price}">${escapeHtml(product.name)} - $${Number(price).toFixed(2)}</option>`
                    );
                });
                return options.join('');
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function createEmptyRow() {
                const rowId = `productRow_${productCounter}`;
                const rowIndex = productCounter;
                const row = document.createElement('tr');
                row.id = rowId;
                row.innerHTML = `
                    <td>
                        <select name="products[${rowIndex}][product_id]" class="form-select product-select" data-row="${rowIndex}" required>
                            ${buildProductOptions()}
                        </select>
                    </td>
                    <td>
                        <input type="number" name="products[${rowIndex}][quantity]" value="1" class="form-control quantity-input" min="1"
                               data-row="${rowIndex}" onchange="updateProductAmount(${rowIndex})">
                    </td>
                    <td>
                        <input type="text" name="products[${rowIndex}][unit]" value="" class="form-control unit-input" data-row="${rowIndex}">
                    </td>
                    <td>
                        <input type="number" name="products[${rowIndex}][rate]" value="0" class="form-control rate-input" step="0.01" min="0"
                               data-row="${rowIndex}" onchange="updateProductAmount(${rowIndex})">
                    </td>
                    <td>
                        <input type="number" name="products[${rowIndex}][discount]" value="0" class="form-control discount-input" step="0.01" min="0"
                               data-row="${rowIndex}" onchange="updateProductAmount(${rowIndex})">
                    </td>
                    <td>
                        <select name="products[${rowIndex}][tax_id]" class="form-select tax-select" data-row="${rowIndex}" onchange="updateProductAmount(${rowIndex})">
                            <option value="">No Tax</option>
                            @foreach($taxOptions as $tax)
                                <option value="{{ $tax->id }}">{{ $tax->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="amount-cell" id="amount_${rowIndex}">0.00</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeProductRow('${rowId}')">Remove</button>
                    </td>
                `;

                tableBody.appendChild(row);
                productCounter += 1;
                bindRowEvents(row);
                return row;
            }

            function bindRowEvents(row) {
                const productSelect = row.querySelector('.product-select');
                if (!productSelect) {
                    return;
                }

                productSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const rowIndex = this.getAttribute('data-row');
                    const nameField = document.querySelector(`input[name="products[${rowIndex}][product_name]"]`);
                    const unitInput = row.querySelector('.unit-input');
                    const rateInput = row.querySelector('.rate-input');

                    const unit = selectedOption.getAttribute('data-unit') || '';
                    const price = selectedOption.getAttribute('data-price') || 0;
                    const productName = selectedOption.getAttribute('data-name') || '';

                    if (!nameField && productName) {
                        const hiddenName = document.createElement('input');
                        hiddenName.type = 'hidden';
                        hiddenName.name = `products[${rowIndex}][product_name]`;
                        hiddenName.value = productName;
                        row.querySelector('td').appendChild(hiddenName);
                    } else if (nameField) {
                        nameField.value = productName;
                    }

                    if (unitInput) {
                        unitInput.value = unit;
                    }
                    if (rateInput && Number(rateInput.value || 0) <= 0) {
                        rateInput.value = price;
                    }

                    updateProductAmount(rowIndex);

                    if (isLastRowFilled()) {
                        createEmptyRow();
                    }
                });
            }

            function isLastRowFilled() {
                const rows = Array.from(tableBody.querySelectorAll('tr'));
                if (rows.length === 0) {
                    return false;
                }
                const lastRow = rows[rows.length - 1];
                const productSelect = lastRow.querySelector('.product-select');
                return productSelect && productSelect.value;
            }

            addBtn.addEventListener('click', function() {
                createEmptyRow();
            });

            createEmptyRow();
            
            // Global functions for inline event handlers
            window.updateProductAmount = function(rowIndex) {
                const quantity = parseFloat(document.querySelector(`input[name="products[${rowIndex}][quantity]"]`).value) || 0;
                const rate = parseFloat(document.querySelector(`input[name="products[${rowIndex}][rate]"]`).value) || 0;
                const discount = parseFloat(document.querySelector(`input[name="products[${rowIndex}][discount]"]`).value) || 0;
                
                const amount = (quantity * rate) - discount;
                document.getElementById(`amount_${rowIndex}`).textContent = amount.toFixed(2);
                
                updateTotals();
            };
            
            window.removeProductRow = function(rowId) {
                const row = document.getElementById(rowId);
                if (row) {
                    row.remove();
                }

                if (tableBody.children.length === 0) {
                    createEmptyRow();
                }
                
                updateTotals();
            };
            
            function updateTotals() {
                let taxableAmount = 0;
                let totalDiscount = 0;
                
                // Calculate from visible rows
                const quantityInputs = document.querySelectorAll('.quantity-input');
                const rateInputs = document.querySelectorAll('.rate-input');
                const discountInputs = document.querySelectorAll('.discount-input');
                
                for (let i = 0; i < quantityInputs.length; i++) {
                    const quantity = parseFloat(quantityInputs[i].value) || 0;
                    const rate = parseFloat(rateInputs[i].value) || 0;
                    const discount = parseFloat(discountInputs[i].value) || 0;
                    
                    taxableAmount += quantity * rate;
                    totalDiscount += discount;
                }
                
                // Apply global discount
                const discountType = document.getElementById('discount_type').value;
                const discountValue = parseFloat(document.getElementById('discount_value').value) || 0;
                let globalDiscount = 0;
                
                if (discountType === 'percentage') {
                    globalDiscount = (taxableAmount * discountValue) / 100;
                } else {
                    globalDiscount = discountValue;
                }
                
                // Calculate tax
                const taxId = document.getElementById('tax_id').value;
                let taxRate = 0;
                @foreach($taxOptions as $tax)
                    if ("{{ $tax->id }}" === taxId) {
                        taxRate = {{ $tax->rate ?? 0 }};
                    }
                @endforeach
                
                const vatAmount = ((taxableAmount - totalDiscount - globalDiscount) * taxRate) / 100;
                
                // Calculate totals
                const subtotal = taxableAmount - totalDiscount - globalDiscount;
                const roundOff = document.getElementById('round_off').checked;
                let roundOffAmount = 0;
                let totalAmount = subtotal + vatAmount;
                
                if (roundOff) {
                    totalAmount = Math.round(totalAmount);
                    roundOffAmount = totalAmount - (subtotal + vatAmount);
                }
                
                // Update UI
                document.getElementById('taxableAmount').textContent = taxableAmount.toFixed(2);
                document.getElementById('totalDiscount').textContent = (totalDiscount + globalDiscount).toFixed(2);
                document.getElementById('vatAmount').textContent = vatAmount.toFixed(2);
                document.getElementById('roundOffAmount').textContent = roundOffAmount.toFixed(2);
                document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
            }
            
            // Event listeners for dynamic updates
            document.getElementById('discount_type').addEventListener('change', updateTotals);
            document.getElementById('discount_value').addEventListener('input', updateTotals);
            document.getElementById('tax_id').addEventListener('change', updateTotals);
            document.getElementById('round_off').addEventListener('change', updateTotals);
            
            // Form validation
            document.getElementById('purchaseForm').addEventListener('submit', function(e) {
                const vendorId = document.getElementById('vendor_id').value;
                const purchaseDate = document.getElementById('purchase_date').value;
                const productRows = document.querySelectorAll('#productsTableBody tr:not(#noProductsRow)');
                
                if (!vendorId) {
                    e.preventDefault();
                    alert('Please select a vendor');
                    return;
                }
                
                if (!purchaseDate) {
                    e.preventDefault();
                    alert('Please select a purchase date');
                    return;
                }
                
                if (productRows.length === 0) {
                    e.preventDefault();
                    alert('Please add at least one product');
                    return;
                }
            });
        });
    </script>
</body>
</html>
