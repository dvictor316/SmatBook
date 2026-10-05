<?php

namespace App\Http\Controllers;

use App\Models\TaxAuthorityConnection;
use App\Models\TaxFiling;
use App\Models\TaxFilingLine;
use App\Models\TaxFilingSubmission;
use App\Models\TaxJurisdiction;
use App\Support\TaxAuditService;
use App\Support\TaxAuthorityFilingService;
use App\Support\TaxFilingCatalog;
use App\Support\TaxReturnPreparationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TaxFilingController extends Controller
{
    public function index()
    {
        if (! $this->taxTablesReady()) {
            return view('compliance.tax-filings.index', [
                'filings' => collect(),
                'taxSetupMissing' => true,
            ]);
        }

        $filings = TaxFiling::with(['jurisdiction', 'lines', 'submissions' => fn ($query) => $query->latest()])
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->latest()
            ->paginate(20);

        return view('compliance.tax-filings.index', compact('filings'));
    }

    public function create(TaxFilingCatalog $filingCatalog)
    {
        if (! $this->taxTablesReady()) {
            return redirect()->route('compliance.tax-filings.index')->with('error', $this->migrationMessage());
        }

        $jurisdictions = TaxJurisdiction::query()
            ->with(['taxCodes', 'withholdingRules', 'accountMappings'])
            ->where('is_active', true)
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->orderBy('name')
            ->get();

        $filingOptionsByJurisdiction = $jurisdictions->mapWithKeys(
            fn (TaxJurisdiction $jurisdiction) => [$jurisdiction->id => $filingCatalog->optionsFor($jurisdiction)]
        );
        $frequencyLabels = $filingCatalog->frequencyLabels();

        return view('compliance.tax-filings.create', compact(
            'jurisdictions',
            'filingOptionsByJurisdiction',
            'frequencyLabels'
        ));
    }

    public function store(
        Request $request,
        TaxReturnPreparationService $returnPreparationService,
        TaxFilingCatalog $filingCatalog
    ) {
        if (! $this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $validated = $request->validate(array_merge([
            'tax_jurisdiction_id' => 'required|exists:tax_jurisdictions,id',
            'name' => 'required|string|max:255',
            'filing_type' => 'required|in:vat,sales_tax,withholding,paye,corporate_income_tax',
            'filing_frequency' => 'required|in:weekly,biweekly,monthly,bimonthly,quarterly,semiannual,annual',
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
        ], $this->citValidationRules()));

        $jurisdiction = TaxJurisdiction::query()
            ->with(['taxCodes', 'withholdingRules', 'accountMappings'])
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->findOrFail($validated['tax_jurisdiction_id']);
        $filingOption = $filingCatalog->optionsFor($jurisdiction)[$validated['filing_type']] ?? null;
        if (! $filingOption) {
            throw ValidationException::withMessages([
                'filing_type' => 'This return type is not configured for the selected jurisdiction.',
            ]);
        }
        if (! in_array($validated['filing_frequency'], $filingOption['frequencies'], true)) {
            throw ValidationException::withMessages([
                'filing_frequency' => 'Select a filing frequency configured for this jurisdiction and return type.',
            ]);
        }
        $validated['currency_code'] = $filingOption['currency'];
        if (empty($validated['due_date'])) {
            $periodEnd = Carbon::parse($validated['period_end']);
            if ($filingOption['deadline_months'] > 0) {
                $validated['due_date'] = $periodEnd->addMonthsNoOverflow($filingOption['deadline_months'])->toDateString();
            } elseif ($filingOption['deadline_days'] > 0) {
                $validated['due_date'] = $periodEnd->addDays($filingOption['deadline_days'])->toDateString();
            }
        }

        $preview = $returnPreparationService->prepare(
            $validated['period_start'],
            $validated['period_end'],
            array_merge([
                'filing_type' => $validated['filing_type'],
                'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
                'user_id' => auth()->id(),
                'branch_scope' => session('active_branch_scope', 'branch'),
                'branch_id' => session('active_branch_id'),
                'branch_name' => session('active_branch_name'),
                'currency_code' => $validated['currency_code'] ?? $jurisdiction->currency_code ?? 'NGN',
            ], $this->citContext($validated))
        );

        $payload = array_merge(array_diff_key($validated, $this->citValidationRules()), $this->tenantPayload('tax_filings'), [
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
        return $this->approve($id);
    }

    public function approve($id)
    {
        if (! $this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->findOrFail($id);

        if (! in_array($filing->status, ['draft', 'rejected', 'failed', 'check_failed'], true)) {
            return back()->with('error', 'Only a draft or returned filing can be approved.');
        }

        $before = $filing->toArray();
        $filing->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'reference_no' => $filing->reference_no ?: ('TXF-'.str_pad((string) $filing->id, 6, '0', STR_PAD_LEFT)),
        ]);
        TaxAuditService::record($filing, 'tax_filing.approved', $before, $filing->fresh()->toArray());

        return back()->with('success', 'Filing approved and locked for authority submission or manual filing.');
    }

    public function transmit($id, TaxAuthorityFilingService $filingService)
    {
        $filing = TaxFiling::query()->with('lines')
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->findOrFail($id);
        $connection = TaxAuthorityConnection::query()
            ->where('provider', 'nrs')->where('country_code', 'NGA')
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_authority_connections'))
            ->latest()->first();
        if (! $connection) {
            return back()->with('error', 'Configure the NRS authority connection in Tax Center first.');
        }

        try {
            $submission = $filingService->submit($filing, $connection);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        TaxAuditService::record($filing, 'tax_filing.authority_transmission', null, [
            'submission_id' => $submission->id,
            'status' => $submission->status,
            'authority_reference' => $submission->authority_reference,
        ]);

        return back()->with(
            $submission->error_message ? 'error' : 'success',
            $submission->error_message ?: 'Filing transmitted. Authority status: '.ucfirst($submission->status).'.'
        );
    }

    public function syncSubmission($id, $submissionId, TaxAuthorityFilingService $filingService)
    {
        $filing = TaxFiling::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->findOrFail($id);
        $submission = TaxFilingSubmission::query()
            ->where('tax_filing_id', $filing->id)
            ->findOrFail($submissionId);

        try {
            $submission = $filingService->sync($submission);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Authority status refreshed: '.ucfirst($submission->status).'.');
    }

    public function recordManual(Request $request, $id)
    {
        $validated = $request->validate([
            'authority_reference' => 'required|string|max:255',
            'filed_at' => 'required|date',
        ]);
        $filing = TaxFiling::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->findOrFail($id);
        if (! $filing->approved_at) {
            return back()->with('error', 'Approve the filing before recording an external submission.');
        }
        $submission = TaxFilingSubmission::firstOrCreate(
            ['idempotency_key' => 'manual-'.hash('sha256', $filing->id.'|'.$validated['authority_reference'])],
            [
                'tax_filing_id' => $filing->id,
                'company_id' => $filing->company_id,
                'created_by' => auth()->id(),
                'provider' => 'manual',
                'environment' => 'production',
                'status' => 'accepted',
                'authority_reference' => $validated['authority_reference'],
                'request_payload' => ['recorded_manually' => true],
                'response_payload' => ['filed_at' => $validated['filed_at']],
                'submitted_at' => $validated['filed_at'],
                'acknowledged_at' => now(),
            ]
        );
        $filing->update([
            'status' => 'accepted',
            'reference_no' => $validated['authority_reference'],
            'submitted_by' => auth()->id(),
            'submitted_at' => $validated['filed_at'],
        ]);
        TaxAuditService::record($filing, 'tax_filing.manual_submission_recorded', null, $submission->toArray());

        return back()->with('success', 'External authority filing reference recorded.');
    }

    public function export($id, string $format)
    {
        abort_unless(in_array($format, ['json', 'csv', 'pdf'], true), 404);
        $filing = TaxFiling::query()->with(['jurisdiction', 'lines', 'submissions'])
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->findOrFail($id);
        $package = [
            'generated_at' => now()->toIso8601String(),
            'filing' => $filing->only([
                'id', 'name', 'filing_type', 'country_code', 'currency_code', 'filing_frequency',
                'period_start', 'period_end', 'due_date', 'status', 'total_taxable', 'total_tax',
                'tax_due', 'tax_credit', 'tax_refund', 'adjustments_total', 'reference_no',
                'approved_by', 'approved_at', 'submitted_by', 'submitted_at',
            ]),
            'jurisdiction' => $filing->jurisdiction?->only(['name', 'country_code', 'region', 'tax_authority_name']),
            'lines' => $filing->lines->toArray(),
            'submissions' => $filing->submissions->makeHidden(['request_payload', 'response_payload'])->toArray(),
        ];
        $package['integrity_hash'] = hash('sha256', json_encode($package, JSON_UNESCAPED_SLASHES));
        $filename = 'tax-filing-'.$filing->id.'-'.now()->format('Ymd-His');

        if ($format === 'json') {
            return response(json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.json"',
            ]);
        }
        if ($format === 'csv') {
            return response()->streamDownload(function () use ($filing, $package) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Filing', $filing->name]);
                fputcsv($handle, ['Period', $filing->period_start?->format('Y-m-d'), $filing->period_end?->format('Y-m-d')]);
                fputcsv($handle, ['Status', $filing->status, 'Reference', $filing->reference_no]);
                fputcsv($handle, []);
                fputcsv($handle, ['Line', 'Tax Type', 'Taxable Base', 'Tax Amount', 'Credit', 'Net Amount']);
                foreach ($filing->lines as $line) {
                    fputcsv($handle, [$line->label, $line->tax_type, $line->taxable_base, $line->tax_amount, $line->credit_amount, $line->net_amount]);
                }
                fputcsv($handle, []);
                fputcsv($handle, ['Integrity Hash', $package['integrity_hash']]);
                fclose($handle);
            }, $filename.'.csv', ['Content-Type' => 'text/csv']);
        }

        return Pdf::loadView('compliance.tax-filings.workpaper', compact('filing', 'package'))
            ->setPaper('a4')->download($filename.'.pdf');
    }

    public function edit($id)
    {
        if (! $this->taxTablesReady()) {
            return redirect()->route('compliance.tax-filings.index')->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::query()
            ->with('lines')
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->findOrFail($id);
        if (! in_array($filing->status, ['draft', 'rejected', 'failed', 'check_failed'], true)) {
            return redirect()->route('compliance.tax-filings.index')
                ->with('error', 'Approved or authority-filed returns are locked and cannot be edited.');
        }
        $jurisdictions = TaxJurisdiction::query()
            ->where('is_active', true)
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->orderBy('name')
            ->get();

        return view('compliance.tax-filings.edit', compact('filing', 'jurisdictions'));
    }

    public function update(Request $request, $id, TaxReturnPreparationService $returnPreparationService)
    {
        if (! $this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::query()
            ->with('lines')
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->findOrFail($id);

        if (! in_array($filing->status, ['draft', 'rejected', 'failed', 'check_failed'], true)) {
            return back()->with('error', 'Approved or authority-filed returns are locked and cannot be edited.');
        }

        $validated = $request->validate(array_merge([
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
            'status' => 'nullable|in:draft,rejected,failed,check_failed',
        ], $this->citValidationRules()));

        $jurisdiction = TaxJurisdiction::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->findOrFail($validated['tax_jurisdiction_id']);

        $preview = $returnPreparationService->prepare(
            $validated['period_start'],
            $validated['period_end'],
            array_merge([
                'filing_type' => $validated['filing_type'],
                'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
                'user_id' => auth()->id(),
                'branch_scope' => session('active_branch_scope', 'branch'),
                'branch_id' => session('active_branch_id'),
                'branch_name' => session('active_branch_name'),
                'currency_code' => $validated['currency_code'] ?? $jurisdiction->currency_code ?? 'NGN',
            ], $this->citContext($validated))
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
        if (! $this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $filing = TaxFiling::query()
            ->withCount('submissions')
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_filings'))
            ->findOrFail($id);
        if ($filing->status !== 'draft' || $filing->submissions_count > 0) {
            return back()->with('error', 'Only draft filings without submission history can be deleted.');
        }
        $filing->delete();
        TaxAuditService::record($filing, 'tax_filing.deleted', $filing->toArray(), null);

        return back()->with('success', 'Tax filing deleted.');
    }

    public function previewTotals(Request $request, TaxReturnPreparationService $returnPreparationService)
    {
        if (! $this->taxTablesReady()) {
            return response()->json(['message' => $this->migrationMessage()], 422);
        }

        $validated = $request->validate(array_merge([
            'tax_jurisdiction_id' => 'nullable|exists:tax_jurisdictions,id',
            'filing_type' => 'nullable|string|max:64',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ], $this->citValidationRules()));

        $jurisdiction = ! empty($validated['tax_jurisdiction_id'])
            ? TaxJurisdiction::query()
                ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
                ->find($validated['tax_jurisdiction_id'])
            : null;

        return response()->json($returnPreparationService->prepare(
            $validated['period_start'],
            $validated['period_end'],
            array_merge([
                'filing_type' => $validated['filing_type'] ?? 'vat',
                'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
                'user_id' => auth()->id(),
                'branch_scope' => session('active_branch_scope', 'branch'),
                'branch_id' => session('active_branch_id'),
                'branch_name' => session('active_branch_name'),
                'currency_code' => $jurisdiction?->currency_code ?? 'NGN',
            ], $this->citContext($validated))
        ));
    }

    private function citValidationRules(): array
    {
        return [
            'accounting_profit' => 'nullable|numeric|min:0',
            'disallowable_expenses' => 'nullable|numeric|min:0',
            'loss_relief' => 'nullable|numeric|min:0',
            'capital_allowances' => 'nullable|numeric|min:0',
            'cit_credits' => 'nullable|numeric|min:0',
            'cit_rate' => 'nullable|numeric|min:0|max:100',
            'development_levy_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }

    private function citContext(array $validated): array
    {
        return collect($this->citValidationRules())
            ->keys()
            ->mapWithKeys(fn (string $key) => [$key => $validated[$key] ?? null])
            ->all();
    }

    private function taxTablesReady(): bool
    {
        return Schema::hasTable('tax_jurisdictions')
            && Schema::hasTable('tax_codes')
            && Schema::hasTable('withholding_rules')
            && Schema::hasTable('tax_filings')
            && Schema::hasTable('tax_authority_connections')
            && Schema::hasTable('tax_filing_submissions');
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
