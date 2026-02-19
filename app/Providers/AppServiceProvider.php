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
            static $categoriesLoaded = false;
            static $categoriesCache;

            if ($categoriesLoaded) {
                $view->with('categories', $categoriesCache);
                return;
            }

            if (!Schema::hasTable('categories')) {
                $categoriesLoaded = true;
                $categoriesCache = collect();
                $view->with('categories', $categoriesCache);
                return;
            }

            $categoriesCache = Cache::remember('ui:categories:minimal', now()->addMinutes(10), function () {
                return DB::table('categories')->select('id', 'name')->get();
            });

            $categoriesLoaded = true;
            $view->with('categories', $categoriesCache);
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
                    $resolvedSubscription = Subscription::where('user_id', $resolvedAuthUser->id)->latest()->first();
                    $resolvedUser = true;
                }

                $view->with('currentSubscription', $resolvedSubscription);
                $view->with('currentUser', $resolvedAuthUser);
            }

            $view->with('geoCountry', GeoCurrency::currentCountry('NG'));
            $view->with('geoCurrency', GeoCurrency::currentCurrency());
        });
    }
}
