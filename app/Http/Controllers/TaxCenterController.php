<?php

namespace App\Http\Controllers;

use App\Models\TaxCode;
use App\Models\TaxJurisdiction;
use App\Models\WithholdingRule;
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
                'taxSetupMissing' => true,
            ]);
        }

        $jurisdictions = TaxJurisdiction::latest()->get();
        $taxCodes = TaxCode::with('jurisdiction')->latest()->limit(20)->get();
        $withholdingRules = WithholdingRule::with('jurisdiction')->latest()->limit(20)->get();

        return view('compliance.tax-center.index', compact('jurisdictions', 'taxCodes', 'withholdingRules'));
    }

    public function storeJurisdiction(Request $request)
    {
        if (!$this->taxTablesReady()) {
            return back()->with('error', $this->migrationMessage());
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country_code' => 'nullable|string|max:3',
            'region' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|max:3',
        ]);

        TaxJurisdiction::create($validated + ['is_active' => true]);

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
            'description' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string|max:64',
        ]);

        TaxCode::create($validated + ['is_active' => true]);

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
            'counterparty_type' => 'required|string|max:64',
            'rate' => 'required|numeric|min:0|max:100',
            'threshold_amount' => 'nullable|numeric|min:0',
            'account_code' => 'nullable|string|max:64',
        ]);

        WithholdingRule::create($validated + ['is_active' => true]);

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
            'country_code' => 'nullable|string|max:3',
            'region' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|max:3',
            'is_active' => 'nullable|boolean',
        ]);

        $jurisdiction->update($validated + ['is_active' => (bool) ($request->is_active ?? true)]);

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
            'description' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string|max:64',
            'is_active' => 'nullable|boolean',
        ]);

        $taxCode->update($validated + ['is_active' => (bool) ($request->is_active ?? true)]);

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
            'counterparty_type' => 'required|string|max:64',
            'rate' => 'required|numeric|min:0|max:100',
            'threshold_amount' => 'nullable|numeric|min:0',
            'account_code' => 'nullable|string|max:64',
            'is_active' => 'nullable|boolean',
        ]);

        $rule->update($validated + ['is_active' => (bool) ($request->is_active ?? true)]);

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
}
