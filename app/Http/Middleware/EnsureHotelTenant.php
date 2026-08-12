<?php

namespace App\Http\Middleware;

use App\Support\HotelAccess;
use Closure;
use Illuminate\Http\Request;

class EnsureHotelTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        if (!HotelAccess::userIsHotelTenant($user)) {
            abort(403, 'Hotel module is not enabled for your company.');
        }

        return $next($request);
    }
}
