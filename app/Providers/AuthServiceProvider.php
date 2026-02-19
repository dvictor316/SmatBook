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
            return in_array($user->role, ['super_admin', 'administrator', 'accountant']);
        });

        Gate::define('view_sales_metrics', function ($user) {
            return in_array($user->role, ['super_admin', 'administrator', 'store_manager']);
        });

        Gate::define('view_inventory_metrics', function ($user) {
            return in_array($user->role, ['super_admin', 'administrator', 'store_manager']);
        });

        Gate::define('view_company_metrics', function ($user) {
            return in_array($user->role, ['super_admin', 'administrator', 'deployment_manager']);
        });

        // ROLE-BASED GATES
        Gate::define('is_super_admin', function ($user) {
            return $user->role === 'super_admin';
        });

        Gate::define('is_administrator', function ($user) {
            return $user->role === 'administrator';
        });

        Gate::define('is_store_manager', function ($user) {
            return $user->role === 'store_manager';
        });

        Gate::define('is_accountant', function ($user) {
            return $user->role === 'accountant';
        });

        Gate::define('is_cashier', function ($user) {
            return $user->role === 'cashier';
        });

        Gate::define('is_deployment_manager', function ($user) {
            return $user->role === 'deployment_manager';
        });

        // COMBINED PERMISSIONS
        Gate::define('manage_users', function ($user) {
            return in_array($user->role, ['super_admin', 'administrator']);
        });

        Gate::define('manage_invoices', function ($user) {
            return in_array($user->role, ['super_admin', 'administrator', 'accountant', 'cashier']);
        });

        Gate::define('manage_products', function ($user) {
            return in_array($user->role, ['super_admin', 'administrator', 'store_manager']);
        });

        Gate::define('manage_reports', function ($user) {
            return in_array($user->role, ['super_admin', 'administrator', 'accountant']);
        });
    }
}