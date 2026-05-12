<?php $page = 'recurring-invoice-show'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">

    @component('components.page-header')
        @slot('title') Recurring Template: {{ $template->template_name }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Action bar ───────────────────────────────────────────────── --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('sales.recurring-invoices.edit', $template) }}" class="btn btn-primary btn-sm">
            <i class="fe fe-edit me-1"></i> Edit
        </a>

        @if($template->status === 'active')
            <form method="POST" action="{{ route('sales.recurring-invoices.run', $template) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fe fe-play me-1"></i> Run Now
                </button>
            </form>
            <form method="POST" action="{{ route('sales.recurring-invoices.pause', $template) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="fe fe-pause me-1"></i> Pause
                </button>
            </form>
        @elseif($template->status === 'paused')
            <form method="POST" action="{{ route('sales.recurring-invoices.resume', $template) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fe fe-play me-1"></i> Resume
                </button>
            </form>
        @endif

        <form method="POST" action="{{ route('sales.recurring-invoices.clone', $template) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="fe fe-copy me-1"></i> Clone
            </button>
        </form>

        @if(!in_array($template->status, ['cancelled','completed']))
        <form method="POST" action="{{ route('sales.recurring-invoices.cancel', $template) }}" class="d-inline"
              onsubmit="return confirm('Cancel this recurring template? This cannot be undone.')">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="fe fe-x-circle me-1"></i> Cancel
            </button>
        </form>
        @endif
        @if($template->status !== 'archived')
        <form method="POST" action="{{ route('sales.recurring-invoices.archive', $template) }}" class="d-inline"
              onsubmit="return confirm('Archive this recurring template?')">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="fe fe-archive me-1"></i> Archive
            </button>
        </form>
        @endif

        <a href="{{ route('sales.recurring-invoices.index') }}" class="btn btn-outline-secondary btn-sm ms-auto">
            ← Back to list
        </a>
    </div>

    <div class="row g-4">

        {{-- ── Template info ─────────────────────────────────────────── --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between">
                    <strong>Template Details</strong>
                    <span class="badge {{ $template->status_badge }}">{{ ucfirst($template->status) }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Customer</dt>
                        <dd class="col-7">{{ $template->display_customer_name }}</dd>

                        <dt class="col-5">Currency</dt>
                        <dd class="col-7">{{ $template->currency }}</dd>

                        <dt class="col-5">Amount</dt>
                        <dd class="col-7 fw-semibold">{{ $template->currency }} {{ number_format($template->total, 2) }}</dd>

                        <dt class="col-5">Frequency</dt>
                        <dd class="col-7">{{ $template->frequency_label }}</dd>

                        <dt class="col-5">Automation</dt>
                        <dd class="col-7"><span class="badge bg-secondary">{{ $template->automation_label }}</span></dd>

                        <dt class="col-5">Timezone</dt>
                        <dd class="col-7">{{ $template->timezone ?? config('app.timezone') }}</dd>

                        <dt class="col-5">Starts On</dt>
                        <dd class="col-7">{{ optional($template->starts_on)->format('d M Y') ?? '—' }}</dd>

                        <dt class="col-5">Next Run</dt>
                        <dd class="col-7 {{ $template->isDue() ? 'text-danger fw-semibold' : '' }}">
                            {{ optional($template->next_run_on)->format('d M Y') ?? '—' }}
                        </dd>

                        <dt class="col-5">Last Run</dt>
                        <dd class="col-7">{{ optional($template->last_run_on)->format('d M Y') ?? 'Never' }}</dd>

                        <dt class="col-5">End Type</dt>
                        <dd class="col-7">{{ ucfirst($template->end_type) }}</dd>

                        @if($template->end_type === 'date')
                        <dt class="col-5">Ends On</dt>
                        <dd class="col-7">{{ optional($template->ends_on)->format('d M Y') }}</dd>
                        @elseif($template->end_type === 'count')
                        <dt class="col-5">Max Invoices</dt>
                        <dd class="col-7">{{ $template->max_occurrences }}</dd>
                        @endif

                        <dt class="col-5">Terms</dt>
                        <dd class="col-7">{{ $template->terms ?: '—' }}</dd>

                        <dt class="col-5">Due Days</dt>
                        <dd class="col-7">{{ $template->due_days }}</dd>

                        <dt class="col-5">Payment Link</dt>
                        <dd class="col-7">{{ $template->payment_link_enabled ? 'Enabled' : 'Disabled' }}</dd>

                        <dt class="col-5">Failures</dt>
                        <dd class="col-7">
                            {{ (int) ($template->failure_count ?? 0) }}
                            @if($template->last_failure_at)
                                <div class="text-danger small">{{ $template->last_failure_at->format('d M Y H:i') }} - {{ Str::limit($template->last_failure_message, 80) }}</div>
                            @endif
                        </dd>

                        @if($template->notes)
                        <dt class="col-5">Notes</dt>
                        <dd class="col-7">{{ $template->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Counters --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row text-center g-0">
                        <div class="col-4 border-end">
                            <div class="fs-3 fw-bold text-primary">{{ $template->occurrences_count }}</div>
                            <div class="text-muted small">Generated</div>
                        </div>
                        <div class="col-4 border-end">
                            <div class="fs-3 fw-bold text-success">
                                {{ $logs->where('status', 'success')->count() }}
                            </div>
                            <div class="text-muted small">Successful</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-3 fw-bold text-danger">
                                {{ $logs->where('status', 'failed')->count() }}
                            </div>
                            <div class="text-muted small">Failed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Items list + generation log ─────────────────────────── --}}
        <div class="col-md-7">

            {{-- Items table --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header"><strong>Invoice Items</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($template->items ?? [] as $item)
                                <tr>
                                    <td>{{ $item['product_name'] ?? '—' }}</td>
                                    <td class="text-end">{{ $item['qty'] ?? 1 }}</td>
                                    <td class="text-end">{{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($item['tax'] ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($item['discount'] ?? 0, 2) }}</td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format((($item['qty'] ?? 1) * ($item['unit_price'] ?? 0)) + ($item['tax'] ?? 0) - ($item['discount'] ?? 0), 2) }}
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No items stored.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-semibold">Subtotal</td>
                                    <td class="text-end">{{ number_format($template->subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-semibold">Tax</td>
                                    <td class="text-end">{{ number_format($template->tax_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-semibold">Discount</td>
                                    <td class="text-end">{{ number_format($template->discount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Total</td>
                                    <td class="text-end fw-bold">{{ $template->currency }} {{ number_format($template->total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Generation log --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header"><strong>Generation Log</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Scheduled Date</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                    <th>Invoice</th>
                                    <th>By</th>
                                    <th>Note</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                <tr>
                                    <td>{{ optional($log->scheduled_date)->format('d M Y') }}</td>
                                    <td><span class="badge {{ $log->status_badge }}">{{ ucfirst($log->status) }}</span></td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $log->event_type ?? 'generation') }}</td>
                                    <td>
                                        @if($log->sale)
                                            <a href="#" class="text-primary">{{ $log->sale->invoice_no }}</a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-capitalize">{{ $log->generated_by }}</td>
                                    <td class="text-muted small">
                                        {{ Str::limit($log->message, 60) }}
                                        @if(!empty($log->payload['posted_to_ledger']))
                                            <div class="text-success">Ledger posted</div>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ optional($log->finished_at ?? $log->created_at)->format('d M H:i') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fe fe-inbox d-block fs-2 mb-1"></i>
                                        No invoices generated yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($logs, 'hasPages') && $logs->hasPages())
                    <div class="p-3">{{ $logs->links() }}</div>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>
</div>
@endsection
