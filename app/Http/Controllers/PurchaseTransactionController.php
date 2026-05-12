<?php

namespace App\Http\Controllers;

use App\Models\PurchaseTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PurchaseTransactionController extends Controller
{
    public function index()
    {
        $query = PurchaseTransaction::query()->with('company');
        $this->applyScope($query);

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'amount'           => 'required|numeric',
            'transaction_type' => 'required|string',
            'date'             => 'required|date',
            'reference'        => 'nullable|string|max:191',
            'description'      => 'nullable|string|max:1000',
        ]);

        $transaction = PurchaseTransaction::create(array_merge($validated, $this->tenantPayload()));

        return response()->json($transaction, 201);
    }

    public function show(PurchaseTransaction $purchaseTransaction)
    {
        $this->ensureAccessible($purchaseTransaction);
        return response()->json($purchaseTransaction->load('company'));
    }

    public function update(Request $request, PurchaseTransaction $purchaseTransaction)
    {
        $this->authorizeAccess();
        $this->ensureAccessible($purchaseTransaction);

        $validated = $request->validate([
            'amount'           => 'sometimes|required|numeric',
            'transaction_type' => 'sometimes|required|string',
            'date'             => 'sometimes|required|date',
            'reference'        => 'nullable|string|max:191',
            'description'      => 'nullable|string|max:1000',
        ]);

        $purchaseTransaction->update($validated);
        return response()->json($purchaseTransaction);
    }

    public function destroy(PurchaseTransaction $purchaseTransaction)
    {
        $this->authorizeAccess();
        $this->ensureAccessible($purchaseTransaction);

        $purchaseTransaction->delete();
        return response()->json(['message' => 'Transaction deleted']);
    }

    private function authorizeAccess(): void
    {
        $user = Auth::user();

        if (!$user || !(
            $user->isSuperAdmin()
            || $user->hasRole('administrator')
            || $user->hasPermissionTo('manage_reports')
        )) {
            abort(403, 'You do not have permission to manage purchase transactions.');
        }
    }

    private function applyScope($query): void
    {
        $user = Auth::user();
        $companyId = (int) ($user?->company_id ?? session('current_tenant_id') ?? 0);

        if ($companyId > 0 && Schema::hasColumn('purchase_transactions', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($user && Schema::hasColumn('purchase_transactions', 'user_id')) {
            $query->where('user_id', $user->id);
        }
    }

    private function ensureAccessible(PurchaseTransaction $purchaseTransaction): void
    {
        $user = Auth::user();
        $companyId = (int) ($user?->company_id ?? session('current_tenant_id') ?? 0);

        if ($companyId > 0 && Schema::hasColumn('purchase_transactions', 'company_id')) {
            abort_unless((int) $purchaseTransaction->company_id === $companyId, 404);
            return;
        }

        if ($user && Schema::hasColumn('purchase_transactions', 'user_id')) {
            abort_unless((int) $purchaseTransaction->user_id === (int) $user->id, 404);
        }
    }

    private function tenantPayload(): array
    {
        $user = Auth::user();

        return array_filter([
            'company_id' => $user?->company_id ?? session('current_tenant_id'),
            'user_id' => $user?->id,
            'branch_id' => session('active_branch_id'),
            'branch_name' => session('active_branch_name'),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
