<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ReturnableAsset;
use App\Models\ReturnableAssetMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReturnableAssetController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = trim((string) $request->query('status'));

        $assetsQuery = ReturnableAsset::query()->latest();
        if ($search !== '') {
            $assetsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('asset_code', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }
        if (in_array($status, ['active', 'inactive', 'retired'], true)) {
            $assetsQuery->where('status', $status);
        }

        $assets = $assetsQuery->paginate(15)->withQueryString();
        $availableAssets = ReturnableAsset::query()
            ->where('status', 'active')
            ->where('available_quantity', '>', 0)
            ->orderBy('name')
            ->get();
        $openIssues = ReturnableAssetMovement::with(['asset', 'customer'])
            ->where('movement_type', 'issue')
            ->whereColumn('returned_quantity', '<', 'quantity')
            ->orderByRaw('CASE WHEN due_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_on')
            ->get();

        $customers = Customer::query()->orderBy('customer_name')->get(['id', 'customer_name']);
        $summary = [
            'asset_types' => ReturnableAsset::where('status', 'active')->count(),
            'total_units' => (int) ReturnableAsset::where('status', 'active')->sum('total_quantity'),
            'issued_units' => (int) $openIssues->sum(fn ($movement) => $movement->outstanding_quantity),
            'overdue_units' => (int) $openIssues
                ->filter(fn ($movement) => $movement->due_on?->isPast())
                ->sum(fn ($movement) => $movement->outstanding_quantity),
            'deposit_held' => (float) $openIssues->sum(function ($movement) {
                if ((int) $movement->quantity === 0) {
                    return 0;
                }
                return ((float) $movement->deposit_held / (int) $movement->quantity)
                    * $movement->outstanding_quantity;
            }),
        ];

        return view('assets.returnables.index', compact(
            'assets', 'availableAssets', 'openIssues', 'customers', 'summary', 'search', 'status'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->companyId();
        $validated = $request->validate([
            'asset_code' => [
                'required', 'string', 'max:80',
                Rule::unique('returnable_assets')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'name' => 'required|string|max:191',
            'category' => 'nullable|string|max:120',
            'serial_number' => 'nullable|string|max:191',
            'total_quantity' => 'required|integer|min:1|max:1000000',
            'deposit_amount' => 'nullable|numeric|min:0',
            'replacement_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        ReturnableAsset::create(array_merge($validated, [
            'company_id' => $companyId,
            'branch_id' => session('active_branch_id'),
            'branch_name' => session('active_branch_name'),
            'created_by' => Auth::id(),
            'available_quantity' => $validated['total_quantity'],
            'status' => 'active',
        ]));

        return back()->with('success', 'Returnable asset added to the custody register.');
    }

    public function issue(Request $request, ReturnableAsset $returnableAsset): RedirectResponse
    {
        $this->assertAssetAccess($returnableAsset);
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'customer_id' => 'nullable|integer',
            'custodian_name' => 'required|string|max:191',
            'custodian_phone' => 'nullable|string|max:60',
            'issued_on' => 'required|date',
            'due_on' => 'nullable|date|after_or_equal:issued_on',
            'deposit_held' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:191',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($returnableAsset, $validated) {
            $asset = ReturnableAsset::query()->lockForUpdate()->findOrFail($returnableAsset->id);
            abort_if($asset->status !== 'active', 422, 'Only active returnable assets can be issued.');
            abort_if((int) $validated['quantity'] > (int) $asset->available_quantity, 422, 'The issue quantity exceeds available units.');

            if (! empty($validated['customer_id'])) {
                Customer::query()->findOrFail((int) $validated['customer_id']);
            }

            $asset->decrement('available_quantity', (int) $validated['quantity']);
            $asset->movements()->create(array_merge($validated, [
                'company_id' => $this->companyId(),
                'branch_id' => $asset->branch_id,
                'branch_name' => $asset->branch_name,
                'created_by' => Auth::id(),
                'movement_type' => 'issue',
                'returned_quantity' => 0,
            ]));
        });

        return back()->with('success', 'Asset custody issue recorded.');
    }

    public function returnAsset(Request $request, ReturnableAssetMovement $movement): RedirectResponse
    {
        $this->assertMovementAccess($movement);
        abort_unless($movement->movement_type === 'issue', 404);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'returned_on' => 'required|date|after_or_equal:' . optional($movement->issued_on)->toDateString(),
            'condition' => 'required|in:good,damaged,lost',
            'reference' => 'nullable|string|max:191',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($movement, $validated) {
            $issue = ReturnableAssetMovement::query()->lockForUpdate()->findOrFail($movement->id);
            $asset = ReturnableAsset::query()->lockForUpdate()->findOrFail($issue->returnable_asset_id);
            $quantity = (int) $validated['quantity'];
            abort_if($quantity > $issue->outstanding_quantity, 422, 'The return quantity exceeds the outstanding custody quantity.');

            $issue->increment('returned_quantity', $quantity);
            if ($validated['condition'] === 'good') {
                $asset->increment('available_quantity', $quantity);
            } else {
                $asset->decrement('total_quantity', $quantity);
                if ((int) $asset->fresh()->total_quantity === 0) {
                    $asset->update(['status' => 'retired']);
                }
            }

            $asset->movements()->create([
                'company_id' => $this->companyId(),
                'branch_id' => $issue->branch_id,
                'branch_name' => $issue->branch_name,
                'parent_movement_id' => $issue->id,
                'customer_id' => $issue->customer_id,
                'created_by' => Auth::id(),
                'movement_type' => $validated['condition'] === 'good' ? 'return' : $validated['condition'],
                'custodian_name' => $issue->custodian_name,
                'custodian_phone' => $issue->custodian_phone,
                'quantity' => $quantity,
                'returned_quantity' => $quantity,
                'returned_on' => $validated['returned_on'],
                'condition' => $validated['condition'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Asset return and condition recorded.');
    }

    private function companyId(): int
    {
        $companyId = (int) (session('current_tenant_id') ?? Auth::user()?->company_id ?? 0);
        abort_if($companyId < 1, 403, 'An active business workspace is required.');
        return $companyId;
    }

    private function assertAssetAccess(ReturnableAsset $asset): void
    {
        abort_unless((int) $asset->company_id === $this->companyId(), 404);
    }

    private function assertMovementAccess(ReturnableAssetMovement $movement): void
    {
        abort_unless((int) $movement->company_id === $this->companyId(), 404);
    }
}
