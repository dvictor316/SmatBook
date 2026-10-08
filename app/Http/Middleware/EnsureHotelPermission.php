<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureHotelPermission
{
    public function handle(Request $request, Closure $next, string $area)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $role = strtolower(str_replace([' ', '-'], '_', (string) ($user->role ?? '')));
        $managers = ['super_admin', 'administrator', 'admin', 'business_owner', 'owner', 'manager', 'hotel_manager', 'general_manager'];
        $areaRoles = [
            'frontdesk' => ['frontdesk', 'front_desk', 'receptionist', 'reservations'],
            'cashier' => ['cashier', 'accountant', 'finance'],
            'housekeeping' => ['housekeeper', 'housekeeping', 'room_attendant', 'housekeeping_supervisor'],
            'maintenance' => ['maintenance', 'engineer', 'engineering'],
            'night_audit' => ['night_auditor', 'auditor', 'accountant'],
            'commercial' => ['sales', 'sales_manager', 'revenue_manager', 'reservations'],
        ];

        if (in_array($role, array_merge($managers, $areaRoles[$area] ?? []), true)) {
            return $next($request);
        }

        if ($user->permissions_override !== null) {
            abort_unless($user->hasPermissionTo('hotel.'.$area.'.manage'), 403, 'You do not have permission for this hotel operation.');
        }

        // Preserve access for legacy hotel users until their role permissions are configured.
        return $next($request);
    }
}
