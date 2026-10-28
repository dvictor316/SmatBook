<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'Model' => 'Policy',
    ];

    public function boot()
    {
        $this->registerPolicies();

        // SUPER ADMIN - Full Access
        Gate::define('view_financial_metrics', function ($user) {
            return $user->isSuperAdmin() || $user->hasRole('administrator') || $user->hasRole('accountant');
        });

        Gate::define('view_sales_metrics', function ($user) {
            return $user->isSuperAdmin() || $user->hasRole('administrator') || $user->hasRole('store_manager');
        });

        Gate::define('view_inventory_metrics', function ($user) {
            return $user->isSuperAdmin() || $user->hasRole('administrator') || $user->hasRole('store_manager');
        });

        Gate::define('view_company_metrics', function ($user) {
            return $user->isSuperAdmin()
                || $user->hasRole('administrator')
                || $user->hasRole('state_manager')
                || $user->hasRole('deployment_manager');
        });

        // ROLE-BASED GATES
        Gate::define('is_super_admin', function ($user) {
            return $user->isSuperAdmin();
        });

        Gate::define('is_administrator', function ($user) {
            return $user->hasRole('administrator');
        });

        Gate::define('is_store_manager', function ($user) {
            return $user->hasRole('store_manager');
        });

        Gate::define('is_accountant', function ($user) {
            return $user->hasRole('accountant');
        });

        Gate::define('is_cashier', function ($user) {
            return $user->hasRole('cashier');
        });

        Gate::define('is_deployment_manager', function ($user) {
            return $user->hasRole('state_manager')
                || $user->hasRole('deployment_manager')
                || $user->hasRole('manager');
        });

        // COMBINED PERMISSIONS
        Gate::define('manage_users', function ($user) {
            return $user->isSuperAdmin() || $user->hasRole('administrator');
        });

        Gate::define('manage_invoices', function ($user) {
            return $user->isSuperAdmin()
                || $user->hasRole('administrator')
                || $user->hasRole('accountant')
                || $user->hasRole('cashier');
        });

        Gate::define('manage_products', function ($user) {
            return $user->isSuperAdmin() || $user->hasRole('administrator') || $user->hasRole('store_manager');
        });

        Gate::define('manage_reports', function ($user) {
            return $user->isSuperAdmin() || $user->hasRole('administrator') || $user->hasRole('accountant');
        });
    }
}
