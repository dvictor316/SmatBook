<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixedAssetController extends Controller
{
    private function applyTenantScope($query, string $table)
    {
        $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (Auth::id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'created_by')) {
            $query->where("{$table}.created_by", $userId);
        }

        return $query;
    }

    private function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
    }

    private function applyBranchScope($query, string $table)
    {
        $activeBranch = $this->getActiveBranchContext();
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        if ($branchId === '' && $branchName === '') {
            return $query;
        }

        return $query->where(function ($sub) use ($table, $branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $sub->where("{$table}.branch_id", $branchId);
            }
            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $sub->orWhere("{$table}.branch_name", $branchName);
            }
        });
    }

    public function index()
    {
        $assetsQuery = FixedAsset::with(['assetAccount', 'depreciationAccount', 'expenseAccount'])->latest();
        $this->applyTenantScope($assetsQuery, 'fixed_assets');
        $this->applyBranchScope($assetsQuery, 'fixed_assets');
        $assets = $assetsQuery->paginate(15);

        $historyQuery = FixedAssetDepreciation::with('asset')->latest('run_date');
        $this->applyTenantScope($historyQuery, 'fixed_asset_depreciations');
        $this->applyBranchScope($historyQuery, 'fixed_asset_depreciations');
        $depreciations = $historyQuery->limit(20)->get();

        $accountQuery = Account::query()->where('is_active', true)->orderBy('name');
        $this->applyTenantScope($accountQuery, 'accounts');
        $this->applyBranchScope($accountQuery, 'accounts');
        $accounts = $accountQuery->get();

        $assetAccounts = $accounts->filter(fn ($account) => $account->type === Account::TYPE_ASSET);
        $expenseAccounts = $accounts->filter(fn ($account) => $account->type === Account::TYPE_EXPENSE);

        $summary = [
            'asset_count' => $assets->total(),
            'gross_cost' => (float) $assets->getCollection()->sum(fn ($asset) => (float) ($asset->cost ?? 0)),
            'accumulated_depreciation' => (float) $assets->getCollection()->sum(fn ($asset) => (float) ($asset->accumulated_depreciation ?? 0)),
            'book_value' => (float) $assets->getCollection()->sum(fn ($asset) => (float) ($asset->book_value ?? 0)),
        ];

        return view('Finance.fixed-assets', compact('assets', 'depreciations', 'assetAccounts', 'expenseAccounts', 'summary'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'account_id' => 'required|exists:accounts,id',
            'depreciation_account_id' => 'required|exists:accounts,id',
            'expense_account_id' => 'required|exists:accounts,id',
            'acquired_on' => 'required|date',
            'cost' => 'required|numeric|min:0.01',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1|max:600',
            'depreciation_method' => 'required|in:straight_line',
            'notes' => 'nullable|string|max:1000',
        ]);

        $companyId = Auth::user()?->company_id ?? session('current_tenant_id');
        $activeBranch = $this->getActiveBranchContext();
        $cost = round((float) $validated['cost'], 2);

        FixedAsset::create([
            'company_id' => $companyId,
            'branch_id' => $activeBranch['id'],
            'branch_name' => $activeBranch['name'],
            'created_by' => Auth::id(),
            'asset_code' => 'FA-' . now()->format('ymdHis') . '-' . strtoupper(substr(md5((string) microtime(true)), 0, 3)),
            'name' => $validated['name'],
            'account_id' => (int) $validated['account_id'],
            'depreciation_account_id' => (int) $validated['depreciation_account_id'],
            'expense_account_id' => (int) $validated['expense_account_id'],
            'acquired_on' => $validated['acquired_on'],
            'cost' => $cost,
            'salvage_value' => round((float) ($validated['salvage_value'] ?? 0), 2),
            'useful_life_months' => (int) $validated['useful_life_months'],
            'depreciation_method' => $validated['depreciation_method'],
            'status' => 'active',
            'accumulated_depreciation' => 0,
            'book_value' => $cost,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('finance.fixed-assets.index')->with('success', 'Fixed asset added successfully.');
    }

    public function depreciate(Request $request, FixedAsset $fixedAsset)
    {
        $asset = $this->scopeAssetQuery()->findOrFail($fixedAsset->id);

        if (($asset->status ?? 'active') !== 'active') {
            return back()->with('error', 'Only active assets can be depreciated.');
        }

        $validated = $request->validate([
            'run_date' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($asset, $validated) {
            $cost = (float) ($asset->cost ?? 0);
            $salvage = (float) ($asset->salvage_value ?? 0);
            $depreciableBase = max(0, $cost - $salvage);
            $remaining = max(0, $depreciableBase - (float) ($asset->accumulated_depreciation ?? 0));

            if ($remaining <= 0.009) {
                return back()->with('info', 'This asset is already fully depreciated.');
            }

            $lifeMonths = max(1, (int) ($asset->useful_life_months ?? 1));
            $monthlyAmount = round($depreciableBase / $lifeMonths, 2);
            $amount = min($remaining, $monthlyAmount);
            $runDate = $validated['run_date'] ?? now()->toDateString();
            $reference = 'FADP-' . now()->format('Ymd-His') . '-' . $asset->id;
            $description = 'Depreciation for fixed asset ' . ($asset->asset_code ?: $asset->name);

            $this->postTransaction($asset->expense_account_id, $runDate, $reference, $description, $amount, 0, $asset);
            $this->postTransaction($asset->depreciation_account_id, $runDate, $reference, $description, 0, $amount, $asset);

            FixedAssetDepreciation::create([
                'company_id' => $asset->company_id,
                'branch_id' => $asset->branch_id,
                'branch_name' => $asset->branch_name,
                'fixed_asset_id' => $asset->id,
                'created_by' => Auth::id(),
                'run_date' => $runDate,
                'period_label' => now()->parse($runDate)->format('M Y'),
                'amount' => $amount,
                'reference_no' => $reference,
                'notes' => $description,
            ]);

            $asset->accumulated_depreciation = round((float) $asset->accumulated_depreciation + $amount, 2);
            $asset->book_value = max($salvage, round($cost - (float) $asset->accumulated_depreciation, 2));
            $asset->last_depreciated_on = $runDate;
            if ($asset->book_value <= $salvage + 0.009) {
                $asset->status = 'fully_depreciated';
            }
            $asset->save();

            return redirect()->route('finance.fixed-assets.index')->with('success', 'Depreciation posted successfully.');
        });
    }

    private function postTransaction(int $accountId, string $date, string $reference, string $description, float $debit, float $credit, FixedAsset $asset): void
    {
        Transaction::create([
            'account_id' => $accountId,
            'company_id' => $asset->company_id,
            'branch_id' => $asset->branch_id,
            'branch_name' => $asset->branch_name,
            'transaction_date' => $date,
            'reference' => $reference,
            'description' => $description,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'balance' => 0,
            'transaction_type' => Transaction::TYPE_JOURNAL,
            'related_id' => $asset->id,
            'related_type' => FixedAsset::class,
            'user_id' => Auth::id(),
        ]);
    }

    private function scopeAssetQuery()
    {
        $query = FixedAsset::query();
        $this->applyTenantScope($query, 'fixed_assets');
        $this->applyBranchScope($query, 'fixed_assets');

        return $query;
    }
}
