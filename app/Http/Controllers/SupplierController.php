<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SupplierController extends Controller
{
    private function applyTenantScope($query, string $table = 'suppliers')
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        }

        return $query;
    }

    private function resolveNameColumn(): string
    {
        foreach (['name', 'supplier_name', 'company_name'] as $column) {
            if (Schema::hasColumn('suppliers', $column)) {
                return $column;
            }
        }

        return 'name';
    }

    public function index(Request $request)
    {
        $query = Supplier::query()->latest();
        $this->applyTenantScope($query);

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $nameColumn = $this->resolveNameColumn();
            $query->where(function ($builder) use ($search, $nameColumn) {
                $builder->where($nameColumn, 'like', "%{$search}%");
                if (Schema::hasColumn('suppliers', 'email')) {
                    $builder->orWhere('email', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('suppliers', 'phone')) {
                    $builder->orWhere('phone', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('suppliers', 'address')) {
                    $builder->orWhere('address', 'like', "%{$search}%");
                }
            });
        }

        $suppliers = $query->paginate(20)->withQueryString();
        return view('Customers.suppliers', compact('suppliers'));
    }

    public function create()
    {
        return view('Customers.suppliers-create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'    => 'required|string|max:191',
            'contact' => 'nullable|string|max:191',
            'email'   => 'nullable|email|max:191',
            'phone'   => 'nullable|string|max:191',
            'address' => 'nullable|string|max:255',
        ]);

        $payload = $this->sanitizeForSupplierColumns([
            'contact' => $request->input('contact'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'company_id' => auth()->user()?->company_id ?? null,
            'user_id' => auth()->id(),
        ]);

        $nameColumn = $this->resolveNameColumn();
        $payload[$nameColumn] = $request->input('name');
        $supplier = Supplier::create($payload);

        return redirect()->route('suppliers.index')->with('success', 'Supplier added successfully.');
    }

    public function edit($id)
    {
        $supplier = $this->applyTenantScope(Supplier::query())->findOrFail($id);
        return view('Customers.suppliers-edit', compact('supplier'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $supplier = $this->applyTenantScope(Supplier::query())->findOrFail($id);
        $request->validate([
            'name'    => 'required|string|max:191',
            'contact' => 'nullable|string|max:191',
            'email'   => 'nullable|email|max:191',
            'phone'   => 'nullable|string|max:191',
            'address' => 'nullable|string|max:255',
        ]);

        $payload = $this->sanitizeForSupplierColumns([
            'contact' => $request->input('contact'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
        ]);
        $nameColumn = $this->resolveNameColumn();
        $payload[$nameColumn] = $request->input('name');

        $supplier->update($payload);
        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $supplier = $this->applyTenantScope(Supplier::query())->findOrFail($id);
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
    }

    public function downloadImportTemplate()
    {
        $content = implode(',', [
            'name',
            'email',
            'phone',
            'address',
            'contact',
        ]) . PHP_EOL;

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="supplier-import-template.csv"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        Log::info('Supplier import request received.', [
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
            if (!Schema::hasTable('suppliers')) {
                return back()->with('error', 'Suppliers table is not available in this workspace.');
            }

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
            foreach (['name'] as $requiredColumn) {
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

                if (($rowData['name'] ?? '') === '') {
                    $skipped++;
                    $missingRequired++;
                    if (count($rowErrors) < 10) {
                        $rowErrors[] = 'Row ' . ($rowNumber + 1) . ': missing name';
                    }
                    continue;
                }

                try {
                    $lookupEmail = $rowData['email'] ?? '';
                    $lookupPhone = $rowData['phone'] ?? '';

                    $supplierQuery = Supplier::query();
                    if ($companyId > 0 && Schema::hasColumn('suppliers', 'company_id')) {
                        $supplierQuery->where('company_id', $companyId);
                    } elseif ($userId > 0 && Schema::hasColumn('suppliers', 'user_id')) {
                        $supplierQuery->where('user_id', $userId);
                    }

                    if ($lookupEmail !== '' && Schema::hasColumn('suppliers', 'email')) {
                        $supplierQuery->where(function ($query) use ($lookupEmail, $lookupPhone) {
                            $query->where('email', $lookupEmail);

                            if ($lookupPhone !== '' && Schema::hasColumn('suppliers', 'phone')) {
                                $query->orWhere('phone', $lookupPhone);
                            }
                        });
                    } elseif ($lookupPhone !== '' && Schema::hasColumn('suppliers', 'phone')) {
                        $supplierQuery->where('phone', $lookupPhone);
                    } else {
                        $nameColumn = $this->resolveNameColumn();
                        $supplierQuery->where($nameColumn, $rowData['name']);
                    }

                    $supplier = $supplierQuery->first();
                    $isNew = !$supplier;
                    if ($supplier && !$updateExisting) {
                        $skipped++;
                        $duplicates++;
                        if (count($rowErrors) < 10) {
                            $rowErrors[] = 'Row ' . ($rowNumber + 1) . ': duplicate supplier detected';
                        }
                        continue;
                    }

                    $nameColumn = $this->resolveNameColumn();
                    $payload = $this->sanitizeForSupplierColumns([
                        'contact' => $rowData['contact'] ?? null,
                        'email' => $lookupEmail !== '' ? $lookupEmail : null,
                        'phone' => $lookupPhone !== '' ? $lookupPhone : null,
                        'address' => $rowData['address'] ?? null,
                        'company_id' => $companyId > 0 ? $companyId : null,
                        'user_id' => $userId > 0 ? $userId : null,
                    ]);
                    $payload[$nameColumn] = $rowData['name'];

                    $supplier = $supplier ?: new Supplier();
                    $supplier->fill($payload);
                    $supplier->save();

                    if ($isNew) {
                        $created++;
                    } else {
                        $updated++;
                        $updatedExisting++;
                    }
                } catch (\Throwable $rowException) {
                    Log::warning('Supplier import row skipped.', [
                        'row' => $rowNumber + 1,
                        'supplier' => $rowData['name'] ?? null,
                        'error' => $rowException->getMessage(),
                    ]);
                    $skipped++;
                }
            }

            $summary = "Supplier import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.";
            if ($updatedExisting > 0) {
                $summary .= " Updated existing: {$updatedExisting}.";
            }
            if ($duplicates > 0 || $missingRequired > 0) {
                $summary .= " Duplicates skipped: {$duplicates}, Missing required: {$missingRequired}.";
            }

            $redirect = redirect()->route('suppliers.index')->with('success', $summary);
            if (!empty($rowErrors)) {
                $redirect->with('warning', 'Some rows were skipped: ' . implode(' | ', $rowErrors));
            }
            return $redirect;
        } catch (\Throwable $exception) {
            Log::error('Supplier import failed.', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with(
                'error',
                'The supplier import could not be completed. Please confirm the spreadsheet columns and try again.'
            );
        }
    }

    private function sanitizeForSupplierColumns(array $data): array
    {
        $allowed = array_flip(Schema::getColumnListing('suppliers'));
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
            foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row) {
                $cells = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $cells[] = $cell?->getFormattedValue();
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
