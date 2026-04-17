<style>
    .table-custom-sapphire {
        background: #ffffff;
        border-radius: 20px;
        overflow: hidden;
    }

    .table-custom-sapphire thead th {
        background-color: #f8fbff;
        border-bottom: 1px solid #edf2f7;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-size: 0.65rem;
        padding: 15px 20px;
    }

    .table-custom-sapphire tbody td {
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.85rem;
        color: var(--deep-sapphire);
    }

    .table-custom-sapphire tbody tr:hover {
        background-color: rgba(0, 35, 71, 0.02);
    }

    .order-badge {
        background: rgba(0, 35, 71, 0.05);
        color: var(--deep-sapphire);
        padding: 5px 12px;
        border-radius: 8px;
        font-family: 'Monaco', 'Consolas', monospace;
        font-size: 0.75rem;
    }

    .customer-name {
        font-weight: 600;
        color: #1e293b;
    }

    .amount-bold {
        font-weight: 800;
        color: var(--deep-sapphire);
    }
</style>

<div class="table-custom-sapphire border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Order Ref</th>
                    <th>Node Customer</th>
                    <th>Transaction Total</th>
                    <th>Processed Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($latestInvoices as $invoice)
                <tr>
                    <td>
                        <span class="order-badge">#{{ $invoice->order_number }}</span>
                    </td>
                    <td>
                        <div class="customer-name">{{ $invoice->customer_name }}</div>
                        <small class="text-muted" style="font-size: 0.7rem;">Verified Transaction</small>
                    </td>
                    <td class="amount-bold">
                        ₦{{ number_format($invoice->total, 2) }}
                    </td>
                    <td>
                        <div class="text-muted small">
                            <i class="far fa-calendar-alt me-1"></i>
                            {{ $invoice->created_at->format('d M, Y') }}
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-5">
                        <img src="{{ asset('assets/img/logos.png') }}" style="height: 60px; opacity: 0.1;" class="mb-3">
                        <p class="text-muted small">No recent transactions detected for this node.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>