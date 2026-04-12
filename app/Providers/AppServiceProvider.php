<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\{View, Schema, DB, Auth, Cache};
use Illuminate\Pagination\Paginator;
use App\Models\{Company, User, Subscription}; 
use App\Observers\UserObserver;
use App\Livewire\InboxComponent;
use Livewire\Livewire; 
use Carbon\Carbon;
use App\Support\GeoCurrency;

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

        config([
            'app.currency' => GeoCurrency::currentCurrency(),
            'app.currency_symbol' => GeoCurrency::currentSymbol(),
            'app.currency_locale' => GeoCurrency::currentLocale(),
        ]);

        // 2. UI & Pagination
        Paginator::useBootstrapFive();

        // 3. Livewire Components
        // Manually registering the inbox component
        if (class_exists(Livewire::class)) {
            Livewire::component('inbox-component', InboxComponent::class);
        }

        // 4. Model Observers
        User::observe(UserObserver::class);

        // 5. Global View Composers
        $this->registerViewComposers();
    }

    /**
     * Register all View Composers for the application.
     */
    private function registerViewComposers(): void
    {
        // COMPOSER 1: Global Categories (Only if table exists)
        View::composer('*', function ($view) {
            static $categoriesLoaded = [];
            static $categoriesCache = [];

            $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
            $userId = (int) (Auth::id() ?? 0);
            $cacheKey = 'company:' . $companyId . ':user:' . $userId;

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

            $categoriesCache[$cacheKey] = Cache::remember('ui:categories:minimal:' . $cacheKey, now()->addMinutes(10), function () use ($companyId, $userId) {
                $query = DB::table('categories')->select('id', 'name');

                if ($companyId > 0 && Schema::hasColumn('categories', 'company_id')) {
                    $query->where('company_id', $companyId);
                } elseif ($userId > 0 && Schema::hasColumn('categories', 'user_id')) {
                    $query->where('user_id', $userId);
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
            }

            $view->with('geoCountry', GeoCurrency::currentCountry('NG'));
            $view->with('geoCurrency', GeoCurrency::currentCurrency());
            $view->with('geoCurrencySymbol', GeoCurrency::currentSymbol());
            $view->with('geoCurrencyLocale', GeoCurrency::currentLocale());
        });
    }
}
