<?php

namespace App\Http;

/*
|--------------------------------------------------------------------------
| HTTP KERNEL - COMPLETE REWRITE 2026
|--------------------------------------------------------------------------
| 
| Features:
| ✅ Multi-tenant middleware groups
| ✅ Subdomain routing middleware
| ✅ Deployment manager verification
| ✅ Subscription validation
| ✅ Role-based access control
| ✅ Security & throttling
|
| Middleware Groups:
| - web: Main domain routes (landing, auth, registration)
| - tenant: Subdomain routes (company workspaces)
| - api: API routes
|--------------------------------------------------------------------------
*/

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     */
    protected $middleware = [
        // Security & Trust
        \App\Http\Middleware\TrustProxies::class,
        
        // CORS Handling
        \Illuminate\Http\Middleware\HandleCors::class,
        
        // Maintenance Mode
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        
        // Request Size Validation
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        
        // String Processing
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     */
    protected $middlewareGroups = [
        /*
        |--------------------------------------------------------------------------
        | WEB MIDDLEWARE GROUP - Main Domain Routes
        |--------------------------------------------------------------------------
        | Used for: Landing pages, authentication, registration, checkout
        | Domain: smatbook.com
        */
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // Add subdomain detection for local dev routing
            // \App\Http\Middleware\SubdomainRouting::class, // Uncomment if created
        ],

        /*
        |--------------------------------------------------------------------------
        | TENANT MIDDLEWARE GROUP - Subdomain Routes
        |--------------------------------------------------------------------------
        | Used for: Company workspaces (e.g., acme.smatbook.com)
        | Identifies tenant, validates subscription, enforces access control
        */
        'tenant' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            
            // Tenant Identification (critical for multi-tenancy)
            \App\Http\Middleware\IdentifyTenant::class,
            
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            
            // Tenant Session Verification
            \App\Http\Middleware\VerifyTenantSession::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | API MIDDLEWARE GROUP
        |--------------------------------------------------------------------------
        | Used for: RESTful API endpoints
        | Features: Rate limiting, stateless authentication
        */
        'api' => [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases allow you to apply middleware to routes more easily.
     */
    protected $middlewareAliases = [
        /*
        |--------------------------------------------------------------------------
        | CORE LARAVEL MIDDLEWARE
        |--------------------------------------------------------------------------
        */
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,

        /*
        |--------------------------------------------------------------------------
        | MULTI-TENANCY MIDDLEWARE
        |--------------------------------------------------------------------------
        */
        // Tenant Resolution & Validation
        'resolve.tenant' => \App\Http\Middleware\ResolveTenant::class,
        'tenant.belongs' => \App\Http\Middleware\TenantBelongs::class,
        'verify.tenant.session' => \App\Http\Middleware\VerifyTenantSession::class,
        'tenant.throttle' => \App\Http\Middleware\ThrottleTenant::class,

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION MIDDLEWARE
        |--------------------------------------------------------------------------
        */
        // Check if user has active subscription
        'subscription.active' => \App\Http\Middleware\SubscriptionActive::class,
        'subscription.check' => \App\Http\Middleware\CheckSubscription::class,

        /*
        |--------------------------------------------------------------------------
        | ROLE & PERMISSION MIDDLEWARE
        |--------------------------------------------------------------------------
        */
        // Check user role
        'role' => \App\Http\Middleware\CheckRole::class,
        
        // Deployment Manager Verification
        'manager.verified' => \App\Http\Middleware\ManagerVerified::class,

        /*
        |--------------------------------------------------------------------------
        | CUSTOM APPLICATION MIDDLEWARE
        |--------------------------------------------------------------------------
        */
        'plan.access' => \App\Http\Middleware\CheckPlanAccess::class,
        // Add any other custom middleware aliases here
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces middleware to always be in the given order.
     */
    protected $middlewarePriority = [
        // Cookie handling must come first
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        
        // Session starts early
        \Illuminate\Session\Middleware\StartSession::class,
        
        // Share errors with views
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        
        // Tenant resolution happens before authentication
        \App\Http\Middleware\ResolveTenant::class,
        \App\Http\Middleware\IdentifyTenant::class,
        
        // Authentication checks
        \App\Http\Middleware\Authenticate::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        
        // Bindings
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        
        // Authorization
        \Illuminate\Auth\Middleware\Authorize::class,
        
        // Tenant session verification
        \App\Http\Middleware\VerifyTenantSession::class,
    ];
}
