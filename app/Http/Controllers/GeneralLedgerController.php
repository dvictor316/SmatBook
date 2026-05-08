<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class GeneralLedgerController extends Controller
{
    private function activeBranchContext(Request $request): array
    {
        $branchScope = strtolower(trim((string) $request->get('branch_scope', '')));
        $requestBranchId = trim((string) $request->get('branch_id', ''));
        $allBranches = $request->boolean('all_branches')
            || $branchScope === 'all'
            || strtolower($requestBranchId) === 'all'
            || session('active_branch_scope') === 'all';

        if ($allBranches) {
            $request->session()->put('active_branch_scope', 'all');

            return [
                'id' => null,
                'name' => 'All Branches',
                'scope' => 'all',
            ];
        }

        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        if ($requestBranchId !== '') {
            $branchId = $requestBranchId;
            $branchName = '';
        }

        if (($branchId === '' || $branchName === '') && Schema::hasTable('settings')) {
            $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
            if ($companyId > 0) {
                $key = 'branches_json_company_' . $companyId;
                $raw = (string) (DB::table('settings')->where('key', $key)->value('value') ?? '');
                $branches = json_decode($raw, true) ?: [];

                if ($branchId !== '') {
                    $match = collect($branches)->firstWhere('id', $branchId);
                    $branchName = trim((string) ($match['name'] ?? $branchName));
                } else {
                    $first = collect($branches)->first();
                    $branchId = trim((string) ($first['id'] ?? $branchId));
                    $branchName = trim((string) ($first['name'] ?? $branchName));
                }
            }
        }

        if ($branchId !== '') {
            $request->session()->put('active_branch_id', $branchId);
        }
        if ($branchName !== '') {
            $request->session()->put('active_branch_name', $branchName);
        }
        $request->session()->put('active_branch_scope', 'branch');

        return [
            'id' => $branchId !== '' ? $branchId : null,
            'name' => $branchName !== '' ? $branchName : null,
            'scope' => 'branch',
        ];
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

    private function applyBranchScope($query, string $table, array $activeBranch)
    {
        if (($activeBranch['scope'] ?? 'branch') === 'all') {
            return $query;
        }

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
        $activeBranch = $this->activeBranchContext($request);
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return view('Reports.Reports.general-ledger', [
                'message' => 'Accounting tables are missing. Run migrations to enable General Ledger.',
                'entries' => collect(),
                'accounts' => collect(),
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
                'totals' => ['debit' => 0, 'credit' => 0],
                'selectedAccountId' => null,
                'search' => '',
            ]);
        }

        $accountsQuery = Account::query()->orderBy('code')->orderBy('name');
        $this->applyTenantScope($accountsQuery, 'accounts');
        $this->applyBranchScope($accountsQuery, 'accounts', $activeBranch);
        $accounts = $accountsQuery->get(['id', 'code', 'name']);
        $activeAccountIds = $accounts->pluck('id')->map(fn ($id) => (int) $id)->all();

        $query = Transaction::query()->with('account')
            ->whereBetween('transaction_date', [$startDate, $endDate]);
        $this->applyTenantScope($query, 'transactions');
        $this->applyBranchScope($query, 'transactions', $activeBranch);

        if (!empty($activeAccountIds)) {
            $query->whereIn('account_id', $activeAccountIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        $selectedAccountId = $request->input('account_id');
        if ($selectedAccountId) {
            $query->where('account_id', $selectedAccountId);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhereHas('account', function ($a) use ($search) {
                        $a->where('name', 'like', '%' . $search . '%')
                            ->orWhere('code', 'like', '%' . $search . '%');
                    });
            });
        }

        $user = Auth::user();

        if ($user && Schema::hasColumn('transactions', 'user_id')) {
            $userIds = $this->companyUserIds($user);
            $query->whereIn('user_id', $userIds);
        }

        $entries = $query->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(40)
            ->withQueryString();

        $totals = [
            'debit' => (float) (clone $query)->sum('debit'),
            'credit' => (float) (clone $query)->sum('credit'),
        ];

        return view('Reports.Reports.general-ledger', [
            'entries' => $entries,
            'accounts' => $accounts,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'totals' => $totals,
            'selectedAccountId' => $selectedAccountId,
            'search' => $search,
            'activeBranch' => $activeBranch,
        ]);
    }

    private function companyUserIds($user): array
    {
        $ids = [(int) $user->id];

        if (!empty($user->company_id) && Schema::hasColumn('users', 'company_id')) {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id')->toArray();
            $ids = array_merge($ids, $companyUserIds);
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
