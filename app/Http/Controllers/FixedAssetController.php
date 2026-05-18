<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Services\FixedAssetDepreciationService;
use App\Support\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class FixedAssetController extends Controller
{
    public function __construct(private FixedAssetDepreciationService $depreciationService)
    {
    }

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

    public function index(Request $request)
    {
        $status = strtolower(trim((string) $request->string('status')));
        $search = trim((string) $request->string('q'));
        $month = trim((string) $request->string('month'));
        $fromDate = trim((string) $request->string('from_date'));
        $toDate = trim((string) $request->string('to_date'));

        $assetsQuery = FixedAsset::with(['assetAccount', 'depreciationAccount', 'expenseAccount'])->latest();
        $this->applyTenantScope($assetsQuery, 'fixed_assets');
        $this->applyBranchScope($assetsQuery, 'fixed_assets');
        if (in_array($status, ['active', 'fully_depreciated', 'disposed', 'archived'], true)) {
            $assetsQuery->where('status', $status);
        }
        if ($search !== '') {
            $assetsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('asset_code', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%');
            });
        }
        if ($month !== '') {
            $assetsQuery->whereBetween('acquired_on', [
                now()->parse($month . '-01')->startOfMonth()->toDateString(),
                now()->parse($month . '-01')->endOfMonth()->toDateString(),
            ]);
        } else {
            if ($fromDate !== '') {
                $assetsQuery->whereDate('acquired_on', '>=', $fromDate);
            }
            if ($toDate !== '') {
                $assetsQuery->whereDate('acquired_on', '<=', $toDate);
            }
        }
        $assets = $assetsQuery->paginate(15)->appends($request->query());
        $assets->getCollection()->transform(function (FixedAsset $asset) {
            $asset->next_due_date = $this->depreciationService->nextDueDate($asset);
            $asset->scheduled_depreciation_amount = $this->depreciationService->previewAmount($asset);
            $asset->depreciation_is_due = ($asset->status ?? 'active') === 'active'
                && Carbon::parse($asset->next_due_date)->lte(now()->startOfDay());

            return $asset;
        });
        $dueAssetsCount = (clone $assetsQuery)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('next_depreciation_on')
                    ->orWhereDate('next_depreciation_on', '<=', now()->toDateString());
            })
            ->count();

        $historyQuery = FixedAssetDepreciation::with('asset')->latest('run_date');
        $this->applyTenantScope($historyQuery, 'fixed_asset_depreciations');
        $this->applyBranchScope($historyQuery, 'fixed_asset_depreciations');
        if ($month !== '') {
            $historyQuery->whereBetween('run_date', [
                now()->parse($month . '-01')->startOfMonth()->toDateString(),
                now()->parse($month . '-01')->endOfMonth()->toDateString(),
            ]);
        } else {
            if ($fromDate !== '') {
                $historyQuery->whereDate('run_date', '>=', $fromDate);
            }
            if ($toDate !== '') {
                $historyQuery->whereDate('run_date', '<=', $toDate);
            }
        }
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

        return view('Finance.fixed-assets', compact(
            'assets',
            'depreciations',
            'assetAccounts',
            'expenseAccounts',
            'summary',
            'dueAssetsCount',
            'status',
            'search',
            'month',
            'fromDate',
            'toDate'
        ));
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
            'depreciation_frequency' => 'required|in:monthly,quarterly,yearly',
            'notes' => 'nullable|string|max:1000',
        ]);

        $companyId = Auth::user()?->company_id ?? session('current_tenant_id');
        $activeBranch = $this->getActiveBranchContext();
        $cost = round((float) $validated['cost'], 2);
        $nextDepreciationOn = Carbon::parse($validated['acquired_on'])
            ->addMonthsNoOverflow($this->frequencyMonths($validated['depreciation_frequency']))
            ->toDateString();

        $asset = FixedAsset::create([
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
            'depreciation_frequency' => $validated['depreciation_frequency'],
            'status' => 'active',
            'accumulated_depreciation' => 0,
            'book_value' => $cost,
            'next_depreciation_on' => $nextDepreciationOn,
            'notes' => $validated['notes'] ?? null,
        ]);

        LedgerService::postFixedAssetAcquisition($asset);

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

        $result = $this->depreciationService->runDueForAsset($asset, $validated['run_date'] ?? null, Auth::id());
        $level = ((int) $result['posted']) > 0 ? 'success' : 'info';

        return redirect()
            ->route('finance.fixed-assets.index')
            ->with($level, $result['message'] . (((int) $result['posted']) > 0 ? ' Total: ₦' . number_format((float) $result['amount'], 2) : ''));
    }

    public function depreciateDue(Request $request)
    {
        $validated = $request->validate([
            'run_date' => 'nullable|date',
        ]);

        $assets = $this->scopeAssetQuery()
            ->where('status', 'active')
            ->where(function ($query) use ($validated) {
                $asOf = $validated['run_date'] ?? now()->toDateString();
                $query->whereNull('next_depreciation_on')
                    ->orWhereDate('next_depreciation_on', '<=', $asOf);
            })
            ->get();

        $posted = 0;
        $amount = 0.0;
        foreach ($assets as $asset) {
            $result = $this->depreciationService->runDueForAsset($asset, $validated['run_date'] ?? null, Auth::id());
            $posted += (int) $result['posted'];
            $amount += (float) $result['amount'];
        }

        return redirect()
            ->route('finance.fixed-assets.index')
            ->with($posted > 0 ? 'success' : 'info', $posted > 0
                ? 'Posted ' . $posted . ' due depreciation period(s). Total: ₦' . number_format($amount, 2)
                : 'No asset depreciation is due yet.');
    }

    private function scopeAssetQuery()
    {
        $query = FixedAsset::query();
        $this->applyTenantScope($query, 'fixed_assets');
        $this->applyBranchScope($query, 'fixed_assets');

        return $query;
    }

    private function frequencyMonths(string $frequency): int
    {
        return match ($frequency) {
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };
    }
}
