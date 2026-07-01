<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\{View, Schema, DB, Auth, Cache};
use Illuminate\Pagination\Paginator;
use App\Models\{Company, User, Subscription, Customer}; 
use App\Observers\UserObserver;
use App\Livewire\InboxComponent;
use Livewire\Livewire; 
use Carbon\Carbon;
use App\Support\GeoCurrency;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Database & Schema Defaults
        // Prevents "index too long" errors on older MySQL versions
        Schema::defaultStringLength(191);

        try {
            config([
                'app.currency' => GeoCurrency::currentCurrency(),
                'app.currency_symbol' => GeoCurrency::currentSymbol(),
                'app.currency_locale' => GeoCurrency::currentLocale(),
            ]);
        } catch (Throwable) {
            config([
                'app.currency' => 'NGN',
                'app.currency_symbol' => '₦',
                'app.currency_locale' => 'en_NG',
            ]);
        }

        // 2. UI & Pagination
        Paginator::useBootstrapFive();

        // 3. Livewire Components
        // Manually registering the inbox component
        if (class_exists(Livewire::class)) {
            Livewire::component('inbox-component', InboxComponent::class);
        }

        // 4. Model Observers
        try {
            if (Schema::hasTable('users')) {
                User::observe(UserObserver::class);
            }
        } catch (Throwable) {
            // Artisan commands and tests should still boot when the DB is temporarily unavailable.
        }

        // 5. Global View Composers
        $this->registerViewComposers();
    }

    /**
     * Register all View Composers for the application.
     */
    private function registerViewComposers(): void
    {
        if (!$this->databaseConnectionIsReady()) {
            return;
        }

        // COMPOSER 1: Global Categories (Only if table exists)
        View::composer('*', function ($view) {
            static $categoriesLoaded = [];
            static $categoriesCache = [];

            if (array_key_exists('categories', $view->getData())) {
                return;
            }

            $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
            $userId = (int) (Auth::id() ?? 0);
            $branchId = trim((string) session('active_branch_id', ''));
            $branchName = trim((string) session('active_branch_name', ''));
            $cacheKey = 'company:' . $companyId . ':user:' . $userId . ':branch:' . ($branchId !== '' ? $branchId : 'default') . ':branch_name:' . ($branchName !== '' ? md5($branchName) : 'default');

            if (($categoriesLoaded[$cacheKey] ?? false) === true) {
                $view->with('categories', $categoriesCache[$cacheKey] ?? collect());
                return;
            }

            if (!Schema::hasTable('categories')) {
                $categoriesLoaded[$cacheKey] = true;
                $categoriesCache[$cacheKey] = collect();
                $view->with('categories', $categoriesCache[$cacheKey]);
                return;
            }

            $categoriesCache[$cacheKey] = Cache::remember('ui:categories:minimal:' . $cacheKey, now()->addMinutes(10), function () use ($companyId, $userId, $branchId, $branchName) {
                $query = DB::table('categories')->select('id', 'name');

                if ($companyId > 0 && Schema::hasColumn('categories', 'company_id')) {
                    $query->where(function ($scoped) use ($companyId, $userId) {
                        $scoped->where('company_id', $companyId);

                        if ($userId > 0 && Schema::hasColumn('categories', 'user_id')) {
                            $scoped->orWhere(function ($fallback) use ($userId) {
                                $fallback->whereNull('company_id')
                                    ->where('user_id', $userId);
                            });
                        }
                    });
                } elseif ($userId > 0 && Schema::hasColumn('categories', 'user_id')) {
                    $query->where('user_id', $userId);
                }

                $hasBranchId = Schema::hasColumn('categories', 'branch_id');
                $hasBranchName = Schema::hasColumn('categories', 'branch_name');

                if (($branchId !== '' || $branchName !== '') && ($hasBranchId || $hasBranchName)) {
                    $query->where(function ($scoped) use ($branchId, $branchName, $hasBranchId, $hasBranchName) {
                        if ($hasBranchId && $branchId !== '') {
                            $scoped->where('branch_id', $branchId);
                            return;
                        }

                        if ($hasBranchName && $branchName !== '') {
                            $scoped->where('branch_name', $branchName);
                        }
                    });
                }

                // The globally shared category list is primarily used by product
                // forms, so keep it limited to product categories (plus legacy
                // untyped rows) to avoid suggesting expense categories in
                // unrelated product fields.
                if (Schema::hasColumn('categories', 'type')) {
                    $query->where(function ($typed) {
                        $typed->where('type', 'product')
                            ->orWhereNull('type')
                            ->orWhere('type', '');
                    });
                }

                return $query->orderBy('name')->get();
            });

            $categoriesLoaded[$cacheKey] = true;
            $view->with('categories', $categoriesCache[$cacheKey]);
        });

        // COMPOSER 2: Company Dashboard Stats (Only for company-related views)
        View::composer('companies.*', function ($view) {
            if (Schema::hasTable('companies')) {
                $view->with([
                    'totalCompanies'         => Company::count(),
                    'activeCompanies'        => Company::where('status', 'active')->count(),
                    'inactiveCompanies'      => Company::where('status', 'inactive')->count(),
                    'companiesWithAddress'   => Company::whereNotNull('address')->count(),
                    'newCompaniesSubscribed' => Company::whereDate('created_at', Carbon::today())->count(),
                ]);
            }
        });

        // COMPOSER 3: Global Subscription/Tenant Data
        // This helps debug why users are being redirected to plans.
        View::composer('*', function ($view) {
            static $resolvedUser = false;
            static $resolvedSubscription = null;
            static $resolvedAuthUser = null;

            if (Auth::check()) {
                if (!$resolvedUser) {
                    $resolvedAuthUser = Auth::user();
                    // Resolve once per request even though composer runs for each partial.
                    $resolvedSubscription = Subscription::resolveCurrentForUser($resolvedAuthUser);
                    $resolvedUser = true;
                }

                $view->with('currentSubscription', $resolvedSubscription);
                $view->with('currentUser', $resolvedAuthUser);
                $view->with('isDemoWorkspace', $resolvedAuthUser?->isDemoUser() ?? false);
                $view->with('demoCompany', $resolvedAuthUser?->company);

                $previewCustomer = null;
                $previewCustomerId = (int) session('demo_customer_preview_id', 0);
                if (($resolvedAuthUser?->isDemoUser() ?? false) && $previewCustomerId > 0) {
                    $previewCustomer = Customer::query()
                        ->where('company_id', $resolvedAuthUser?->company_id)
                        ->find($previewCustomerId);
                }

                $view->with('demoPreviewCustomer', $previewCustomer);
                $view->with('demoPreviewPlan', (string) session('demo_customer_preview_plan', ''));
            }

            $view->with('geoCountry', GeoCurrency::currentCountry('NG'));
            $view->with('geoCurrency', GeoCurrency::currentCurrency());
            $view->with('geoCurrencySymbol', GeoCurrency::currentSymbol());
            $view->with('geoCurrencyLocale', GeoCurrency::currentLocale());
        });
    }

    private function databaseConnectionIsReady(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
