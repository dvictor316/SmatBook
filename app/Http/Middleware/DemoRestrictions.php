<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Support\DemoSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts demo users from performing sensitive or destructive actions.
 *
 * Blocked routes (by name pattern):
 *  - superadmin.*
 *  - subscription.*
 *  - saas.*
 *  - database.reset*
 *  - financial.reset*
 *  - backups.*
 *  - reports.custom.store / reports.custom.run
 */
class DemoRestrictions
{
    public function __construct(private readonly DemoSettings $demoSettings)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return $next($request);
        }

        $company = \App\Models\Company::find($user->company_id);

        if (!$company || !$company->isDemo()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        $blockedPrefixes = $this->demoSettings->blockedRoutePrefixes();
        $blockedRoutes = $this->demoSettings->blockedRoutes();

        $isBlocked = collect($blockedPrefixes)
            ->contains(fn ($prefix) => str_starts_with($routeName, $prefix))
            || in_array($routeName, $blockedRoutes, true);

        if ($isBlocked) {
            ActivityLog::record('Demo', 'restricted_action_attempted', "Demo user {$user->email} tried to access restricted route: {$routeName}", [
                'user_id'    => $user->id,
                'company_id' => $user->company_id,
                'properties' => ['route' => $routeName, 'url' => $request->fullUrl()],
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'This action is not available in demo mode.'], 403);
            }

            return redirect()->back()
                ->with('error', 'This action is not available in demo mode. Please upgrade to a full account.');
        }

        return $next($request);
    }
}
