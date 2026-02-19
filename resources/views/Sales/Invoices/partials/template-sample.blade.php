@php
    $logoPath = \App\Models\Setting::where('key', 'invoice_logo')->value('value')
        ?: \App\Models\Setting::where('key', 'site_logo')->value('value');
    $brandName = \App\Models\Setting::where('key', 'company_name')->value('value')
        ?: (optional(auth()->user())->company->name ?? config('app.name', 'SMATBOOK'));
    $brandEmail = \App\Models\Setting::where('key', 'company_email')->value('value') ?: 'support@smatbook.com';
    $brandPhone = \App\Models\Setting::where('key', 'company_phone')->value('value') ?: '+234-000-0000';
    $currency = \App\Models\Setting::where('key', 'pref_currency')->value('value') ?: 'NGN';
    $logoUrl = $logoPath ? asset($logoPath) : asset('assets/img/settings-logo1.png');
    $invoiceNo = 'INV-' . now()->format('Ymd') . '-001';
@endphp

<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <img src="{{ $logoUrl }}" alt="Company Logo" style="max-height:52px; width:auto;">
                    <h5 class="mt-2 mb-0">{{ $brandName }}</h5>
                    <small class="text-muted">{{ $brandEmail }} | {{ $brandPhone }}</small>
                </div>
                <div class="text-end">
                    <h4 class="mb-1">{{ $templateTitle ?? 'Invoice Template' }}</h4>
                    <small class="text-muted">Sample #{{ $invoiceNo }}</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Unit</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sample Item A</td>
                            <td class="text-end">2</td>
                            <td class="text-end">{{ $currency }} 4,500</td>
                            <td class="text-end">{{ $currency }} 9,000</td>
                        </tr>
                        <tr>
                            <td>Sample Item B</td>
                            <td class="text-end">1</td>
                            <td class="text-end">{{ $currency }} 6,000</td>
                            <td class="text-end">{{ $currency }} 6,000</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end">{{ $currency }} 15,000</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
