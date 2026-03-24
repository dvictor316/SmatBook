

<?php $page = 'product-list'; ?>
@extends('layout.mainlayout')

@section('content')

<style>
    /* Hide default DataTables buttons as we trigger them via our custom dropdown */
    .dt-buttons { display: none !important; }

    .product-action-trigger {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.95rem;
        border: 1px solid rgba(13, 110, 253, 0.25);
        border-radius: 999px;
        background: #eef4ff;
        color: #0d4fd6;
        font-size: 0.875rem;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.12);
        transition: all 0.2s ease;
    }

    .product-action-trigger:hover,
    .product-action-trigger:focus {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
    }

    .product-action-menu {
        min-width: 11rem;
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
        overflow: hidden;
    }

    .product-action-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.8rem 1rem;
        font-weight: 600;
    }

    .product-action-menu .dropdown-item.text-danger {
        background: #fff7f7;
    }

    @media (max-width: 767.98px) {
        .product-action-trigger {
            width: 100%;
            justify-content: center;
            padding: 0.7rem 0.95rem;
        }
    }
    
    @media print {
        .no-print, .dt-buttons, .main-header, .sidebar { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .page-wrapper { margin: 0 !important; padding: 0 !important; }
        table { width: 100% !important; border-collapse: collapse; }
        th, td { border: 1px solid #dee2e6 !important; padding: 8px !important; }
    }
</style>

<div class="page-wrapper" id="main-content-wrapper">
    <div class="content container-fluid">

        {{-- INLINE HEADER & CONTROLS --}}
        <div class="card shadow-sm mb-3 no-print">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h4 class="mb-0 text-primary"><i class="fas fa-boxes me-2"></i>Inventory Management</h4>
                    </div>
                    <div class="col-md-8 text-end">
                        <div class="d-inline-flex gap-2">
                            <form method="GET" action="{{ route('product-list') }}" class="d-flex">
                                <div class="input-group">
                                    <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control" placeholder="Search SKU or Name...">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                                </div>
                            </form>
                            
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-download me-1"></i> Export
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" id="export_print"><i class="fas fa-print me-2 text-secondary"></i>Print</a></li>
                                    <li><a class="dropdown-item" href="#" id="export_excel"><i class="far fa-file-excel me-2 text-success"></i>Excel</a></li>
                                    <li><a class="dropdown-item" href="#" id="export_pdf"><i class="far fa-file-pdf me-2 text-danger"></i>PDF</a></li>
                                </ul>
                            </div>

                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-upload me-1"></i> Import
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('inventory.Products.import.template') }}"><i class="far fa-file-lines me-2 text-primary"></i>Download CSV Template</a></li>
                                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importProductsModal"><i class="fas fa-file-upload me-2 text-success"></i>Import Products</button></li>
                                </ul>
                            </div>

                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fa fa-plus"></i> Add Product
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="products-table">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Item / SKU</th>
                                <th>Category</th>
                                <th>Base Unit</th>
                                <th>Pkg (Ctn/Roll)</th>
                                <th>Stock</th>
                                <th>S. Price</th>
                                <th>P. Price</th>
                                <th class="text-center no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($products) && $products->count() > 0)
                                @foreach($products as $product)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ $product->image_url }}" class="rounded me-2" width="35" height="35" alt="{{ $product->name }}" onerror="this.onerror=null;this.src='{{ asset('assets/img/products/product-01.png') }}';">
                                                <div>
                                                    <div class="fw-bold text-dark">{{ $product->name }}</div>
                                                    <small class="text-muted">{{ $product->sku }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $product->category->name ?? 'N/A' }}</td>
                                        <td><span class="badge bg-soft-info text-info">{{ $product->base_unit_name }}</span></td>
                                        <td>
                                            <small class="text-nowrap">
                                                Ctn: <strong>{{ $product->units_per_carton }}</strong> | 
                                                Roll: <strong>{{ $product->units_per_roll }}</strong>
                                            </small>
                                        </td>
                                        <td>
                                            @php
                                                $displayStock = (float) ($product->active_branch_stock ?? $product->stock);
                                                $hasActiveBranch = !empty($activeBranch['name'] ?? null);
                                            @endphp
                                            <span class="badge {{ $displayStock <= 5 ? 'bg-danger' : 'bg-success' }}">
                                                {{ rtrim(rtrim(number_format($displayStock, 2), '0'), '.') }}
                                            </span>
                                            @if($hasActiveBranch)
                                                <div class="small text-muted mt-1">{{ $activeBranch['name'] }}</div>
                                            @endif
                                        </td>
                                        <td>{{ number_format($product->price, 2) }}</td>
                                        <td>{{ number_format($product->purchase_price, 2) }}</td>
                                        <td class="text-center no-print">
                                            <div class="dropdown">
                                                <a href="#" class="product-action-trigger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-bolt"></i>
                                                    <span>Manage</span>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end product-action-menu">
                                                    <a class="dropdown-item" href="{{ route('inventory.Products.edit', $product->id) }}"><i class="far fa-edit me-2"></i>Edit</a>
                                                    <form action="{{ route('inventory.Products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger"><i class="far fa-trash-alt me-2"></i>Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: ADD PRODUCT --}}
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.Products.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" placeholder="Leave blank to auto-generate">
                            <small class="text-muted">If the product does not come with a code, the system will generate a unique SKU.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" class="form-control" placeholder="Scan or type barcode">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <div class="input-group">
                                <select name="category_id" id="product_category_select" class="form-select" required>
                                    @foreach($categories as $cat) <option value="{{ $cat->id }}">{{ $cat->name }}</option> @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">+</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Base Unit (e.g. pcs)</label>
                            <input type="text" name="base_unit_name" class="form-control" value="pcs" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Unit Type</label>
                            <select name="unit_type" class="form-select">
                                <option value="unit">Unit</option>
                                <option value="sachet">Sachet</option>
                                <option value="roll">Roll</option>
                                <option value="carton">Carton</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Units/Carton</label>
                            <input type="number" name="units_per_carton" min="0" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Units/Roll (Optional)</label>
                            <input type="number" name="units_per_roll" min="0" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Selling Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Purchase Price</label>
                            <input type="number" step="0.01" name="purchase_price" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Opening Stock (Units)</label>
                            <input type="number" name="stock" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Opening Stock (Cartons)</label>
                            <input type="number" step="0.01" name="stock_cartons" class="form-control" value="0">
                            <small class="text-muted">If cartons are entered, the system converts them to unit stock using Units/Carton.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Product Image</label>
                            <input type="file" name="image" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="importProductsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.Products.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Import Products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Use the CSV template for large catalogs. Missing SKU values will be generated automatically during import.</p>
                    <div class="mb-3">
                        <a href="{{ route('inventory.Products.import.template') }}" class="btn btn-light border w-100">
                            <i class="far fa-file-lines me-2"></i>Download CSV Template
                        </a>
                    </div>
                    <div>
                        <label class="form-label">CSV File</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv,text/csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import Products</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: QUICK ADD CATEGORY --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="ajaxAddCategoryForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Quick Category</h5>
                </div>
                <div class="modal-body">
                    <input type="text" name="name" id="new_category_name" class="form-control" placeholder="Category Name" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
{{-- Required DataTables Buttons Assets --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        // PREVENT RE-INITIALIZATION ERROR
        if ($.fn.DataTable.isDataTable('#products-table')) {
            $('#products-table').DataTable().destroy();
        }

        var table = $('#products-table').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { extend: 'excelHtml5', className: 'dt-excel d-none', title: 'Product_Inventory_List', exportOptions: { columns: ':not(.no-print)' } },
                { extend: 'pdfHtml5', className: 'dt-pdf d-none', title: 'Product Inventory List', exportOptions: { columns: ':not(.no-print)' } },
                { extend: 'print', className: 'dt-print d-none', title: 'Product Inventory', exportOptions: { columns: ':not(.no-print)' } }
            ],
            pageLength: 25,
            language: { search: "" , searchPlaceholder: "Search..." }
        });

        // Trigger Exports from Custom Dropdown
        $('#export_excel').on('click', function(e) { e.preventDefault(); table.button('.dt-excel').trigger(); });
        $('#export_pdf').on('click', function(e) { e.preventDefault(); table.button('.dt-pdf').trigger(); });
        $('#export_print').on('click', function(e) { e.preventDefault(); table.button('.dt-print').trigger(); });

        // AJAX Category Store
        $('#ajaxAddCategoryForm').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true);
            
            fetch("{{ route('categories.store') }}", {
                method: "POST",
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ name: $('#new_category_name').val() })
            })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok) {
                    const msg = data?.message || Object.values(data?.errors || {})?.[0]?.[0] || 'Failed to add category.';
                    throw new Error(msg);
                }
                return data;
            })
            .then(data => {
                if(data.data) {
                    $('#product_category_select').append(new Option(data.data.name, data.data.id, true, true));
                    bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                    form.reset();
                }
            })
            .catch((err) => {
                alert(err.message || 'Unable to add category.');
            })
            .finally(() => btn.prop('disabled', false));
        });
    });
</script>
@endpush
@endsection
