
<div> 

    <div class="row">

        <div class="col-md-6 col-sm-6">
            <div class="card">
                <div class="card-header">
                    <div class="row align-center">
                        <div class="col">
                            <h5 class="card-title">Recent Invoices</h5>
                        </div>
                        <div class="col-auto">
                            <a href="{{ url('invoices') }}" class="btn-right btn btn-sm btn-outline-primary">
                                View All
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body" style="height: 400px; overflow-y: auto;"> 

                    <div class="progress progress-md rounded-pill mb-3">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentages['paid'] ?? 0 }}%" aria-valuenow="{{ $percentages['paid'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $percentages['unpaid'] ?? 0 }}%" aria-valuenow="{{ $percentages['unpaid'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $percentages['overdue'] ?? 0 }}%" aria-valuenow="{{ $percentages['overdue'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                        @if(isset($percentages['draft']) && $percentages['draft'] > 0)
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $percentages['draft'] }}%" aria-valuenow="{{ $percentages['draft'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <div class="col-auto">
                            <i class="fas fa-circle text-success me-1"></i> Paid ({{ $currencySymbol }}{{ number_format($invoiceStats['received'] ?? 0, 0) }})
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-circle text-warning me-1"></i> Unpaid/Overdue ({{ $currencySymbol }}{{ number_format($invoiceStats['pending'] ?? 0, 0) }})
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-light"><tr><th>Customer</th><th>Amount</th><th>Due Date</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                            <tbody> 
                                @foreach ($invoices as $invoice) 
                                    <tr>
                                        <td><h2 class="table-avatar"><a href="{{ url('customer-details', $invoice->customer?->id) }}"><img class="avatar avatar-sm me-2 avatar-img rounded-circle" src="{{ URL::asset('/assets/img/profiles/avatar-01.jpg') }}" alt="User Image">{{ $invoice->customer?->name ?? 'N/A' }}</a></h2></td>
                                        <td>{{ $currencySymbol }}{{ number_format($invoice->amount, 2) }}</td>
                                        <td>{{ optional($invoice->due_date)->format('d M Y') ?? 'N/A' }}</td>
                                        <td><span class="badge bg-inverse-{{ Str::slug($invoice->status) ?? 'info' }}">{{ ucfirst($invoice->status) }}</span></td>
                                        <td class="text-end"><div class="dropdown dropdown-action"><a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-h"></i></a><div class="dropdown-menu dropdown-menu-right"><a class="dropdown-item" href="{{ url('edit-invoice') }}"><i class="far fa-edit me-2"></i>Edit</a><a class="dropdown-item" href="{{ url('invoice-details') }}"><i class="far fa-eye me-2"></i>View</a></div></div></td>
                                    </tr> 
                                @endforeach 
                            </tbody>
                        </table>
                    </div> 
                </div> 
            </div> 
        </div> 

        <div class="col-md-6 col-sm-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Invoice Status Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="invoiceDoughnutChart" style="height: 400px;"></canvas> 
                </div>
            </div>
        </div>

    </div> 

</div> 

@push('scripts')
<script>
document.addEventListener('livewire:initialized', () => {
    const totalReceived = @json($invoiceStats['received'] ?? 0);
    const totalPending = @json($invoiceStats['pending'] ?? 0);
    const currency = @json($currencySymbol); 

    if (typeof Chart !== 'undefined') {
        var ctxDoughnut = document.getElementById('invoiceDoughnutChart').getContext('2d');
        var invoiceDoughnutChart = new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Unpaid/Overdue'],
                datasets: [{
                    data: [totalReceived, totalPending],
                    backgroundColor: ['#28a745', '#ffc107'],
                    borderColor: ['#fff', '#fff'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) { label += ': '; }
                                label += currency + context.parsed.toLocaleString();
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
