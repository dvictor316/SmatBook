<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class GeneralLedgerController extends Controller
{
    public function index(Request $request)
    {
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

        $accounts = Account::orderBy('code')->orderBy('name')->get(['id', 'code', 'name']);

        $query = Transaction::query()->with('account')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

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

        // Tenant-safe scoping for non-super-admin users
        $user = Auth::user();
        $isSuperAdmin = in_array(strtolower((string) ($user->role ?? '')), ['super_admin', 'superadmin'], true)
            || strtolower((string) ($user->email ?? '')) === 'donvictorlive@gmail.com';

        if (!$isSuperAdmin && Schema::hasColumn('transactions', 'user_id')) {
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
