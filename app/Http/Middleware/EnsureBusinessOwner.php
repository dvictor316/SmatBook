<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureBusinessOwner
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->canManageRoles(), 403, 'Only the business owner can manage roles and permissions.');

        return $next($request);
    }
}
