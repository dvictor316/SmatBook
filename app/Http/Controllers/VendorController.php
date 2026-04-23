<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\VendorLedgerTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse; 
use Illuminate\Routing\Redirector; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VendorController extends Controller
{
    private function applyTenantScope($query, string $table = 'vendors')
    {
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);
        $activeBranch = $this->getActiveBranchContext();
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        }

        if ($branchId !== '' || $branchName !== '') {
            $query->where(function ($sub) use ($table, $branchId, $branchName) {
                if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                    $sub->where("{$table}.branch_id", $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                    $sub->orWhere("{$table}.branch_name", $branchName);
                }
            });
        }

        return $query;
    }

    private function getActiveBranchContext(): array
    {
        $branchId = session('active_branch_id') ? (string) session('active_branch_id') : null;
        $branchName = session('active_branch_name') ? (string) session('active_branch_name') : null;

        if (!$branchId && !$branchName && Schema::hasTable('settings')) {
            $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
            if ($companyId > 0) {
                $key = 'branches_json_company_' . $companyId;
                $raw = (string) (DB::table('settings')->where('key', $key)->value('value') ?? '');
                $branches = json_decode($raw, true) ?: [];
                $first = collect($branches)->first();
                if ($first) {
                    $branchId = $branchId ?: ($first['id'] ?? null);
                    $branchName = $branchName ?: ($first['name'] ?? null);
                }
            }
        }

        return [
            'id' => $branchId,
            'name' => $branchName,
        ];
    }

    /**
     * Display a listing of the vendors.
     */
    public function index(Request $request): View
    {
        $query = Vendor::query()->latest();
        $this->applyTenantScope($query);

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $vendors = $query->paginate(20)->withQueryString();

        // Loop through vendors and calculate their actual current balance dynamically
        foreach ($vendors as $vendor) {
            $vendor->current_balance = VendorLedgerTransaction::where('vendor_id', $vendor->id)->sum('amount');
        }

        return view('Customers.vendors', compact('vendors'));
    }

    /**
     * Show the form for creating a new vendor.
     */
    public function create(): View
    {
        return view('Customers.create');
    }
    
    /**
     * Store a newly created vendor in storage and record initial balance transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191|unique:vendors,email',
            'phone' => 'nullable|string|max:191',
            'address' => 'nullable|string|max:191',
            'balance' => 'nullable|numeric|min:0',
            'logo' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('vendors', 'public');
        }

        if (Schema::hasColumn('vendors', 'company_id')) {
            $validated['company_id'] = auth()->user()?->company_id ?? session('current_tenant_id');
        }
        if (Schema::hasColumn('vendors', 'user_id')) {
            $validated['user_id'] = auth()->id();
        }
        if (Schema::hasColumn('vendors', 'branch_id')) {
            $validated['branch_id'] = $this->getActiveBranchContext()['id'];
        }
        if (Schema::hasColumn('vendors', 'branch_name')) {
            $validated['branch_name'] = $this->getActiveBranchContext()['name'];
        }

        // 1. Create the Vendor account
        $vendor = Vendor::create($validated);

        // 2. Create the initial ledger transaction regardless of the balance amount
        $initialAmount = (float) $request->input('balance', 0.00);

        VendorLedgerTransaction::create([
            'vendor_id' => $vendor->id,
            'name' => 'Initial Balance on Creation',
            'reference' => 'SYS-INIT',
            'mode' => 'System',
            'amount' => $initialAmount,
        ]);

        return redirect()->route('vendors.index')->with('success', 'Vendor added successfully!');
    }

    /**
     * Handle the general ledger route (no ID provided).
     */
    public function ledger_general(): View
    {
         return view('Customers.ledger');
    }

    /**
     * Handle the specific vendor ledger route (ID provided).
     */
    public function ledger($id): View
    {
        $vendor = $this->applyTenantScope(Vendor::query())->findOrFail($id);
        
        $transactions = VendorLedgerTransaction::where('vendor_id', $vendor->id)
                                ->orderBy('created_at', 'asc')
                                ->get();

        $closingBalance = $transactions->sum('amount');

        return view('Customers.ledger', compact('vendor', 'transactions', 'closingBalance')); 
    }

    /**
     * Route alias handler used by vendors/{id}/ledger.
     */
    public function vendorLedger($id): View
    {
        return $this->ledger($id);
    }
    
    /**
     * Show the form for adding a new transaction to a vendor's ledger.
     */
    public function createTransaction($id): View
    {
        $vendor = $this->applyTenantScope(Vendor::query())->findOrFail($id);
        return view('Customers.create_transaction', compact('vendor'));
    }

    /**
     * Store a new transaction for a specific vendor.
     */
    public function storeTransaction(Request $request, $id): RedirectResponse
    {
        $vendor = $this->applyTenantScope(Vendor::query())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'reference' => 'required|string|max:191',
            'mode' => 'required|string|max:191',
            'amount' => 'required|numeric',
        ]);
        
        $validated['vendor_id'] = $vendor->id;

        VendorLedgerTransaction::create($validated);

        return redirect()->route('vendors.ledger', ['id' => $vendor->id])->with('success', 'Transaction added.');
    }

    public function updateLedgerProfile(Request $request, $id): RedirectResponse
    {
        $vendor = $this->applyTenantScope(Vendor::query())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|unique:vendors,email,' . $vendor->id . '|max:191',
            'phone' => 'nullable|string|max:191',
            'address' => 'nullable|string|max:191',
            'logo' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            if ($vendor->logo && Storage::disk('public')->exists($vendor->logo)) {
                Storage::disk('public')->delete($vendor->logo);
            }

            $validated['logo'] = $request->file('logo')->store('vendors', 'public');
        }

        $vendor->update($validated);

        return redirect()
            ->route('vendors.ledger', ['id' => $vendor->id])
            ->with('success', 'Vendor profile updated successfully.');
    }

    public function updateTransaction(Request $request, $id, $transactionId): RedirectResponse
    {
        $vendor = $this->applyTenantScope(Vendor::query())->findOrFail($id);
        $transaction = VendorLedgerTransaction::where('vendor_id', $vendor->id)->findOrFail($transactionId);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'reference' => 'required|string|max:191',
            'mode' => 'required|string|max:191',
            'amount' => 'required|numeric',
        ]);

        $transaction->update($validated);

        return redirect()
            ->route('vendors.ledger', ['id' => $vendor->id])
            ->with('success', 'Transaction updated successfully.');
    }

    public function destroyTransaction($id, $transactionId): RedirectResponse
    {
        $vendor = $this->applyTenantScope(Vendor::query())->findOrFail($id);
        $transaction = VendorLedgerTransaction::where('vendor_id', $vendor->id)->findOrFail($transactionId);
        $transaction->delete();

        return redirect()
            ->route('vendors.ledger', ['id' => $vendor->id])
            ->with('success', 'Transaction deleted successfully.');
    }
    
    /**
     * Show the form for editing the specified vendor.
     */
    public function edit($id): View
    {
        $vendor = $this->applyTenantScope(Vendor::query())->findOrFail($id);
        // Calculate current balance here to display it in the edit view
        $vendor->current_balance = VendorLedgerTransaction::where('vendor_id', $vendor->id)->sum('amount');

        return view('Customers.edit', compact('vendor'));
    }

    /**
     * Update the specified vendor in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $vendor = $this->applyTenantScope(Vendor::query())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|unique:vendors,email,'.$vendor->id.'|max:191',
            'phone' => 'nullable|string|max:191',
            'address' => 'nullable|string|max:191',
            'logo' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            if ($vendor->logo && Storage::disk('public')->exists($vendor->logo)) {
                Storage::disk('public')->delete($vendor->logo);
            }

            $validated['logo'] = $request->file('logo')->store('vendors', 'public');
        }

        $vendor->update($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully!');
    }

    /**
     * Remove the specified vendor from storage.
     */
    public function destroy($id): RedirectResponse
    {
        $vendor = $this->applyTenantScope(Vendor::query())->findOrFail($id);
        if ($vendor->logo && Storage::disk('public')->exists($vendor->logo)) {
            Storage::disk('public')->delete($vendor->logo);
        }
        $vendor->delete(); 

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully!');
    }

    /**
     * Show a single vendor record page.
     */
    public function show($id): View
    {
        $vendor = $this->applyTenantScope(Vendor::query())->findOrFail($id);
        $vendor->current_balance = VendorLedgerTransaction::where('vendor_id', $vendor->id)->sum('amount');
        $transactions = VendorLedgerTransaction::where('vendor_id', $vendor->id)
            ->latest()
            ->limit(15)
            ->get();

        return view('Customers.ledger', [
            'vendor' => $vendor,
            'transactions' => $transactions,
            'closingBalance' => $vendor->current_balance,
        ]);
    }

    public function downloadImportTemplate()
    {
        $content = implode(',', [
            'name',
            'email',
            'phone',
            'address',
            'balance',
            'notes',
        ]) . PHP_EOL;

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="vendor-import-template.csv"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        Log::info('Vendor import request received.', [
            'user_id' => auth()->id(),
            'has_file' => $request->hasFile('import_file'),
            'filename' => $request->file('import_file')?->getClientOriginalName(),
            'size' => $request->file('import_file')?->getSize(),
        ]);

        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xls,xlsx|max:20480',
            'update_existing' => 'nullable|boolean',
        ]);

        try {
            $file = $request->file('import_file');
            $header = null;

            foreach ($this->spreadsheetRowIterator($file) as $row) {
                $header = $row;
                break;
            }

            if (!$header) {
                return back()->with('error', 'The import file is empty.');
            }

            $header = array_map(fn ($value) => $this->normalizeImportHeaderCell($value), $header);
            foreach (['name', 'email'] as $requiredColumn) {
                if (!in_array($requiredColumn, $header, true)) {
                    return back()->with('error', 'Missing required import column: ' . $requiredColumn);
                }
            }

            $created = 0;
            $updated = 0;
            $updatedExisting = 0;
            $skipped = 0;
            $duplicates = 0;
            $missingRequired = 0;
            $rowErrors = [];
            $companyId = (int) (auth()->user()?->company_id ?? 0);
            $userId = (int) (auth()->id() ?? 0);
            $updateExisting = $request->boolean('update_existing');

            foreach ($this->spreadsheetRowIterator($file) as $rowNumber => $row) {
                if ($rowNumber === 0) {
                    continue;
                }

                $rowData = [];
                foreach ($header as $index => $column) {
                    $rowData[$column] = trim((string) ($row[$index] ?? ''));
                }

                $missing = [];
                foreach (['name', 'email'] as $requiredField) {
                    if (($rowData[$requiredField] ?? '') === '') {
                        $missing[] = $requiredField;
                    }
                }
                if (!empty($missing)) {
                    $skipped++;
                    $missingRequired++;
                    if (count($rowErrors) < 10) {
                        $rowErrors[] = 'Row ' . ($rowNumber + 1) . ': missing ' . implode(', ', $missing);
                    }
                    continue;
                }

                try {
                    $lookupEmail = $rowData['email'] ?? '';
                    $lookupPhone = $rowData['phone'] ?? '';

                    $vendorQuery = Vendor::query();
                    if ($companyId > 0 && Schema::hasColumn('vendors', 'company_id')) {
                        $vendorQuery->where('company_id', $companyId);
                    } elseif ($userId > 0 && Schema::hasColumn('vendors', 'user_id')) {
                        $vendorQuery->where('user_id', $userId);
                    }

                    if ($lookupEmail !== '' && Schema::hasColumn('vendors', 'email')) {
                        $vendorQuery->where(function ($query) use ($lookupEmail, $lookupPhone) {
                            $query->where('email', $lookupEmail);

                            if ($lookupPhone !== '' && Schema::hasColumn('vendors', 'phone')) {
                                $query->orWhere('phone', $lookupPhone);
                            }
                        });
                    } elseif ($lookupPhone !== '' && Schema::hasColumn('vendors', 'phone')) {
                        $vendorQuery->where('phone', $lookupPhone);
                    } else {
                        $vendorQuery->where('name', $rowData['name']);
                    }

                    $vendor = $vendorQuery->first();
                    $isNew = !$vendor;
                    if ($vendor && !$updateExisting) {
                        $skipped++;
                        $duplicates++;
                        if (count($rowErrors) < 10) {
                            $rowErrors[] = 'Row ' . ($rowNumber + 1) . ': duplicate vendor detected';
                        }
                        continue;
                    }

                    $vendor = $vendor ?: new Vendor();
                    $payload = $this->sanitizeForVendorColumns([
                        'name' => $rowData['name'],
                        'email' => $lookupEmail !== '' ? $lookupEmail : null,
                        'phone' => $lookupPhone !== '' ? $lookupPhone : null,
                        'address' => $rowData['address'] ?? null,
                        'notes' => $rowData['notes'] ?? null,
                        'company_id' => $companyId > 0 ? $companyId : null,
                        'user_id' => $userId > 0 ? $userId : null,
                    ]);

                    $vendor->fill($payload);
                    $vendor->save();

                    if ($isNew) {
                        $initialAmount = is_numeric($rowData['balance'] ?? null) ? (float) $rowData['balance'] : 0.0;
                        VendorLedgerTransaction::create([
                            'vendor_id' => $vendor->id,
                            'name' => 'Initial Balance on Import',
                            'reference' => 'SYS-IMPORT',
                            'mode' => 'System',
                            'amount' => $initialAmount,
                        ]);
                        $created++;
                    } else {
                        $updated++;
                        $updatedExisting++;
                    }
                } catch (\Throwable $rowException) {
                    Log::warning('Vendor import row skipped.', [
                        'row' => $rowNumber + 1,
                        'vendor' => $rowData['name'] ?? null,
                        'error' => $rowException->getMessage(),
                    ]);
                    $skipped++;
                }
            }

            $summary = "Vendor import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.";
            if ($updatedExisting > 0) {
                $summary .= " Updated existing: {$updatedExisting}.";
            }
            if ($duplicates > 0 || $missingRequired > 0) {
                $summary .= " Duplicates skipped: {$duplicates}, Missing required: {$missingRequired}.";
            }

            $redirect = redirect()->route('vendors.index')->with('success', $summary);
            if (!empty($rowErrors)) {
                $redirect->with('warning', 'Some rows were skipped: ' . implode(' | ', $rowErrors));
            }
            return $redirect;
        } catch (\Throwable $exception) {
            Log::error('Vendor import failed.', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with(
                'error',
                'The vendor import could not be completed. Please confirm the spreadsheet columns and try again.'
            );
        }
    }

    private function sanitizeForVendorColumns(array $data): array
    {
        $allowed = array_flip(Schema::getColumnListing('vendors'));
        return array_intersect_key($data, $allowed);
    }

    private function normalizeImportHeaderCell($value): string
    {
        $header = strtolower(trim((string) $value));
        return preg_replace('/^\x{FEFF}/u', '', $header) ?? $header;
    }

    private function spreadsheetRowIterator(\Illuminate\Http\UploadedFile $file): \Generator
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'], true)) {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                return;
            }

            try {
                $delimiter = $this->detectCsvDelimiter($handle);
                while (($line = fgets($handle)) !== false) {
                    $line = $this->normalizeCsvLine($line);
                    if ($line === '') {
                        continue;
                    }

                    $row = str_getcsv($line, $delimiter);
                    $row = $this->expandEmbeddedDelimitedRow($row);
                    if ($row === [null] || $row === false) {
                        continue;
                    }

                    yield $row;
                }
            } finally {
                fclose($handle);
            }

            return;
        }

        $reader = IOFactory::createReaderForFile($file->getRealPath());
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        $spreadsheet = $reader->load($file->getRealPath());

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = (int) $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();

            if ($highestRow <= 0 || $highestColumn === '') {
                return;
            }

            foreach ($sheet->rangeToArray("A1:{$highestColumn}{$highestRow}", null, true, false, false) as $cells) {
                $cells = array_map(fn ($value) => is_scalar($value) ? trim((string) $value) : $value, $cells);
                $hasValue = collect($cells)->contains(fn ($value) => trim((string) $value) !== '');
                if (!$hasValue) {
                    continue;
                }

                yield $cells;
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    private function detectCsvDelimiter($handle): string
    {
        $default = ',';
        $candidates = [',', ';', "\t", '|'];
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            rewind($handle);
            return $default;
        }

        $firstLine = $this->normalizeCsvLine($firstLine);
        $bestDelimiter = $default;
        $bestCount = 0;
        foreach ($candidates as $candidate) {
            $count = substr_count($firstLine, $candidate);
            if ($count > $bestCount) {
                $bestDelimiter = $candidate;
                $bestCount = $count;
            }
        }

        rewind($handle);
        return $bestDelimiter;
    }

    private function normalizeCsvLine(string $line): string
    {
        if ($line === '') {
            return '';
        }

        if (str_starts_with($line, "\xEF\xBB\xBF")) {
            $line = substr($line, 3);
        }

        if (!mb_check_encoding($line, 'UTF-8')) {
            $line = mb_convert_encoding($line, 'UTF-8', 'UTF-16LE,UTF-16BE,Windows-1252,ISO-8859-1');
        }

        return trim($line);
    }

    private function expandEmbeddedDelimitedRow($row): array
    {
        if (!is_array($row)) {
            return [];
        }

        if (count($row) !== 1) {
            return $row;
        }

        $cell = (string) ($row[0] ?? '');
        if ($cell === '') {
            return $row;
        }

        $delimiterCandidates = [',', ';', "\t", '|'];
        foreach ($delimiterCandidates as $delimiter) {
            if (strpos($cell, $delimiter) !== false) {
                return str_getcsv($cell, $delimiter);
            }
        }

        return $row;
    }
}
