<?php

namespace App\Http\Controllers;

use App\Models\TaxAccountMapping;
use App\Models\TaxCode;
use App\Models\TaxJurisdiction;
use App\Models\WithholdingRule;
use App\Support\TaxAuditService;
use App\Support\TaxCountryCatalog;
use App\Support\TaxEngineBootstrapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TaxCenterController extends Controller
{
    public function index()
    {
        if (!$this->taxTablesReady()) {
            return view('compliance.tax-center.index', [
                'jurisdictions' => collect(),
                'taxCodes' => collect(),
                'withholdingRules' => collect(),
                'accountMappings' => collect(),
                'supportedCountries' => TaxCountryCatalog::supportedCountries(),
                'taxSetupMissing' => true,
            ]);
        }

        $jurisdictions = TaxJurisdiction::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->latest()
            ->get();
        $taxCodes = TaxCode::with('jurisdiction')
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_codes'))
            ->latest()
            ->limit(50)
            ->get();
        $withholdingRules = WithholdingRule::with('jurisdiction')
            ->tap(fn ($query) => $this->applyTaxScope($query, 'withholding_rules'))
            ->latest()
            ->limit(50)
            ->get();
        $accountMappings = Schema::hasTable('tax_account_mappings')
            ? TaxAccountMapping::with(['jurisdiction', 'taxCode'])
                ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_account_mappings'))
                ->latest()
                ->limit(50)
                ->get()
            : collect();

        return view('compliance.tax-center.index', [
            'jurisdictions' => $jurisdictions,
            'taxCodes' => $taxCodes,
            'withholdingRules' => $withholdingRules,
            'accountMappings' => $accountMappings,
            'supportedCountries' => TaxCountryCatalog::supportedCountries(),
        ]);
    }

    public function bootstrapDefaults(TaxEngineBootstrapService $bootstrapService)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $created = $bootstrapService->bootstrapDefaults();

        return back()->with('success', sprintf(
            'Tax defaults bootstrapped. Jurisdictions: %d, Tax codes: %d, WHT rules: %d, Account mappings: %d.',
            $created['jurisdictions'] ?? 0,
            $created['tax_codes'] ?? 0,
            $created['withholding_rules'] ?? 0,
            $created['account_mappings'] ?? 0
        ));
    }

    public function storeJurisdiction(Request $request)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|size:3',
            'region' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|size:3',
            'filing_frequency' => 'nullable|string|max:50',
            'filing_deadline_days' => 'nullable|integer|min:0|max:365',
            'tax_authority_name' => 'nullable|string|max:255',
            'tax_authority_reference' => 'nullable|string|max:255',
            'tax_authority_email' => 'nullable|email|max:255',
            'tax_authority_phone' => 'nullable|string|max:50',
            'portal_url' => 'nullable|url|max:255',
            'registration_threshold' => 'nullable|numeric|min:0',
            'is_default' => 'nullable|boolean',
        ]);

        $payload = array_merge($validated, $this->tenantPayload('tax_jurisdictions'), [
            'country_code' => strtoupper((string) $validated['country_code']),
            'currency_code' => strtoupper((string) ($validated['currency_code'] ?? '')),
            'is_active' => true,
            'is_default' => $request->boolean('is_default'),
        ]);

        $jurisdiction = TaxJurisdiction::create($payload);
        TaxAuditService::record($jurisdiction, 'tax_jurisdiction.created', null, $jurisdiction->toArray());

        return back()->with('success', 'Tax jurisdiction added.');
    }

    public function storeTaxCode(Request $request)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $validated = $request->validate([
            'tax_jurisdiction_id' => 'required|exists:tax_jurisdictions,id',
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('tax_codes', 'code')->where(function ($q) use ($request) {
                    return $q->where('tax_jurisdiction_id', $request->tax_jurisdiction_id);
                }),
            ],
            'name' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string|max:64',
            'category' => 'nullable|string|max:64',
            'calculation_method' => 'nullable|in:inclusive,exclusive',
            'is_compound' => 'nullable|boolean',
            'compound_order' => 'nullable|integer|min:0|max:100',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'filing_frequency' => 'nullable|string|max:50',
            'filing_deadline_days' => 'nullable|integer|min:0|max:365',
            'report_template' => 'nullable|string|max:255',
            'ledger_output_account_code' => 'nullable|string|max:64',
            'ledger_input_account_code' => 'nullable|string|max:64',
            'ledger_payable_account_code' => 'nullable|string|max:64',
            'ledger_receivable_account_code' => 'nullable|string|max:64',
            'ledger_expense_account_code' => 'nullable|string|max:64',
            'registration_threshold' => 'nullable|numeric|min:0',
            'supports_reverse_charge' => 'nullable|boolean',
            'is_zero_rated' => 'nullable|boolean',
            'is_exempt' => 'nullable|boolean',
            'recoverability_rate' => 'nullable|numeric|min:0|max:100',
            'applies_to' => 'nullable|array',
            'applies_to.*' => 'string|max:64',
        ]);

        $jurisdiction = TaxJurisdiction::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->findOrFail($validated['tax_jurisdiction_id']);

        $payload = array_merge($validated, $this->tenantPayload('tax_codes'), [
            'tax_jurisdiction_id' => $jurisdiction->id,
            'country_code' => $jurisdiction->country_code,
            'category' => $validated['category'] ?? 'indirect',
            'calculation_method' => $validated['calculation_method'] ?? 'exclusive',
            'is_compound' => $request->boolean('is_compound'),
            'compound_order' => (int) ($validated['compound_order'] ?? 0),
            'supports_reverse_charge' => $request->boolean('supports_reverse_charge'),
            'is_zero_rated' => $request->boolean('is_zero_rated'),
            'is_exempt' => $request->boolean('is_exempt'),
            'recoverability_rate' => (float) ($validated['recoverability_rate'] ?? 100),
            'is_active' => true,
        ]);

        $taxCode = TaxCode::create($payload);
        TaxAuditService::record($taxCode, 'tax_code.created', null, $taxCode->toArray());

        return back()->with('success', 'Tax code added.');
    }

    public function storeWithholdingRule(Request $request)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $validated = $request->validate([
            'tax_jurisdiction_id' => 'required|exists:tax_jurisdictions,id',
            'name' => 'required|string|max:255',
            'service_type' => 'nullable|string|max:255',
            'counterparty_type' => 'required|string|max:64',
            'rate' => 'required|numeric|min:0|max:100',
            'threshold_amount' => 'nullable|numeric|min:0',
            'account_code' => 'nullable|string|max:64',
            'certificate_prefix' => 'nullable|string|max:64',
            'payable_account_code' => 'nullable|string|max:64',
            'receivable_account_code' => 'nullable|string|max:64',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        $jurisdiction = TaxJurisdiction::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->findOrFail($validated['tax_jurisdiction_id']);

        $rule = WithholdingRule::create(array_merge($validated, $this->tenantPayload('withholding_rules'), [
            'country_code' => $jurisdiction->country_code,
            'is_active' => true,
        ]));
        TaxAuditService::record($rule, 'withholding_rule.created', null, $rule->toArray());

        return back()->with('success', 'Withholding rule added.');
    }

    public function updateJurisdiction(Request $request, $id)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $jurisdiction = TaxJurisdiction::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|size:3',
            'region' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|size:3',
            'filing_frequency' => 'nullable|string|max:50',
            'filing_deadline_days' => 'nullable|integer|min:0|max:365',
            'tax_authority_name' => 'nullable|string|max:255',
            'tax_authority_reference' => 'nullable|string|max:255',
            'tax_authority_email' => 'nullable|email|max:255',
            'tax_authority_phone' => 'nullable|string|max:50',
            'portal_url' => 'nullable|url|max:255',
            'registration_threshold' => 'nullable|numeric|min:0',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $before = $jurisdiction->toArray();
        $jurisdiction->update(array_merge($validated, [
            'country_code' => strtoupper((string) $validated['country_code']),
            'currency_code' => strtoupper((string) ($validated['currency_code'] ?? '')),
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
        ]));
        TaxAuditService::record($jurisdiction, 'tax_jurisdiction.updated', $before, $jurisdiction->fresh()->toArray());

        return back()->with('success', 'Jurisdiction updated.');
    }

    public function destroyJurisdiction($id)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $jurisdiction = TaxJurisdiction::findOrFail($id);
        $jurisdiction->delete();

        return back()->with('success', 'Jurisdiction deleted.');
    }

    public function updateTaxCode(Request $request, $id)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $taxCode = TaxCode::findOrFail($id);

        $validated = $request->validate([
            'tax_jurisdiction_id' => 'required|exists:tax_jurisdictions,id',
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('tax_codes', 'code')
                    ->where(function ($q) use ($request) {
                        return $q->where('tax_jurisdiction_id', $request->tax_jurisdiction_id);
                    })
                    ->ignore($taxCode->id),
            ],
            'name' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string|max:64',
            'category' => 'nullable|string|max:64',
            'calculation_method' => 'nullable|in:inclusive,exclusive',
            'is_compound' => 'nullable|boolean',
            'compound_order' => 'nullable|integer|min:0|max:100',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'filing_frequency' => 'nullable|string|max:50',
            'filing_deadline_days' => 'nullable|integer|min:0|max:365',
            'report_template' => 'nullable|string|max:255',
            'ledger_output_account_code' => 'nullable|string|max:64',
            'ledger_input_account_code' => 'nullable|string|max:64',
            'ledger_payable_account_code' => 'nullable|string|max:64',
            'ledger_receivable_account_code' => 'nullable|string|max:64',
            'ledger_expense_account_code' => 'nullable|string|max:64',
            'registration_threshold' => 'nullable|numeric|min:0',
            'supports_reverse_charge' => 'nullable|boolean',
            'is_zero_rated' => 'nullable|boolean',
            'is_exempt' => 'nullable|boolean',
            'recoverability_rate' => 'nullable|numeric|min:0|max:100',
            'applies_to' => 'nullable|array',
            'applies_to.*' => 'string|max:64',
            'is_active' => 'nullable|boolean',
        ]);

        $jurisdiction = TaxJurisdiction::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->findOrFail($validated['tax_jurisdiction_id']);

        $before = $taxCode->toArray();
        $taxCode->update(array_merge($validated, [
            'country_code' => $jurisdiction->country_code,
            'category' => $validated['category'] ?? 'indirect',
            'calculation_method' => $validated['calculation_method'] ?? 'exclusive',
            'is_compound' => $request->boolean('is_compound'),
            'compound_order' => (int) ($validated['compound_order'] ?? 0),
            'supports_reverse_charge' => $request->boolean('supports_reverse_charge'),
            'is_zero_rated' => $request->boolean('is_zero_rated'),
            'is_exempt' => $request->boolean('is_exempt'),
            'recoverability_rate' => (float) ($validated['recoverability_rate'] ?? 100),
            'is_active' => $request->boolean('is_active', true),
        ]));
        TaxAuditService::record($taxCode, 'tax_code.updated', $before, $taxCode->fresh()->toArray());

        return back()->with('success', 'Tax code updated.');
    }

    public function destroyTaxCode($id)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $taxCode = TaxCode::findOrFail($id);
        $taxCode->delete();

        return back()->with('success', 'Tax code deleted.');
    }

    public function updateWithholdingRule(Request $request, $id)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $rule = WithholdingRule::findOrFail($id);

        $validated = $request->validate([
            'tax_jurisdiction_id' => 'required|exists:tax_jurisdictions,id',
            'name' => 'required|string|max:255',
            'service_type' => 'nullable|string|max:255',
            'counterparty_type' => 'required|string|max:64',
            'rate' => 'required|numeric|min:0|max:100',
            'threshold_amount' => 'nullable|numeric|min:0',
            'account_code' => 'nullable|string|max:64',
            'certificate_prefix' => 'nullable|string|max:64',
            'payable_account_code' => 'nullable|string|max:64',
            'receivable_account_code' => 'nullable|string|max:64',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ]);

        $jurisdiction = TaxJurisdiction::query()
            ->tap(fn ($query) => $this->applyTaxScope($query, 'tax_jurisdictions'))
            ->findOrFail($validated['tax_jurisdiction_id']);

        $before = $rule->toArray();
        $rule->update(array_merge($validated, [
            'country_code' => $jurisdiction->country_code,
            'is_active' => $request->boolean('is_active', true),
        ]));
        TaxAuditService::record($rule, 'withholding_rule.updated', $before, $rule->fresh()->toArray());

        return back()->with('success', 'Withholding rule updated.');
    }

    public function destroyWithholdingRule($id)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $rule = WithholdingRule::findOrFail($id);
        $rule->delete();

        return back()->with('success', 'Withholding rule deleted.');
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
            $query->where($table . '.company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where($table . '.user_id', $userId);
        }

        if ($branchScope === 'all' || ($branchId === '' && $branchName === '')) {
            return;
        }

        $query->where(function ($scoped) use ($table, $branchId, $branchName) {
            $matched = false;

            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $scoped->where($table . '.branch_id', $branchId);
                $matched = true;
            }

            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $method = $matched ? 'orWhere' : 'where';
                $scoped->{$method}($table . '.branch_name', $branchName);
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
