<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\LandingSetting;
use App\Models\Company;
use App\Models\Plan; // Added Plan model
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    public function index()
    {
        $host = Str::lower((string) request()->getHost());
        $mainDomain = trim((string) config('session.domain', env('SESSION_DOMAIN', 'smartprobook.com')), ". \t\n\r\0\x0B");
        $appUrlHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        $centralHosts = collect([
            $mainDomain,
            'www.' . $mainDomain,
            'localhost',
            '127.0.0.1',
            $appUrlHost,
            $appUrlHost ? preg_replace('/^www\./i', '', $appUrlHost) : null,
            $appUrlHost ? 'www.' . preg_replace('/^www\./i', '', $appUrlHost) : null,
        ])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => Str::lower((string) $value))
            ->unique()
            ->values()
            ->all();

        if (!in_array($host, $centralHosts, true)) {
            $subdomain = explode('.', $host)[0] ?? null;

            if ($subdomain) {
                $company = Company::query()
                    ->where('domain_prefix', $subdomain)
                    ->orWhere('subdomain', $subdomain)
                    ->first();

                if ($company) {
                    session([
                        'current_tenant_id' => $company->id,
                        'current_tenant_name' => $company->domain_prefix ?: $company->subdomain,
                    ]);

                    return Auth::check()
                        ? redirect('/dashboard')
                        : redirect()->route('login');
                }
            }
        }

        $totalInvoices = Sale::count() ?? 0;

        try {
            $settings = LandingSetting::first();
        } catch (\Exception $e) {
            $settings = null;
        }

        return view('Landing.index', compact('totalInvoices', 'settings'));
    }


    public function about()
    {
        return view('Landing.about');
    }

    public function contact()
    {
        return view('Landing.contact');
    }

    public function demo(Request $request)
    {
        $demoEmail = 'demo@smartprobook.local';
        $demoCompanyName = 'SmartProbook Demo Company';
        $demoPrefix = 'demo-hq';

        try {
            $user = DB::transaction(function () use ($demoEmail, $demoCompanyName, $demoPrefix) {
                $user = User::withTrashed()->firstOrNew(['email' => $demoEmail]);

                if (method_exists($user, 'trashed') && $user->trashed()) {
                    $user->restore();
                }

                $userPayload = $this->onlyExistingColumns('users', [
                    'name' => 'SmartProbook Demo',
                    'password' => Hash::make('DemoAccess2026'),
                    'role' => 'admin',
                    'status' => 'active',
                    'is_verified' => 1,
                    'email_verified_at' => now(),
                    'verified_at' => now(),
                    'phone' => '+2348000000000',
                ]);

                if (!empty($userPayload)) {
                    $user->fill($userPayload);
                }
                $user->save();

                $company = Company::updateOrCreate(
                    $this->onlyExistingColumns('companies', ['user_id' => $user->id]),
                    $this->onlyExistingColumns('companies', [
                        'user_id' => $user->id,
                        'owner_id' => $user->id,
                        'name' => $demoCompanyName,
                        'company_name' => $demoCompanyName,
                        'email' => $demoEmail,
                        'phone' => '+2348000000000',
                        'address' => 'Demo HQ, Lagos',
                        'status' => 'active',
                        'country' => 'Nigeria',
                        'currency_code' => 'NGN',
                        'currency_symbol' => '₦',
                        'subdomain' => $demoPrefix,
                        'domain_prefix' => $demoPrefix,
                        'domain' => $demoPrefix,
                        'plan' => 'Professional',
                        'industry' => 'Technology',
                        'subscription_start' => now()->subDays(7),
                        'subscription_end' => now()->addDays(30),
                    ])
                );

                if ((int) $user->company_id !== (int) $company->id) {
                    $user->company_id = $company->id;
                    $user->save();
                }

                $professionalPlanId = null;
                if (Schema::hasTable('plans')) {
                    $professionalPlanId = Plan::query()
                        ->whereRaw('LOWER(name) like ?', ['%professional%'])
                        ->value('id');
                }

                Subscription::updateOrCreate(
                    $this->onlyExistingColumns('subscriptions', ['user_id' => $user->id]),
                    $this->onlyExistingColumns('subscriptions', [
                        'user_id' => $user->id,
                        'company_id' => $company->id,
                        'plan_id' => $professionalPlanId,
                        'plan' => 'Professional',
                        'plan_name' => 'Professional',
                        'subscriber_name' => $demoCompanyName,
                        'domain_prefix' => $demoPrefix,
                        'employee_size' => '25-50',
                        'amount' => 19500,
                        'billing_cycle' => 'Monthly',
                        'start_date' => now()->subDays(7),
                        'end_date' => now()->addDays(30),
                        'status' => 'Active',
                        'payment_status' => 'paid',
                        'payment_gateway' => 'demo',
                        'payment_reference' => 'demo-workspace',
                        'transaction_reference' => 'demo-workspace',
                        'activated_at' => now()->subDays(7),
                        'initialized_at' => now()->subDays(7),
                        'paid_at' => now()->subDays(7),
                        'payment_date' => now()->subDays(7),
                    ])
                );

                $this->seedDemoWorkspace($user, $company);

                return $user->fresh();
            });

            Auth::logout();
            Auth::login($user, true);
            $request->session()->regenerate();
            $request->session()->put('user_plan', 'professional');
            $request->session()->put('is_demo_workspace', true);

            return redirect()->route('user.dashboard')
                ->with('success', 'Demo workspace is ready. Explore the app freely.');
        } catch (\Throwable $e) {
            Log::error('Demo launch failed', ['error' => $e->getMessage()]);

            return redirect()->route('landing.contact')
                ->with('error', 'The live demo could not be launched right now. Please try again shortly.');
        }
    }

    public function storeContact(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'department' => 'nullable|string|max:191',
            'message' => 'required|string|max:5000',
            'company_name' => 'nullable|string|max:191',
        ]);

        try {
            $settings = null;
            if (Schema::hasTable('landing_settings')) {
                $settings = LandingSetting::first();
            }

            $recipients = array_values(array_filter(array_unique([
                $settings?->contact_email,
                env('MAIL_ADMIN_INBOX'),
                config('mail.from.address'),
                'donvictorlive@gmail.com',
            ]), fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)));

            if (empty($recipients)) {
                return back()->with('error', 'No contact email is configured right now.')->withInput();
            }

            $subject = 'New Landing Contact: ' . ($validated['department'] ?? 'General Inquiry');
            $body = implode("\n", [
                'Name: ' . $validated['fullname'],
                'Email: ' . $validated['email'],
                'Company: ' . ($validated['company_name'] ?? 'N/A'),
                'Department: ' . ($validated['department'] ?? 'General'),
                '',
                'Message:',
                $validated['message'],
            ]);

            $preferredMailer = strtolower((string) config('mail.default'));
            $smtpReady = trim((string) config('mail.mailers.smtp.host')) !== ''
                && trim((string) config('mail.mailers.smtp.username')) !== ''
                && trim((string) config('mail.mailers.smtp.password')) !== '';
            $deliveryMailer = ($preferredMailer === 'log' && $smtpReady) ? 'smtp' : $preferredMailer;

            Mail::mailer($deliveryMailer)->raw($body, function ($message) use ($recipients, $validated, $subject) {
                $message->from((string) config('mail.from.address'), (string) config('mail.from.name'))
                    ->to($recipients)
                    ->subject($subject);
                $message->replyTo($validated['email'], $validated['fullname']);
            });

            if ($deliveryMailer === 'log') {
                return back()->with('success', 'Request received. Mailer is in LOG mode, so email was captured in logs. Set MAIL_MAILER=smtp with valid credentials for inbox delivery.');
            }

            return back()->with('success', 'Message sent successfully. Our team will reach out shortly.');
        } catch (\Throwable $e) {
            Log::error('Landing contact submission failed', [
                'error' => $e->getMessage(),
                'email' => $validated['email'] ?? null,
            ]);

            if (str_contains(strtolower($e->getMessage()), 'daily user sending limit exceeded')) {
                return back()->withInput()->with(
                    'error',
                    'Your request was captured, but email delivery is temporarily paused because the current sender mailbox has reached its daily Gmail sending limit.'
                );
            }

            return back()->with('error', 'Request captured, but email delivery failed. Check MAIL settings and try again.')->withInput();
        }
    }

    public function team()
    {
        return view('Landing.team');
    }

    public function policy()
    {
        return view('Landing.policy');
    }

    public function projectLahome()
    {
        return view('Landing.projects.lahome');
    }

    public function projectMasterJamb()
    {
        return view('Landing.projects.master-jamb');
    }

    public function projectPayplus()
    {
        return view('Landing.projects.payplus');
    }

    protected function seedDemoWorkspace(User $user, Company $company): void
    {
        $categoryId = $this->seedDemoCategory();
        $productIds = $this->seedDemoProducts($user, $company, $categoryId);
        $customerIds = $this->seedDemoCustomers($user, $company);

        $this->seedDemoExpenses($user, $company);
        $this->seedDemoSales($user, $company, $customerIds, $productIds);
    }

    protected function seedDemoCategory(): ?int
    {
        if (!Schema::hasTable('categories')) {
            return null;
        }

        $category = Category::query()->firstOrCreate(
            ['name' => 'Demo Essentials'],
            ['description' => 'Sample inventory used for the SmartProbook live demo.']
        );

        return $category->id;
    }

    protected function seedDemoProducts(User $user, Company $company, ?int $categoryId): array
    {
        if (!$categoryId || !Schema::hasTable('products')) {
            return [];
        }

        $records = [
            [
                'sku' => 'DEMO-CARTON-TISSUE',
                'name' => 'Premium Tissue Carton',
                'barcode' => '9001001001',
                'price' => 18500,
                'purchase_price' => 14200,
                'stock' => 480,
                'stock_quantity' => 480,
                'base_unit_name' => 'pcs',
                'unit_type' => 'carton',
                'units_per_carton' => 48,
                'units_per_roll' => 6,
                'description' => 'Fast-moving carton demo product for retail and wholesale flows.',
                'status' => 'active',
            ],
            [
                'sku' => 'DEMO-ROLL-FILM',
                'name' => 'Stretch Film Roll',
                'barcode' => '9001001002',
                'price' => 7500,
                'purchase_price' => 5100,
                'stock' => 180,
                'stock_quantity' => 180,
                'base_unit_name' => 'roll',
                'unit_type' => 'roll',
                'units_per_carton' => 12,
                'units_per_roll' => 1,
                'description' => 'Packaging roll demo product for warehouse workflows.',
                'status' => 'active',
            ],
            [
                'sku' => 'DEMO-UNIT-DETERGENT',
                'name' => 'Liquid Detergent Sachet Pack',
                'barcode' => '9001001003',
                'price' => 950,
                'purchase_price' => 600,
                'stock' => 960,
                'stock_quantity' => 960,
                'base_unit_name' => 'pcs',
                'unit_type' => 'sachet',
                'units_per_carton' => 120,
                'units_per_roll' => 12,
                'description' => 'Small-ticket demo item for fast checkout examples.',
                'status' => 'active',
            ],
        ];

        $ids = [];

        foreach ($records as $record) {
            $payload = $this->onlyExistingColumns('products', array_merge($record, [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'category_id' => $categoryId,
            ]));

            Product::query()->updateOrCreate(['sku' => $record['sku']], $payload);
            $ids[] = Product::query()->where('sku', $record['sku'])->value('id');
        }

        return array_values(array_filter($ids));
    }

    protected function seedDemoCustomers(User $user, Company $company): array
    {
        if (!Schema::hasTable('customers')) {
            return [];
        }

        $records = [
            [
                'name' => 'Afro Retail Mart',
                'customer_name' => 'Afro Retail Mart',
                'email' => 'buyer@afroretail.demo',
                'phone' => '+2348011111111',
                'address' => '12 Adeola Odeku, Victoria Island, Lagos',
                'status' => 'active',
                'balance' => 0,
                'currency' => '₦',
                'notes' => 'Flagship walk-in and wholesale customer for demo sales.',
            ],
            [
                'name' => 'Nordic Home Store',
                'customer_name' => 'Nordic Home Store',
                'email' => 'accounts@nordichome.demo',
                'phone' => '+2348022222222',
                'address' => '5 Admiralty Way, Lekki Phase 1, Lagos',
                'status' => 'active',
                'balance' => 24500,
                'currency' => '₦',
                'notes' => 'Credit customer used to demonstrate balances and follow-up.',
            ],
            [
                'name' => 'Walk-in Corporate Desk',
                'customer_name' => 'Walk-in Corporate Desk',
                'email' => 'walkin@smartprobook.demo',
                'phone' => '+2348033333333',
                'address' => 'Demo reception counter',
                'status' => 'active',
                'balance' => 0,
                'currency' => '₦',
                'notes' => 'Generic walk-in profile kept for instant counter sales.',
            ],
        ];

        $ids = [];

        foreach ($records as $record) {
            $payload = $this->onlyExistingColumns('customers', array_merge($record, [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'created_by' => $user->id,
            ]));

            DB::table('customers')->updateOrInsert(
                ['email' => $record['email']],
                $this->stampTimestamps('customers', $payload)
            );

            $ids[] = DB::table('customers')->where('email', $record['email'])->value('id');
        }

        return array_values(array_filter($ids));
    }

    protected function seedDemoExpenses(User $user, Company $company): void
    {
        if (!Schema::hasTable('expenses')) {
            return;
        }

        $records = [
            [
                'reference' => 'DEMO-EXP-001',
                'expense_id' => 'DEMO-EXP-001',
                'company_name' => $company->name ?? 'SmartProbook Demo Company',
                'email' => 'ops@smartprobook.demo',
                'amount' => 125000,
                'payment_mode' => 'Bank Transfer',
                'payment_status' => 'paid',
                'category' => 'Operations',
                'status' => 'Paid',
                'notes' => 'Monthly logistics and dispatch support.',
                'created_by' => $user->id,
                'company_id' => $company->id,
            ],
            [
                'reference' => 'DEMO-EXP-002',
                'expense_id' => 'DEMO-EXP-002',
                'company_name' => $company->name ?? 'SmartProbook Demo Company',
                'email' => 'growth@smartprobook.demo',
                'amount' => 68000,
                'payment_mode' => 'Card',
                'payment_status' => 'pending',
                'category' => 'Marketing',
                'status' => 'Pending',
                'notes' => 'Campaign spend for seasonal promotion.',
                'created_by' => $user->id,
                'company_id' => $company->id,
            ],
        ];

        foreach ($records as $record) {
            $payload = $this->stampTimestamps('expenses', $this->onlyExistingColumns('expenses', $record));
            $match = ['company_name' => $record['company_name'], 'amount' => $record['amount']];
            if (Schema::hasColumn('expenses', 'reference')) {
                $match = ['reference' => $record['reference']];
            } elseif (Schema::hasColumn('expenses', 'expense_id')) {
                $match = ['expense_id' => $record['expense_id']];
            }

            DB::table('expenses')->updateOrInsert($match, $payload);
        }
    }

    protected function seedDemoSales(User $user, Company $company, array $customerIds, array $productIds): void
    {
        if (!Schema::hasTable('sales')) {
            return;
        }

        $sales = [
            [
                'invoice_no' => 'DEMO-INV-1001',
                'receipt_no' => 'DEMO-RCP-1001',
                'customer_id' => $customerIds[0] ?? null,
                'customer_name' => 'Afro Retail Mart',
                'user_id' => $user->id,
                'terminal_id' => 'POS1',
                'subtotal' => 55500,
                'discount' => 2500,
                'tax' => 3975,
                'total' => 56975,
                'payment_method' => 'transfer',
                'payment_status' => 'paid',
                'paid' => 56975,
                'amount_paid' => 56975,
                'balance' => 0,
                'currency' => 'NGN',
                'order_status' => 'completed',
                'branch_name' => 'Head Office',
                'company_id' => $company->id,
            ],
            [
                'invoice_no' => 'DEMO-INV-1002',
                'receipt_no' => 'DEMO-RCP-1002',
                'customer_id' => $customerIds[1] ?? null,
                'customer_name' => 'Nordic Home Store',
                'user_id' => $user->id,
                'terminal_id' => 'POS2',
                'subtotal' => 28800,
                'discount' => 0,
                'tax' => 2160,
                'total' => 30960,
                'payment_method' => 'card',
                'payment_status' => 'partial',
                'paid' => 15000,
                'amount_paid' => 15000,
                'balance' => 15960,
                'currency' => 'NGN',
                'order_status' => 'pending',
                'branch_name' => 'Lekki Outlet',
                'company_id' => $company->id,
            ],
        ];

        foreach ($sales as $index => $record) {
            $payload = $this->stampTimestamps('sales', $this->onlyExistingColumns('sales', $record));
            $sale = Sale::query()->updateOrCreate(['invoice_no' => $record['invoice_no']], $payload);

            if (!Schema::hasTable('sale_items') || empty($productIds)) {
                continue;
            }

            $lineItems = [
                [
                    'sale_id' => $sale->id,
                    'product_id' => $productIds[$index % count($productIds)],
                    'quantity' => 3,
                    'qty' => 3,
                    'unit_price' => $index === 0 ? 18500 : 7500,
                    'discount' => $index === 0 ? 2.5 : 0,
                    'tax' => 7.5,
                    'subtotal' => $index === 0 ? 55500 : 22500,
                    'total_price' => $index === 0 ? 56975 : 24187.5,
                ],
            ];

            foreach ($lineItems as $item) {
                $match = ['sale_id' => $sale->id, 'product_id' => $item['product_id']];
                $payload = $this->stampTimestamps('sale_items', $this->onlyExistingColumns('sale_items', $item));
                DB::table('sale_items')->updateOrInsert($match, $payload);
            }
        }
    }

    protected function onlyExistingColumns(string $table, array $attributes): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $columns = array_flip(Schema::getColumnListing($table));

        return array_filter(
            $attributes,
            static fn ($value, $key) => isset($columns[$key]),
            ARRAY_FILTER_USE_BOTH
        );
    }

    protected function stampTimestamps(string $table, array $attributes): array
    {
        $now = now();

        if (Schema::hasColumn($table, 'updated_at')) {
            $attributes['updated_at'] = $now;
        }

        if (Schema::hasColumn($table, 'created_at')) {
            $attributes['created_at'] = $attributes['created_at'] ?? $now;
        }

        return $attributes;
    }

}
