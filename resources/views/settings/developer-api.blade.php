<?php $page = 'developer-api'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title') Developer API @endslot
        @endcomponent

        @if($plainTextToken)
            <div class="alert alert-warning border-warning" role="alert">
                <h5 class="alert-heading">Copy this token now</h5>
                <p>For security, SmartProbook stores only its hash and cannot show it again.</p>
                <div class="input-group">
                    <input id="newApiToken" class="form-control font-monospace" value="{{ $plainTextToken }}" readonly>
                    <button class="btn btn-dark" type="button" id="copyApiToken" title="Copy API token"><i class="fas fa-copy"></i></button>
                </div>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-xl-4">
                <section class="border rounded p-4 bg-white shadow-sm">
                    <h4 class="mb-2">Create Access Token</h4>
                    <p class="text-muted">Tokens are restricted to this business and selected read permissions.</p>
                    <form method="POST" action="{{ route('developer-api.tokens.store') }}">
                        @csrf
                        <div class="mb-3"><label class="form-label">Token Name</label><input class="form-control" name="name" value="{{ old('name') }}" placeholder="Warehouse integration" required></div>
                        <div class="mb-3"><label class="form-label">Expires</label><select class="form-select" name="expires_in" required><option value="30">30 days</option><option value="90" selected>90 days</option><option value="365">1 year</option><option value="never">No automatic expiry</option></select></div>
                        <fieldset class="mb-4"><legend class="form-label">Read Permissions</legend>
                            @foreach(['products:read' => 'Products and stock', 'customers:read' => 'Customers', 'invoices:read' => 'Invoices'] as $ability => $label)
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="abilities[]" value="{{ $ability }}" id="ability-{{ $loop->index }}" @checked(in_array($ability, old('abilities', ['products:read'])))><label class="form-check-label" for="ability-{{ $loop->index }}">{{ $label }}</label></div>
                            @endforeach
                        </fieldset>
                        <button class="btn btn-primary w-100" type="submit"><i class="fas fa-key me-1"></i> Generate Token</button>
                    </form>
                </section>
            </div>

            <div class="col-xl-8">
                <section class="border rounded p-4 bg-white shadow-sm mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h4 class="mb-1">Active Tokens</h4><p class="text-muted mb-0">Revoke credentials immediately when an integration is retired.</p></div><span class="badge bg-primary">Business {{ $companyId }}</span></div>
                    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Name</th><th>Permissions</th><th>Last Used</th><th>Expires</th><th class="text-end">Action</th></tr></thead><tbody>
                        @forelse($tokens as $token)
                            <tr><td class="fw-bold">{{ $token->name }}</td><td>@foreach(collect($token->abilities)->reject(fn($ability) => str_starts_with($ability, 'company:')) as $ability)<span class="badge bg-light text-dark border me-1">{{ $ability }}</span>@endforeach</td><td>{{ optional($token->last_used_at)->diffForHumans() ?: 'Never' }}</td><td>{{ optional($token->expires_at)->format('d M Y') ?: 'No expiry' }}</td><td class="text-end"><form method="POST" action="{{ route('developer-api.tokens.destroy', $token->id) }}" onsubmit="return confirm('Revoke this API token?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" title="Revoke token"><i class="fas fa-trash"></i></button></form></td></tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No API tokens exist for this business.</td></tr>
                        @endforelse
                    </tbody></table></div>
                </section>

                <section class="border rounded p-4 bg-white shadow-sm">
                    <h4 class="mb-3">API v1</h4>
                    <p class="text-muted">Send the token as <code>Authorization: Bearer YOUR_TOKEN</code>. Responses are JSON and list endpoints support <code>?per_page=25&amp;page=1</code>.</p>
                    <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Method</th><th>Endpoint</th><th>Required permission</th></tr></thead><tbody>
                        <tr><td><span class="badge bg-success">GET</span></td><td><code>{{ url('/api/v1/workspace') }}</code></td><td>Valid business token</td></tr>
                        <tr><td><span class="badge bg-success">GET</span></td><td><code>{{ url('/api/v1/products') }}</code></td><td><code>products:read</code></td></tr>
                        <tr><td><span class="badge bg-success">GET</span></td><td><code>{{ url('/api/v1/customers') }}</code></td><td><code>customers:read</code></td></tr>
                        <tr><td><span class="badge bg-success">GET</span></td><td><code>{{ url('/api/v1/invoices') }}</code></td><td><code>invoices:read</code></td></tr>
                    </tbody></table></div>
                    <pre class="bg-dark text-white p-3 rounded mb-0"><code>curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     "{{ url('/api/v1/products?per_page=25') }}"</code></pre>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('copyApiToken')?.addEventListener('click', async function () {
    const input = document.getElementById('newApiToken');
    await navigator.clipboard.writeText(input.value);
    this.innerHTML = '<i class="fas fa-check"></i>';
});
</script>
@endpush
