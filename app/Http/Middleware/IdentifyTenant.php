<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\{Domain, DeploymentManager}; 
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
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        if (empty($parts)) {
            return $next($request);
        }
        
        $subdomain = $parts[0];

        // 1. Skip system-level domains
        $systemDomains = ['smatbook', 'www', 'localhost', '127', '0'];
        if (in_array($subdomain, $systemDomains)) {
            return $next($request);
        }

        // 2. MANAGER BYPASS: If the user is an active manager on the main domain, let them pass
        if (Auth::check()) {
            $user = Auth::user();
            if (in_array(strtolower($user->role), ['deployment_manager', 'manager'])) {
                $manager = DeploymentManager::where('user_id', $user->id)->first();
                if ($manager && strtolower($manager->status) === 'active') {
                    // Managers don't always need a 'domain' record to see their management dashboard
                    return $next($request);
                }
            }
        }

        // 3. Identify workspace via Domain model for standard customers
        $tenant = null;
        try {
            if (Schema::hasTable('domains')) {
                $query = Domain::query();
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
            return redirect()->to(config('app.url') ?: 'https://smartprobook.com')
                ->with('error', 'Workspace lookup failed. Please try again.');
        }

        if (!$tenant) {
            // Check if this is a manager route; if so, don't redirect to "not found"
            if ($request->is('manager/*') || $request->is('deployment/*')) {
                return $next($request);
            }
            
            return redirect((config('app.url') ?: 'https://smartprobook.com') . '/workspace-not-found');
        }

        // 4. Set Global Data for Sidebars and Menus
        View::share('currentTenant', $tenant);

        // Required for accessing $request->get('tenant') in Controllers
        $request->attributes->set('tenant', $tenant);

        // 5. Session Persistence (domain => env('SESSION_DOMAIN', null))
        if (session('current_tenant_id') !== $tenant->id) {
            $tenantName = $tenant->domain_name
                ?? $tenant->customer_name
                ?? $subdomain;
            session(['current_tenant_id' => $tenant->id]);
            session(['current_tenant_name' => $tenantName]);
        }

        return $next($request);
    }
}
