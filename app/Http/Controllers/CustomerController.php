<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $customers = $this->buildCustomerQuery($request)
            ->paginate(20)
            ->withQueryString();

        return view('Customers.customers', compact('customers'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('Customers.add-customer'); 
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:191',
            'email'         => 'nullable|email|max:191|unique:customers,email',
            'phone'         => 'nullable|string|max:191',
            'balance'       => 'nullable|numeric|min:0',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Capture all possible form fields, then keep only real DB columns.
        $data = $request->only([
            'customer_name', 'email', 'phone', 'currency', 'website', 'notes',
            'billing_name', 'billing_address_line1', 'billing_address_line2', 
            'billing_country', 'billing_city', 'billing_state', 'billing_pincode',
            'shipping_name', 'shipping_address_line1', 'shipping_address_line2', 
            'shipping_country', 'shipping_city', 'shipping_state', 'shipping_pincode',
            'bank_name', 'branch', 'account_holder', 'account_number', 'ifsc'
        ]);

        $data['status'] = 'active'; 
        $data['balance'] = (float) $request->input('balance', 0.00);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('profiles', 'public');
        }

        $data = $this->sanitizeForCustomerColumns($data);

        Customer::create($data);

        return redirect()->route('customers.index')->with('success', 'Customer added successfully.');
    }

    /**
     * Display the specified customer.
     */
    public function show($id)
    {
        $customer = Customer::with(['sales', 'invoices'])->findOrFail($id);
        $invoices = $customer->invoices;

        $invoicescards = [
            [
                'title'  => 'Total Invoices',
                'amount' => $invoices->count(),
                'icon'   => 'clipboard-text',
                'class'  => 'bg-blue-light',
            ],
            [
                'title'  => 'Total Sales',
                'amount' => '₦' . number_format($customer->sales->sum('total'), 2),
                'icon'   => 'archive',
                'class'  => 'bg-green-light',
            ],
            [
                'title'  => 'Pending Balance',
                'amount' => '₦' . number_format($customer->balance, 2),
                'icon'   => 'clock',
                'class'  => 'bg-orange-light',
            ],
            [
                'title'  => 'Total Paid',
                'amount' => '₦' . number_format($customer->sales->sum('amount_paid'), 2),
                'icon'   => 'check-circle',
                'class'  => 'bg-emerald-light',
            ]
        ];

        return view('Customers.customer-details', compact('customer', 'invoices', 'invoicescards'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('Customers.edit-customer', compact('customer'));
    }

   /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'customer_name' => 'required|string|max:191',
            'email'         => 'nullable|email|max:191|unique:customers,email,' . $id,
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status'        => 'required|in:active,deactive',
        ]);

        // Capture all possible form fields for update, then keep only real DB columns.
        $data = $request->only([
            'customer_name', 'email', 'phone', 'status', 'currency', 'website', 'notes',
            'billing_name', 'billing_address_line1', 'billing_address_line2', 
            'billing_country', 'billing_city', 'billing_state', 'billing_pincode',
            'shipping_name', 'shipping_address_line1', 'shipping_address_line2', 
            'shipping_country', 'shipping_city', 'shipping_state', 'shipping_pincode',
            'bank_name', 'branch', 'account_holder', 'account_number', 'ifsc'
        ]);

        if ($request->hasFile('image')) {
            if ($customer->image && Storage::disk('public')->exists($customer->image)) {
                Storage::disk('public')->delete($customer->image);
            }
            $data['image'] = $request->file('image')->store('profiles', 'public');
        }

        $data = $this->sanitizeForCustomerColumns($data);

        $customer->update($data);

        // CHANGE THIS LINE: 
        // From: return redirect()->route('customers.show', $customer->id)
        // To:   return redirect()->route('customers.index')
        
        $redirectTo = (string) $request->input('redirect_to', '');
        if ($redirectTo === 'show') {
            return redirect()->route('customers.show', $customer->id)
                ->with('success', 'Customer record updated successfully.');
        }

        return redirect()->route('customers.index')
            ->with('success', 'Customer record updated successfully.');
    }
    /**
     * Toggle Status and Filters.
     */
    public function activeView(Request $request)
    {
        $customers = $this->buildCustomerQuery($request, 'active')
            ->paginate(20)
            ->withQueryString();

        return view('Customers.customers', compact('customers'));
    }

    public function deactiveView(Request $request)
    {
        $customers = $this->buildCustomerQuery($request, 'deactive')
            ->paginate(20)
            ->withQueryString();

        return view('Customers.customers', compact('customers'));
    }

    private function buildCustomerQuery(Request $request, ?string $fixedStatus = null)
    {
        $query = Customer::query()->latest();

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($fixedStatus !== null) {
            $query->where('status', $fixedStatus);
        } elseif ($request->filled('status')) {
            $query->where('status', trim((string) $request->input('status')));
        }

        return $query;
    }

    public function activate($id)
    {
        Customer::findOrFail($id)->update(['status' => 'active']);
        return back()->with('success', 'Customer activated.');
    }

    public function deactivate($id)
    {
        Customer::findOrFail($id)->update(['status' => 'deactive']);
        return back()->with('success', 'Customer deactivated.');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        
        if ($customer->image) {
            Storage::disk('public')->delete($customer->image);
        }

        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function export($id)
    {
        $customer = Customer::findOrFail($id);

        $filename = 'customer_' . $customer->id . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($customer) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Customer Name', 'Email', 'Phone', 'Status', 'Balance', 'Created At']);
            fputcsv($out, [
                $customer->id,
                $customer->customer_name,
                $customer->email,
                $customer->phone,
                $customer->status,
                $customer->balance,
                optional($customer->created_at)->toDateTimeString(),
            ]);
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function apiIndex(Request $request)
    {
        $query = Customer::query()->select(['id', 'customer_name', 'email', 'phone', 'status', 'balance']);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json($query->latest()->limit(100)->get());
    }

    public function downloadImportTemplate()
    {
        $headers = ['customer_name', 'email', 'phone', 'address', 'balance', 'status', 'notes'];
        $rows = [
            ['Adebayo Stores', 'accounts@adebayo.example', '08030000000', '12 Market Road', '25000', 'active', 'Opening credit balance'],
            ['Walk-in Customer', '', '', '', '0', 'active', 'General retail customer'],
        ];

        $content = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $content .= implode(',', array_map(function ($value) {
                $escaped = str_replace('"', '""', (string) $value);
                return '"' . $escaped . '"';
            }, $row)) . "\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=customers-import-template.csv',
        ]);
    }

    public function import(Request $request)
    {
        \Log::info('Customer import request received.', [
            'user_id' => auth()->id(),
            'has_file' => $request->hasFile('import_file'),
            'filename' => $request->file('import_file')?->getClientOriginalName(),
            'size' => $request->file('import_file')?->getSize(),
        ]);

        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xls,xlsx|max:20480',
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
            foreach (['customer_name'] as $requiredColumn) {
                if (!in_array($requiredColumn, $header, true)) {
                    return back()->with('error', 'Missing required import column: ' . $requiredColumn);
                }
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $companyId = (int) (auth()->user()?->company_id ?? 0);
            $userId = (int) (auth()->id() ?? 0);

            foreach ($this->spreadsheetRowIterator($file) as $rowNumber => $row) {
                if ($rowNumber === 0) {
                    continue;
                }

                $rowData = [];
                foreach ($header as $index => $column) {
                    $rowData[$column] = trim((string) ($row[$index] ?? ''));
                }

                if (($rowData['customer_name'] ?? '') === '') {
                    $skipped++;
                    continue;
                }

                try {
                    $lookupEmail = $rowData['email'] ?? '';
                    $lookupPhone = $rowData['phone'] ?? '';

                    $customerQuery = Customer::query();
                    if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
                        $customerQuery->where('company_id', $companyId);
                    } elseif ($userId > 0 && Schema::hasColumn('customers', 'user_id')) {
                        $customerQuery->where('user_id', $userId);
                    }

                    if ($lookupEmail !== '' && Schema::hasColumn('customers', 'email')) {
                        $customerQuery->where(function ($query) use ($lookupEmail, $lookupPhone) {
                            $query->where('email', $lookupEmail);

                            if ($lookupPhone !== '' && Schema::hasColumn('customers', 'phone')) {
                                $query->orWhere('phone', $lookupPhone);
                            }
                        });
                    } elseif ($lookupPhone !== '' && Schema::hasColumn('customers', 'phone')) {
                        $customerQuery->where('phone', $lookupPhone);
                    } else {
                        $customerQuery->where('customer_name', $rowData['customer_name']);
                    }

                    $customer = $customerQuery->first();
                    $isNew = !$customer;
                    $customer = $customer ?: new Customer();

                    $payload = $this->sanitizeForCustomerColumns([
                        'customer_name' => $rowData['customer_name'],
                        'email' => $lookupEmail !== '' ? $lookupEmail : null,
                        'phone' => $lookupPhone !== '' ? $lookupPhone : null,
                        'address' => $rowData['address'] ?? null,
                        'balance' => is_numeric($rowData['balance'] ?? null) ? (float) $rowData['balance'] : 0,
                        'status' => in_array(strtolower((string) ($rowData['status'] ?? 'active')), ['active', 'deactive'], true)
                            ? strtolower((string) $rowData['status'])
                            : 'active',
                        'notes' => $rowData['notes'] ?? null,
                        'company_id' => $companyId > 0 ? $companyId : null,
                        'user_id' => $userId > 0 ? $userId : null,
                    ]);

                    $customer->fill($payload);
                    $customer->save();

                    $isNew ? $created++ : $updated++;
                } catch (\Throwable $rowException) {
                    \Log::warning('Customer import row skipped.', [
                        'row' => $rowNumber + 1,
                        'customer_name' => $rowData['customer_name'] ?? null,
                        'error' => $rowException->getMessage(),
                    ]);
                    $skipped++;
                }
            }

            return redirect()->route('customers.index')->with(
                'success',
                "Customer import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}."
            );
        } catch (\Throwable $exception) {
            \Log::error('Customer import failed.', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with(
                'error',
                'The customer import could not be completed. Please confirm the spreadsheet columns and try again.'
            );
        }
    }

    private function sanitizeForCustomerColumns(array $data): array
    {
        $allowed = array_flip(Schema::getColumnListing('customers'));
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
                while (($row = fgetcsv($handle)) !== false) {
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
}
