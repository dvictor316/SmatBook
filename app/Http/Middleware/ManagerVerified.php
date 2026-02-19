<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log};
use App\Models\DeploymentManager;

class ManagerVerified
{
    /**
     * Handle an incoming request.
     * * THE TRAP FIX: 
     * Previously, if 'is_verified' was 0 in the users table, managers were stuck 
     * even if their deployment record was 'active'. This rewrite prioritizes 
     * the DeploymentManager status for smoother onboarding.
     */
    public function handle(Request $request, Closure $next)
    {
        if ((bool) env('TEMP_OPEN_ACCESS', false)) {
            return $next($request);
        }

        $user = Auth::user();

        // 1. Ensure user is logged in
        if (!$user) {
            return redirect()->route('saas-login')
                ->with('error', 'Please login to continue.');
        }

        // 2. Sync with DB to catch recent SuperAdmin approvals
        $user->refresh();

        // 3. SuperAdmin & Role Bypass
        $role = strtolower($user->role);
        if (in_array($role, ['superadmin', 'super_admin'])) {
            return $next($request);
        }

        $allowedRoles = ['deployment_manager', 'manager'];
        if (!in_array($role, $allowedRoles)) {
            return redirect()->route('home')
                ->with('error', 'Unauthorized access.');
        }

        // 4. Prevent Infinite Redirect Loops
        $excludedRoutes = [
            'manager.pending.notice',
            'manager.verification.form',
            'manager.verification.submit',
            'logout'
        ];

        if ($request->routeIs($excludedRoutes)) {
            return $next($request);
        }

        /**
         * 5. DYNAMIC STATUS CHECK
         * We look at the deployment_managers table. If status is 'active',
         * we ignore the email_verified/is_verified user flags to allow access.
         */
        $manager = DeploymentManager::where('user_id', $user->id)->first();
        $status = $manager ? strtolower($manager->status) : 'unregistered';

        if ($status === 'active') {
            return $next($request);
        }

        // 6. REDIRECTION BASED ON STATUS
        if ($status === 'pending_info') {
            return redirect()->route('manager.verification.form')
                ->with('info', 'Please complete your profile details to proceed.');
        }

        // Default: Awaiting Approval (pending, suspended, or null)
        return redirect()->route('manager.pending.notice')
            ->with('warning', 'Your account is currently awaiting administrative approval.');
    }
}
