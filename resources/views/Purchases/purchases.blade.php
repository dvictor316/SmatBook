// Page: resources/views/Purchases/purchases.blade.php

@php $page = 'products'; @endphp
@extends('layout.mainlayout')

@section('content')

{{-- 
    CUSTOM STYLES
--}}
<style>
    /* Base wrapper transition */
    #main-content-wrapper {
        transition: margin-left 0.3s ease, width 0.3s ease;
        width: 100%;
        overflow-x: hidden;
        /* Padding to clear navbar */
        padding-top: 100px; 
    }

    /* DESKTOP Sidebar Offset */
    @media (min-width: 992px) {
        #main-content-wrapper {
            margin-left: 250px;
            width: calc(100% - 250px);
        }
        body.sidebar-collapsed #main-content-wrapper {
            margin-left: 70px;
            width: calc(100% - 70px);
        }
    }

    /* MOBILE */
    @media (max-width: 991.98px) {
        #main-content-wrapper {
            margin-left: 0;
            width: 100%;
            padding-top: 100px;
        }
    }
    
    /* Print overrides */
    @media print {
        #main-content-wrapper { margin: 0 !important; padding: 0 !important; }
        .no-print, .card-header, .card.shadow-sm { display: none !important; }
    }
    
    /* Hide native DataTables buttons since we use custom triggers */
    .dt-buttons { display: none !important; }
</style>

{{-- WRAPPER START --}}
<div id="main-content-wrapper" class="container-fluid px-4 pb-4">

    <div class="mb-3">
        <span class="badge bg-light border text-primary px-3 py-2">
            <i class="fas fa-code-branch me-2"></i>
            Active Branch: {{ $activeBranch['name'] ?? 'All Recorded Purchases' }}
        </span>
    </div>

    {{-- 
        1. TOP RIGHT ACTION BUTTONS 
        Moved here as requested
    --}}
    <div class="d-flex justify-content-end mb-3">
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-download me-2"></i> Export / Print
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="#" id="custom_btn_print">
                        <i class="fas fa-print me-2 text-secondary"></i> Print Report
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="#" id="custom_btn_excel">
                        <i class="far fa-file-excel me-2 text-success"></i> Excel
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" id="custom_btn_pdf">
                        <i class="far fa-file-pdf me-2 text-danger"></i> PDF
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- 2. SEARCH FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('purchase-report') }}" method="GET" class="row align-items-center">
                <div class="col-sm-8">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name, SKU, or Purchase No...">
                </div>
                <div class="col-sm-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
                <div class="col-sm-2">
                    <a href="{{ route('purchase-report') }}" class="btn btn-secondary w-100">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. DATA TABLE --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card-table">
                <div class="card-body">
                    <div class="table-responsive" id="printSection">
                        <table class="table table-striped table-hover" id="inventoryTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th class="no-print text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @isset($products)
                                    @forelse ($products as $product)
                                        <tr>
                                            <td>{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}</td>
                                            <td><strong>{{ $product->name }}</strong></td>
                                            <td>{{ $product->sku }}</td>
                                            <td>{{ $product->category->name ?? 'N/A' }}</td>
                                            <td>{{ number_format($product->price, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $product->stock < 10 ? 'bg-danger' : 'bg-success' }}">
                                                    {{ $product->stock }}
                                                </span>
                                            </td>
                                            <td>{{ ucfirst($product->status) }}</td>
                                            <td class="text-end no-print">
                                                <a href="{{ route('purchases.show', $product->id) }}" class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1">
                                                    <i class="far fa-eye"></i>
                                                    <span class="d-none d-md-inline">View</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">No data matches your filter.</td>
                                        </tr>
                                    @endforelse
                                @else
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-danger">
                                            <strong>Error: Product data not loaded.</strong>
                                        </td>
                                    </tr>
                                @endisset
                            </tbody>
                        </table>

                        {{-- Pagination Links (Hidden on Print) --}}
                        @if(isset($products) && method_exists($products, 'links'))
                            <div class="d-flex justify-content-center mt-4 no-print">
                                {{ $products->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- WRAPPER END --}}

@endsection

@push('scripts')
{{-- 
    REQUIRED SCRIPTS FOR DATATABLES EXPORT 
    (JSZip for Excel, PDFMake for PDF) 
--}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        // Destroy existing if re-initializing
        if ($.fn.DataTable.isDataTable('#inventoryTable')) {
            $('#inventoryTable').DataTable().destroy();
        }

        // Initialize DataTable with Export Capabilities
        var table = $('#inventoryTable').DataTable({
            dom: 'Bfrtip', // Defines where buttons are placed (B = buttons)
            paging: false, // We use Laravel pagination
            searching: false, // We use custom search
            info: false,
            buttons: [
                {
                    extend: 'excelHtml5',
                    className: 'dt-btn-excel', // Hidden hook class
                    title: 'Product Inventory Report',
                    exportOptions: {
                        columns: ':not(.no-print)' // Exclude Action column
                    }
                },
                {
                    extend: 'pdfHtml5',
                    className: 'dt-btn-pdf', // Hidden hook class
                    title: 'Product Inventory Report',
                    exportOptions: {
                        columns: ':not(.no-print)'
                    }
                },
                {
                    extend: 'print',
                    className: 'dt-btn-print', // Hidden hook class
                    title: 'Product Inventory Report',
                    customize: function (win) {
                        $(win.document.body).css('font-size', '10pt');
                        $(win.document.body).find('table')
                            .addClass('compact')
                            .css('font-size', 'inherit');
                    },
                    exportOptions: {
                        columns: ':not(.no-print)'
                    }
                }
            ]
        });

        // --- WIRE CUSTOM BUTTONS TO DATATABLES API --- //
        
        // Excel Trigger
        $('#custom_btn_excel').on('click', function(e) {
            e.preventDefault();
            table.button('.dt-btn-excel').trigger();
        });

        // PDF Trigger
        $('#custom_btn_pdf').on('click', function(e) {
            e.preventDefault();
            table.button('.dt-btn-pdf').trigger();
        });

        // Print Trigger
        $('#custom_btn_print').on('click', function(e) {
            e.preventDefault();
            table.button('.dt-btn-print').trigger();
        });
    });
</script>
@endpush
