<?php

namespace App\Http\Controllers;

use App\Models\CollectionFollowUp;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CollectionFollowUpController extends Controller
{
    private function applyTenantScope($query, string $table)
    {
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'created_by')) {
            $query->where("{$table}.created_by", $userId);
        }

        return $query;
    }

    private function activeBranchContext(): array
    {
        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
    }

    private function applyBranchScope($query, string $table)
    {
        $activeBranch = $this->activeBranchContext();
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

    public function index(Request $request): View
    {
        $status = strtolower(trim((string) $request->string('status')));
        $partyType = strtolower(trim((string) $request->string('party_type')));
        $search = trim((string) $request->string('q'));
        $month = trim((string) $request->string('month'));
        $fromDate = trim((string) $request->string('from_date'));
        $toDate = trim((string) $request->string('to_date'));

        $query = CollectionFollowUp::query()
            ->with(['customer', 'supplier', 'creator', 'completer'])
            ->latest('due_date')
            ->latest('id');
        $this->applyTenantScope($query, 'collection_follow_ups');
        $this->applyBranchScope($query, 'collection_follow_ups');

        if (in_array($status, ['open', 'completed', 'cancelled'], true)) {
            $query->where('status', $status);
        }
        if (in_array($partyType, ['customer', 'supplier'], true)) {
            $query->where('party_type', $partyType);
        }
        if ($search !== '') {
            $customerHasEmail = Schema::hasTable('customers') && Schema::hasColumn('customers', 'email');
            $supplierHasEmail = Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'email');
            $query->where(function ($sub) use ($search, $customerHasEmail, $supplierHasEmail) {
                $sub->where('title', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($q) use ($search, $customerHasEmail) {
                        $q->where('customer_name', 'like', '%' . $search . '%');
                        if ($customerHasEmail) {
                            $q->orWhere('email', 'like', '%' . $search . '%');
                        }
                    })
                    ->orWhereHas('supplier', function ($q) use ($search, $supplierHasEmail) {
                        $q->where('name', 'like', '%' . $search . '%');
                        if ($supplierHasEmail) {
                            $q->orWhere('email', 'like', '%' . $search . '%');
                        }
                    });
            });
        }

        if ($month !== '') {
            $query->whereBetween('due_date', [
                now()->parse($month . '-01')->startOfMonth()->toDateString(),
                now()->parse($month . '-01')->endOfMonth()->toDateString(),
            ]);
        } else {
            if ($fromDate !== '') {
                $query->whereDate('due_date', '>=', $fromDate);
            }
            if ($toDate !== '') {
                $query->whereDate('due_date', '<=', $toDate);
            }
        }

        $followUps = $query->paginate(15)->appends($request->query());

        $customers = Customer::query()->orderBy('customer_name');
        $this->applyTenantScope($customers, 'customers');
        $this->applyBranchScope($customers, 'customers');
        $customers = $customers->limit(100)->get(['id', 'customer_name']);

        $suppliers = Supplier::query()->orderBy(Schema::hasColumn('suppliers', 'name') ? 'name' : 'id');
        $this->applyTenantScope($suppliers, 'suppliers');
        $this->applyBranchScope($suppliers, 'suppliers');
        $suppliers = $suppliers->limit(100)->get(['id', Schema::hasColumn('suppliers', 'name') ? 'name' : 'supplier_name']);

        $statsQuery = CollectionFollowUp::query();
        $this->applyTenantScope($statsQuery, 'collection_follow_ups');
        $this->applyBranchScope($statsQuery, 'collection_follow_ups');
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'open' => (clone $statsQuery)->where('status', 'open')->count(),
            'due_today' => (clone $statsQuery)->whereDate('due_date', now()->toDateString())->where('status', 'open')->count(),
            'overdue' => (clone $statsQuery)->whereDate('due_date', '<', now()->toDateString())->where('status', 'open')->count(),
        ];

        return view('Finance.follow-ups', compact(
            'followUps',
            'customers',
            'suppliers',
            'stats',
            'status',
            'partyType',
            'search',
            'month',
            'fromDate',
            'toDate'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'party_type' => ['required', 'in:customer,supplier'],
            'customer_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'due_date' => ['required', 'date'],
        ]);

        if ($data['party_type'] === 'customer' && empty($data['customer_id'])) {
            return back()->withInput()->with('error', 'Select a customer for this follow-up.');
        }
        if ($data['party_type'] === 'supplier' && empty($data['supplier_id'])) {
            return back()->withInput()->with('error', 'Select a supplier for this follow-up.');
        }

        $activeBranch = $this->activeBranchContext();

        CollectionFollowUp::create([
            'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => $activeBranch['id'],
            'branch_name' => $activeBranch['name'],
            'party_type' => $data['party_type'],
            'customer_id' => $data['party_type'] === 'customer' ? (int) $data['customer_id'] : null,
            'supplier_id' => $data['party_type'] === 'supplier' ? (int) $data['supplier_id'] : null,
            'title' => $data['title'],
            'notes' => $data['notes'] ?? null,
            'status' => 'open',
            'priority' => $data['priority'],
            'due_date' => $data['due_date'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('finance.follow-ups.index')->with('success', 'Follow-up scheduled successfully.');
    }

    public function complete(CollectionFollowUp $collectionFollowUp): RedirectResponse
    {
        $query = CollectionFollowUp::query()->whereKey($collectionFollowUp->id);
        $this->applyTenantScope($query, 'collection_follow_ups');
        $this->applyBranchScope($query, 'collection_follow_ups');
        $followUp = $query->firstOrFail();

        $followUp->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        return redirect()->route('finance.follow-ups.index')->with('success', 'Follow-up marked as completed.');
    }
}
