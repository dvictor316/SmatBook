<?php

namespace App\Http\Controllers;

use App\Models\TaxFiling;
use App\Models\TaxFilingLine;
use App\Models\TaxJurisdiction;
use App\Support\TaxAuditService;
use App\Support\TaxReturnPreparationService;
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

        $filings = TaxFiling::with(['jurisdiction', 'lines'])
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->latest()
            ->paginate(20);
        return view('compliance.tax-filings.index', compact('filings'));
    }

    public function create()
    {
        if (!$this->taxTablesReady()) {
            return redirect()->route('compliance.tax-filings.index')->with('error', $this->migrationMessage());
        }

        $jurisdictions = TaxJurisdiction::query()
            ->where('is_active', true)
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->orderBy('name')
            ->get();
        return view('compliance.tax-filings.create', compact('jurisdictions'));
    }

    public function store(Request $request, TaxReturnPreparationService $returnPreparationService)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $validated = $request->validate([
            'tax_jurisdiction_id' => 'required|exists:tax_jurisdictions,id',
            'name' => 'required|string|max:255',
            'filing_type' => 'required|string|max:64',
            'filing_frequency' => 'nullable|string|max:50',
            'currency_code' => 'nullable|string|size:3',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'due_date' => 'nullable|date',
            'total_taxable' => 'nullable|numeric|min:0',
            'total_tax' => 'nullable|numeric|min:0',
            'tax_due' => 'nullable|numeric|min:0',
            'tax_credit' => 'nullable|numeric|min:0',
            'tax_refund' => 'nullable|numeric|min:0',
            'adjustments_total' => 'nullable|numeric|min:0',
            'credits_total' => 'nullable|numeric|min:0',
        ]);

        $jurisdiction = TaxJurisdiction::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->findOrFail($validated['tax_jurisdiction_id']);

        $preview = $returnPreparationService->prepare(
            $validated['period_start'],
            $validated['period_end'],
            [
                'filing_type' => $validated['filing_type'],
                'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
                'user_id' => auth()->id(),
                'branch_scope' => session('active_branch_scope', 'branch'),
                'branch_id' => session('active_branch_id'),
                'branch_name' => session('active_branch_name'),
                'currency_code' => $validated['currency_code'] ?? $jurisdiction->currency_code ?? 'NGN',
            ]
        );

        $payload = array_merge($validated, $this->tenantPayload('tax_filings'), [
            'country_code' => $jurisdiction->country_code,
            'currency_code' => $validated['currency_code'] ?? $jurisdiction->currency_code,
            'filing_frequency' => $validated['filing_frequency'] ?? $jurisdiction->filing_frequency,
            'status' => 'draft',
            'branch_scope' => session('active_branch_scope', 'branch'),
            'total_taxable' => $validated['total_taxable'] ?? $preview['total_taxable'],
            'total_tax' => $validated['total_tax'] ?? $preview['total_tax'],
            'tax_due' => $validated['tax_due'] ?? $preview['tax_due'],
            'tax_credit' => $validated['tax_credit'] ?? $preview['tax_credit'],
            'tax_refund' => $validated['tax_refund'] ?? $preview['tax_refund'],
            'adjustments_total' => $validated['adjustments_total'] ?? $preview['adjustments_total'],
            'credits_total' => $validated['credits_total'] ?? $preview['credits_total'],
            'metadata' => array_merge($preview, ['prepared_from_transactions' => true]),
        ]);

        DB::beginTransaction();

        try {
            $filing = TaxFiling::create($payload);

            if (Schema::hasTable('tax_filing_lines')) {
                foreach ($preview['lines'] ?? [] as $line) {
                    TaxFilingLine::create([
                        'tax_filing_id' => $filing->id,
                        'line_key' => $line['line_key'],
                        'label' => $line['label'],
                        'tax_type' => $line['tax_type'] ?? null,
                        'taxable_base' => $line['taxable_base'] ?? 0,
                        'tax_amount' => $line['tax_amount'] ?? 0,
                        'adjustment_amount' => $line['adjustment_amount'] ?? 0,
                        'credit_amount' => $line['credit_amount'] ?? 0,
                        'net_amount' => $line['net_amount'] ?? 0,
                        'metadata' => $line['metadata'] ?? null,
                    ]);
                }
            }

            TaxAuditService::record($filing, 'tax_filing.created', null, $filing->toArray());
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('compliance.tax-filings.index')->with('success', 'Tax filing created.');
    }

    public function submit($id)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::findOrFail($id);

        $before = $filing->toArray();
        $filing->update([
            'status' => 'submitted',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'reference_no' => $filing->reference_no ?: ('TXF-' . str_pad((string) $filing->id, 6, '0', STR_PAD_LEFT)),
        ]);
        TaxAuditService::record($filing, 'tax_filing.submitted', $before, $filing->fresh()->toArray());

        return back()->with('success', 'Filing submitted.');
    }

    public function edit($id)
    {
        if (!$this->taxTablesReady()) {
            return redirect()->route('compliance.tax-filings.index')->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::query()
            ->with('lines')
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->findOrFail($id);
        $jurisdictions = TaxJurisdiction::query()
            ->where('is_active', true)
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->orderBy('name')
            ->get();

        return view('compliance.tax-filings.edit', compact('filing', 'jurisdictions'));
    }

    public function update(Request $request, $id, TaxReturnPreparationService $returnPreparationService)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::query()
            ->with('lines')
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->findOrFail($id);

        if ($filing->status === 'submitted') {
            return back()->with('error', 'Submitted filings cannot be edited.');
        }

        $validated = $request->validate([
            'tax_jurisdiction_id' => 'required|exists:tax_jurisdictions,id',
            'name' => 'required|string|max:255',
            'filing_type' => 'required|string|max:64',
            'filing_frequency' => 'nullable|string|max:50',
            'currency_code' => 'nullable|string|size:3',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'due_date' => 'nullable|date',
            'total_taxable' => 'nullable|numeric|min:0',
            'total_tax' => 'nullable|numeric|min:0',
            'tax_due' => 'nullable|numeric|min:0',
            'tax_credit' => 'nullable|numeric|min:0',
            'tax_refund' => 'nullable|numeric|min:0',
            'adjustments_total' => 'nullable|numeric|min:0',
            'credits_total' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,submitted',
        ]);

        $jurisdiction = TaxJurisdiction::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->findOrFail($validated['tax_jurisdiction_id']);

        $preview = $returnPreparationService->prepare(
            $validated['period_start'],
            $validated['period_end'],
            [
                'filing_type' => $validated['filing_type'],
                'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
                'user_id' => auth()->id(),
                'branch_scope' => session('active_branch_scope', 'branch'),
                'branch_id' => session('active_branch_id'),
                'branch_name' => session('active_branch_name'),
                'currency_code' => $validated['currency_code'] ?? $jurisdiction->currency_code ?? 'NGN',
            ]
        );

        $before = $filing->toArray();

        DB::beginTransaction();

        try {
            $filing->update([
            'tax_jurisdiction_id' => $validated['tax_jurisdiction_id'],
            'name' => $validated['name'],
            'filing_type' => $validated['filing_type'],
            'filing_frequency' => $validated['filing_frequency'] ?? $jurisdiction->filing_frequency,
            'currency_code' => $validated['currency_code'] ?? $jurisdiction->currency_code,
            'country_code' => $jurisdiction->country_code,
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'due_date' => $validated['due_date'] ?? null,
            'total_taxable' => $validated['total_taxable'] ?? $preview['total_taxable'],
            'total_tax' => $validated['total_tax'] ?? $preview['total_tax'],
            'tax_due' => $validated['tax_due'] ?? $preview['tax_due'],
            'tax_credit' => $validated['tax_credit'] ?? $preview['tax_credit'],
            'tax_refund' => $validated['tax_refund'] ?? $preview['tax_refund'],
            'adjustments_total' => $validated['adjustments_total'] ?? $preview['adjustments_total'],
            'credits_total' => $validated['credits_total'] ?? $preview['credits_total'],
            'status' => $validated['status'] ?? 'draft',
            'metadata' => array_merge($preview, ['prepared_from_transactions' => true]),
            ]);

            if (Schema::hasTable('tax_filing_lines')) {
                TaxFilingLine::query()->where('tax_filing_id', $filing->id)->delete();
                foreach ($preview['lines'] ?? [] as $line) {
                    TaxFilingLine::create([
                        'tax_filing_id' => $filing->id,
                        'line_key' => $line['line_key'],
                        'label' => $line['label'],
                        'tax_type' => $line['tax_type'] ?? null,
                        'taxable_base' => $line['taxable_base'] ?? 0,
                        'tax_amount' => $line['tax_amount'] ?? 0,
                        'adjustment_amount' => $line['adjustment_amount'] ?? 0,
                        'credit_amount' => $line['credit_amount'] ?? 0,
                        'net_amount' => $line['net_amount'] ?? 0,
                        'metadata' => $line['metadata'] ?? null,
                    ]);
                }
            }

            TaxAuditService::record($filing, 'tax_filing.updated', $before, $filing->fresh()->toArray());
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('compliance.tax-filings.index')->with('success', 'Tax filing updated.');
    }

    public function destroy($id)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->findOrFail($id);
        $filing->delete();
        TaxAuditService::record($filing, 'tax_filing.deleted', $filing->toArray(), null);

        return back()->with('success', 'Tax filing deleted.');
    }

    public function previewTotals(Request $request, TaxReturnPreparationService $returnPreparationService)
    {
        if (!$this->taxTablesReady()) {
            return response()->json(['message' => $this->migrationMessage()], 422);
        }

        $validated = $request->validate([
            'tax_jurisdiction_id' => 'nullable|exists:tax_jurisdictions,id',
            'filing_type' => 'nullable|string|max:64',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $jurisdiction = !empty($validated['tax_jurisdiction_id'])
            ? TaxJurisdiction::query()
                ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
                ->find($validated['tax_jurisdiction_id'])
            : null;

        return response()->json($returnPreparationService->prepare(
            $validated['period_start'],
            $validated['period_end'],
            [
                'filing_type' => $validated['filing_type'] ?? 'vat',
                'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
                'user_id' => auth()->id(),
                'branch_scope' => session('active_branch_scope', 'branch'),
                'branch_id' => session('active_branch_id'),
                'branch_name' => session('active_branch_name'),
                'currency_code' => $jurisdiction?->currency_code ?? 'NGN',
            ]
        ));
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

    private function applyTaxScope($query, string $table): void
    {
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);
        $branchScope = (string) session('active_branch_scope', 'branch');
        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'created_by')) {
            $query->where("{$table}.created_by", $userId);
        }

        if ($branchScope === 'all' || ($branchId === '' && $branchName === '')) {
            return;
        }

        $query->where(function ($sub) use ($table, $branchId, $branchName) {
            $matched = false;

            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $sub->where("{$table}.branch_id", $branchId);
                $matched = true;
            }
            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $method = $matched ? 'orWhere' : 'where';
                $sub->{$method}("{$table}.branch_name", $branchName);
            }
        });
    }

    private function tenantPayload(string $table): array
    {
        $payload = [];
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);
        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $payload['company_id'] = $companyId;
        }
        if ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $payload['user_id'] = $userId;
        }
        if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
            $payload['branch_id'] = $branchId;
        }
        if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
            $payload['branch_name'] = $branchName;
        }

        return $payload;
    }
}
