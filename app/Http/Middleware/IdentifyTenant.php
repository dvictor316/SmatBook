<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\{Company, Domain, DeploymentManager, StorefrontSetting};
use Illuminate\Support\Facades\{View, Auth, Log, Schema};

class IdentifyTenant
{
    /**
     * Handle an incoming request and resolve the workspace.
     * FIXED: Deployment Managers (Active) are now allowed to bypass domain lookups
     * when accessing manager routes, preventing them from being trapped.
     */
    public function handle(Request $request, Closure $next)
    {
        View::share('currentTenant', null);
        View::share('currentStorefront', null);

        // Never apply tenant logic to the workspace-not-found page — prevents redirect loops
        if ($request->is('workspace-not-found')) {
            return $next($request);
        }

        $host = $request->getHost();
        $parts = explode('.', $host);
        
        if (empty($parts)) {
            return $next($request);
        }
        
        $subdomain = strtolower((string) ($parts[0] ?? ''));
        $mainDomain = strtolower((string) (config('app.domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'smartprobook.com'));
        $mainDomainHost = ltrim($mainDomain, '.');
        $mainDomainLabel = strtolower((string) explode('.', $mainDomainHost)[0]);

        if ($host === $mainDomainHost || $host === 'www.' . $mainDomainHost) {
            return $next($request);
        }

        // A mapped storefront host is public commerce traffic, not a private
        // tenant workspace. Resolve the full host before subdomain handling.
        if (Schema::hasTable('storefront_settings')) {
            $storefront = StorefrontSetting::query()
                ->where('custom_domain', strtolower($host))
                ->where('is_enabled', true)
                ->first();
            if ($storefront) {
                View::share('currentStorefront', $storefront);
                $request->attributes->set('public_storefront', $storefront);

                if ($request->path() === '/') {
                    return redirect('/store');
                }

                return $next($request);
            }
        }

        // 1. Skip system-level domains
        $systemDomains = array_unique([$mainDomainLabel, 'www', 'localhost', '127', '0']);
        if (in_array($subdomain, $systemDomains)) {
            return $next($request);
        }

        // 2. MANAGER BYPASS: Active deployment managers must always use the main domain.
        // If they land on a tenant subdomain (e.g. ojo.smartprobook.com/deployment/dashboard),
        // redirect them to the canonical main-domain URL instead of letting the request through
        // on the wrong host — which would poison sessions and show the wrong subdomain in URLs.
        if (Auth::check()) {
            $user = Auth::user();
            if (in_array(strtolower($user->role), ['state_manager', 'deployment_manager'])) {
                $manager = DeploymentManager::where('user_id', $user->id)->first();
                if ($manager && strtolower($manager->status) === 'active') {
                    // Clear any stale tenant context so dashboard URLs use the main domain
                    session()->forget(['current_tenant_id', 'current_tenant_name']);
                    // Redirect to the same path on the main domain
                    $mainUrl = rtrim((string) (config('app.url') ?: 'https://smartprobook.com'), '/');
                    $path = '/' . ltrim($request->getRequestUri(), '/');
                    return redirect($mainUrl . $path);
                }
            }
        }

        // 3. Identify workspace via Domain model for standard customers
        $tenant = null;
        try {
            if (Schema::hasTable('domains')) {
                $query = Domain::withoutGlobalScope('tenant');
                if (Schema::hasColumn('domains', 'domain_name')) {
                    $query->where('domain_name', $subdomain);
                } elseif (Schema::hasColumn('domains', 'domain')) {
                    $query->where('domain', $subdomain);
                } else {
                    Log::warning('IdentifyTenant: domains table missing domain column', [
                        'host' => $host,
                        'subdomain' => $subdomain,
                    ]);
                }
                $tenant = $query->first();
            }
        } catch (\Throwable $e) {
            Log::error('IdentifyTenant failed to resolve tenant', [
                'host' => $host,
                'subdomain' => $subdomain,
                'error' => $e->getMessage(),
            ]);

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'message' => 'Workspace lookup failed. Please try again.',
                ], 500);
            }

            return redirect()->to(config('app.url') ?: 'https://smartprobook.com')
                ->with('error', 'Workspace lookup failed. Please try again.');
        }

        if (!$tenant) {
            // Check if this is a manager route; if so, don't redirect to "not found"
            if ($request->is('manager/*') || $request->is('deployment/*')) {
                return $next($request);
            }

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'message' => 'Workspace not found for this request.',
                ], 404);
            }
            
            return redirect((config('app.url') ?: 'https://smartprobook.com') . '/workspace-not-found');
        }

        // Domain IDs and company IDs are different namespaces. Resolve the actual
        // company, then authorize it before changing any authenticated context.
        $companyId = (int) ($tenant->subscription()->withoutGlobalScope('tenant')->first()?->company_id ?? 0);
        $company = $companyId > 0
            ? Company::withoutGlobalScope('tenant')->find($companyId)
            : Company::withoutGlobalScope('tenant')
                ->where(function ($query) use ($subdomain) {
                    $query->where('domain_prefix', $subdomain)->orWhere('subdomain', $subdomain);
                })
                ->when((int) $tenant->tenant_id > 0, function ($query) use ($tenant) {
                    $query->where(fn ($owner) => $owner->where('user_id', $tenant->tenant_id)->orWhere('owner_id', $tenant->tenant_id));
                })->first();

        if (Auth::check()) {
            $resolver = app(\App\Support\BusinessWorkspaceResolver::class);
            if (! $company || ! $resolver->canAccess(Auth::user(), (int) $company->id)) {
                if ($this->shouldReturnJson($request)) {
                    return response()->json(['message' => 'You do not have access to this business workspace.'], 403);
                }
                return redirect()->to(rtrim(config('app.url'), '/').'/dashboard')
                    ->with('error', 'Please use a business workspace you have access to.');
            }
            if ((int) session('current_tenant_id') !== (int) $company->id) {
                $resolver->activate(Auth::user(), $company);
            } else {
                $resolver->bindCompany(Auth::user(), $company);
            }
        }

        View::share('currentTenant', $tenant);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';
    }
}
