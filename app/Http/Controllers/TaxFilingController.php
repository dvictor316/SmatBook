<?php

namespace App\Http\Controllers;

use App\Models\TaxFiling;
use App\Models\TaxJurisdiction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaxFilingController extends Controller
{
    public function index()
    {
        if (!$this->taxTablesReady()) {
            return view('compliance.tax-filings.index', [
                'filings' => collect(),
                'taxSetupMissing' => true,
            ]);
        }

        $filings = TaxFiling::with('jurisdiction')->latest()->paginate(20);
        return view('compliance.tax-filings.index', compact('filings'));
    }

    public function create()
    {
        if (!$this->taxTablesReady()) {
            return redirect()->route('compliance.tax-filings.index')->with('error', $this->migrationMessage());
        }

        $jurisdictions = TaxJurisdiction::where('is_active', true)->orderBy('name')->get();
        return view('compliance.tax-filings.create', compact('jurisdictions'));
    }

    public function store(Request $request)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $validated = $request->validate([
            'tax_jurisdiction_id' => 'required|exists:tax_jurisdictions,id',
            'name' => 'required|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'due_date' => 'nullable|date',
            'total_taxable' => 'nullable|numeric|min:0',
            'total_tax' => 'nullable|numeric|min:0',
        ]);

        if (!isset($validated['total_taxable']) || !isset($validated['total_tax'])) {
            $preview = $this->calculateTotals($validated['period_start'], $validated['period_end']);
            $validated['total_taxable'] = $validated['total_taxable'] ?? $preview['total_taxable'];
            $validated['total_tax'] = $validated['total_tax'] ?? $preview['total_tax'];
        }

        TaxFiling::create($validated + [
            'status' => 'draft',
            'total_taxable' => $validated['total_taxable'] ?? 0,
            'total_tax' => $validated['total_tax'] ?? 0,
        ]);

        return redirect()->route('compliance.tax-filings.index')->with('success', 'Tax filing created.');
    }

    public function submit($id)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::findOrFail($id);

        $filing->update([
            'status' => 'submitted',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'reference_no' => $filing->reference_no ?: ('TXF-' . str_pad((string) $filing->id, 6, '0', STR_PAD_LEFT)),
        ]);

        return back()->with('success', 'Filing submitted.');
    }

    public function edit($id)
    {
        if (!$this->taxTablesReady()) {
            return redirect()->route('compliance.tax-filings.index')->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::findOrFail($id);
        $jurisdictions = TaxJurisdiction::where('is_active', true)->orderBy('name')->get();

        return view('compliance.tax-filings.edit', compact('filing', 'jurisdictions'));
    }

    public function update(Request $request, $id)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::findOrFail($id);

        if ($filing->status === 'submitted') {
            return back()->with('error', 'Submitted filings cannot be edited.');
        }

        $validated = $request->validate([
            'tax_jurisdiction_id' => 'required|exists:tax_jurisdictions,id',
            'name' => 'required|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'due_date' => 'nullable|date',
            'total_taxable' => 'nullable|numeric|min:0',
            'total_tax' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,submitted',
        ]);

        $filing->update([
            'tax_jurisdiction_id' => $validated['tax_jurisdiction_id'],
            'name' => $validated['name'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'due_date' => $validated['due_date'] ?? null,
            'total_taxable' => $validated['total_taxable'] ?? $filing->total_taxable,
            'total_tax' => $validated['total_tax'] ?? $filing->total_tax,
            'status' => $validated['status'] ?? 'draft',
        ]);

        return redirect()->route('compliance.tax-filings.index')->with('success', 'Tax filing updated.');
    }

    public function destroy($id)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::findOrFail($id);
        $filing->delete();

        return back()->with('success', 'Tax filing deleted.');
    }

    public function previewTotals(Request $request)
    {
        if (!$this->taxTablesReady()) {
            return response()->json(['message' => $this->migrationMessage()], 422);
        }

        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        return response()->json($this->calculateTotals($validated['period_start'], $validated['period_end']));
    }

    private function calculateTotals(string $start, string $end): array
    {
        $salesTax = 0.0;
        $purchaseTax = 0.0;
        $salesTaxable = 0.0;
        $purchaseTaxable = 0.0;

        if (Schema::hasTable('sales')) {
            $salesQuery = DB::table('sales')->whereBetween(DB::raw('DATE(created_at)'), [$start, $end]);
            $this->applyTenantScope($salesQuery, 'sales');
            $this->applyBranchScope($salesQuery, 'sales');
            $salesTax = (float) $salesQuery->sum('tax');
            $salesTaxable = (float) $salesQuery->sum(DB::raw('GREATEST(total - tax, 0)'));
        }

        if (Schema::hasTable('purchases')) {
            $purchaseQuery = DB::table('purchases')->whereBetween(DB::raw('DATE(created_at)'), [$start, $end]);
            $this->applyTenantScope($purchaseQuery, 'purchases');
            $this->applyBranchScope($purchaseQuery, 'purchases');
            $purchaseTax = (float) $purchaseQuery->sum('tax_amount');
            $purchaseTaxable = (float) $purchaseQuery->sum(DB::raw('GREATEST(total_amount - tax_amount, 0)'));
        }

        return [
            'period_start' => $start,
            'period_end' => $end,
            'sales_taxable' => round($salesTaxable, 2),
            'purchase_taxable' => round($purchaseTaxable, 2),
            'total_taxable' => round($salesTaxable + $purchaseTaxable, 2),
            'sales_tax' => round($salesTax, 2),
            'purchase_tax' => round($purchaseTax, 2),
            'total_tax' => round($salesTax + $purchaseTax, 2),
        ];
    }

    private function taxTablesReady(): bool
    {
        return Schema::hasTable('tax_jurisdictions')
            && Schema::hasTable('tax_codes')
            && Schema::hasTable('withholding_rules')
            && Schema::hasTable('tax_filings');
    }

    private function migrationMessage(): string
    {
        return 'Taxation tables are missing. Run `php artisan migrate` to initialize tax modules.';
    }

    private function applyTenantScope($query, string $table): void
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
    }

    private function applyBranchScope($query, string $table): void
    {
        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        if ($branchId === '' && $branchName === '') {
            return;
        }

        $query->where(function ($sub) use ($table, $branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $sub->where("{$table}.branch_id", $branchId);
            }
            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $sub->orWhere("{$table}.branch_name", $branchName);
            }
        });
    }
}
