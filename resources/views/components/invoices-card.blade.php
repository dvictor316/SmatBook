<div class="row d-print-none"> 
    @if(isset($invoicescards) && is_iterable($invoicescards))
        @foreach ($invoicescards as $card)
            <div class="col-xl-3 col-lg-4 col-sm-6 col-12 d-flex">
                <div class="card inovices-card w-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="dash-widget-header">
                            <span class="inovices-widget-icon {{ $card['class'] ?? 'bg-primary-light' }}">
                                <i data-feather="{{ $card['icon'] ?? 'file-text' }}"></i>
                            </span>
                            <div class="dash-count">
                                <div class="dash-title text-muted small">{{ $card['title'] ?? 'Metric' }}</div>
                                <div class="dash-counts">
                                    <h4 class="fw-bold">
                                        @if(($card['is_currency'] ?? true) && is_numeric($card['amount'] ?? null))
                                            ₦{{ number_format($card['amount'], 2) }}
                                        @else
                                            {{ $card['amount'] ?? '0' }}
                                        @endif
                                    </h4>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <p class="inovices-all mb-0 small">Volume: 
                                <span class="badge rounded-pill bg-light text-dark border ms-1">
                                    {{ $card['count'] ?? 0 }}
                                </span>
                            </p>
                            <p class="inovice-trending text-success mb-0 small">
                                {{ $card['trend'] ?? 'Stable' }} 
                                <span class="ms-1"><i class="fas fa-arrow-up tiny"></i></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="col-12">
            <div class="alert alert-warning">No invoice summary data available.</div>
        </div>
    @endif
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
