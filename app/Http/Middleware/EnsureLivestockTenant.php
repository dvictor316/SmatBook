<?php

namespace App\Http\Middleware;

use App\Support\LivestockAccess;
use Closure;

class EnsureLivestockTenant
{
    public function handle($request, Closure $next)
    {
        abort_unless($request->user() && LivestockAccess::userIsLivestockTenant($request->user()), 403, 'Livestock module is not enabled for your company.');

        return $next($request);
    }
}
