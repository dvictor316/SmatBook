<style>
    .stock-card-custom {
        background: #ffffff;
        border-radius: 20px;
        border: none;
    }

    .stock-item {
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s ease;
    }

    .stock-item:last-child {
        border-bottom: none;
    }

    .stock-item:hover {
        background-color: rgba(239, 68, 68, 0.02);
    }

    .progress-stock {
        height: 6px;
        border-radius: 10px;
        background-color: #f1f5f9;
        margin-top: 8px;
    }

    .stock-label {
        font-weight: 700;
        color: var(--deep-sapphire);
        font-size: 0.85rem;
    }

    .stock-count-badge {
        font-size: 0.7rem;
        font-weight: 800;
        padding: 4px 10px;
        border-radius: 6px;
    }

    .status-critical { background: #fee2e2; color: #dc2626; }
    .status-warning { background: #fef3c7; color: #d97706; }
</style>

<div class="stock-card-custom shadow-sm border-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold" style="color: var(--deep-sapphire);">Inventory Depletion Alerts</h6>
        <span class="badge rounded-pill bg-danger" style="font-size: 0.6rem;">CRITICAL</span>
    </div>
    
    <div class="stock-list">
        @forelse($lowStockProducts as $product)
            @php
                // Logic: 15 is our threshold, so we calculate percentage based on that
                $percentage = ($product->stock / 15) * 100;
                $statusClass = $product->stock <= 5 ? 'status-critical' : 'status-warning';
                $barColor = $product->stock <= 5 ? 'bg-danger' : 'bg-warning';
            @endphp
            <div class="stock-item">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="stock-label">{{ $product->name }}</span>
                    <span class="stock-count-badge {{ $statusClass }}">
                        {{ $product->stock }} UNITS LEFT
                    </span>
                </div>
                <div class="progress progress-stock">
                    <div class="progress-bar {{ $barColor }}" role="progressbar" 
                         style="width: {{ $percentage }}%" 
                         aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <div class="mb-2">
                    <i class="fas fa-check-circle text-success opacity-25 fa-3x"></i>
                </div>
                <p class="text-muted small fw-bold">All nodes are optimally stocked.</p>
            </div>
        @endforelse
    </div>
</div>