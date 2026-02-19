<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{RateLimiter, Route, URL};

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     * 
     * CRITICAL: This MUST be '/home'
     * HomeController@index handles all role-based redirects
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureSubdomainRouting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure subdomain routing for multi-tenancy
     */
    protected function configureSubdomainRouting(): void
    {
        $host = request()->getHost();
        $parts = explode('.', $host);
        
        // If we are on a subdomain (e.g., tenant.smatbook.com)
        if (count($parts) >= 3) {
            URL::defaults(['tenant' => $parts[0]]);
        }
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
