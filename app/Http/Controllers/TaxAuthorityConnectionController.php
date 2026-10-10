<?php

namespace App\Http\Controllers;

use App\Models\TaxAuthorityConnection;
use App\Support\TaxAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TaxAuthorityConnectionController extends Controller
{
    public function store(Request $request)
    {
        if (! Schema::hasTable('tax_authority_connections')) {
            return back()->with('error', 'Tax authority connection tables are missing. Run `php artisan migrate` first.');
        }

        $validated = $request->validate([
            'environment' => 'required|in:sandbox,production',
            'taxpayer_id' => 'required|string|max:100',
            'organization_id' => 'nullable|string|max:150',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:2000',
            'access_token' => 'nullable|string|max:10000',
            'refresh_token' => 'nullable|string|max:10000',
            'token_expires_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);
        $companyId = (int) (session('current_tenant_id') ?? auth()->user()?->company_id ?? 0) ?: null;
        $branchId = trim((string) session('active_branch_id', '')) ?: null;
        $connection = TaxAuthorityConnection::firstOrNew([
            'company_id' => $companyId,
            'provider' => 'nrs',
            'country_code' => 'NGA',
            'branch_id' => $branchId,
        ]);
        $before = $connection->exists ? $connection->toArray() : null;
        foreach (['client_secret', 'access_token', 'refresh_token'] as $secret) {
            if (blank($validated[$secret] ?? null)) {
                unset($validated[$secret]);
            }
        }
        $connection->fill(array_merge($validated, [
            'user_id' => auth()->id(),
            'branch_name' => trim((string) session('active_branch_name', '')) ?: null,
            'is_active' => $request->boolean('is_active'),
            'scopes' => ['vat:write', 'wht:write', 'returns:read'],
            'last_connected_at' => filled($validated['access_token'] ?? null) ? now() : $connection->last_connected_at,
        ]))->save();
        TaxAuditService::record($connection, 'tax_authority_connection.saved', $before, [
            'provider' => 'nrs',
            'environment' => $connection->environment,
            'taxpayer_id' => $connection->taxpayer_id,
            'is_active' => $connection->is_active,
        ]);

        return back()->with('success', 'NRS authority connection saved securely.');
    }
}
