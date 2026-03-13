@php
    $logoPath = \App\Models\Setting::where('key', 'invoice_logo')->value('value')
        ?: \App\Models\Setting::where('key', 'site_logo')->value('value');
    $companyName = \App\Models\Setting::where('key', 'company_name')->value('value') ?: (optional(auth()->user())->company->name ?? config('app.name'));
    $selectedInvoiceTemplate = \App\Models\Setting::where('key', 'invoice_template')->value('value') ?: 'template_1';
    $logoUrl = $logoPath ? asset($logoPath) : asset('assets/img/logos.png');
@endphp
<div class="tab-pane active" id="invoice_tab" role="tabpanel" aria-labelledby="invoice-tab" tabindex="0">
    <div class="card template-invoice-card">
        <div class="card-body pb-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="invoice-card-title mb-0">
                    <h6 class="mb-0">Invoice</h6>
                    <small class="text-muted">Preview reflects your current invoice branding</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ $logoUrl }}" alt="Company Logo" style="max-height:30px; width:auto;">
                    <span class="small fw-semibold">{{ $companyName }}</span>
                </div>
            </div>
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('invoice-settings') }}" class="btn btn-primary btn-sm text-white">
                    <i class="fas fa-plus me-1"></i> Add Template Assets
                </a>
            </div>
            <div class="row">
                @php
                    $templates = [
                        ['key' => 'template_1', 'name' => 'Executive Blue', 'route' => route('invoice-one-a')],
                        ['key' => 'template_2', 'name' => 'Compact Ledger', 'route' => route('invoice-two')],
                        ['key' => 'template_3', 'name' => 'Statement Pro', 'route' => route('invoice-three')],
                        ['key' => 'template_4', 'name' => 'Corporate Edge', 'route' => route('invoice-four-a')],
                        ['key' => 'template_5', 'name' => 'Premium Signature', 'route' => route('invoice-five')],
                    ];
                @endphp
                @foreach($templates as $template)
                    <div class="col-md-6 col-xl-3 col-sm-12 d-md-flex d-sm-block">
                        <div class="blog grid-blog invoice-blog flex-fill d-flex flex-wrap align-content-betweens {{ $selectedInvoiceTemplate === $template['key'] ? 'active' : '' }}">
                            <div class="blog-image">
                                <a href="{{ $template['route'] }}" class="img-general">
                                    <img class="img-fluid" src="{{ $logoUrl }}" alt="Template Logo Preview" style="height:130px;object-fit:contain;background:#f8fafc;padding:10px;">
                                </a>
                                <a href="{{ $template['route'] }}" class="preview-invoice"><i class="fa-regular fa-eye"></i></a>
                            </div>
                            <div class="invoice-content-title">
                                <a href="{{ $template['route'] }}">{{ $template['name'] }}</a>
                                <form action="{{ route('settings.update') }}" method="POST" class="ms-2">
                                    @csrf
                                    <input type="hidden" name="invoice_template" value="{{ $template['key'] }}">
                                    <button type="submit" class="invoice-star border-0 bg-transparent p-0" data-bs-toggle="tooltip" data-bs-placement="left" title="Make as default">
                                        <i class="{{ $selectedInvoiceTemplate === $template['key'] ? 'fa-solid text-warning' : 'fa-regular' }} fa-star"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
