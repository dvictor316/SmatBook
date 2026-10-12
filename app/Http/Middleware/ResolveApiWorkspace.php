<?php

namespace App\Http\Middleware;

use App\Support\BusinessWorkspaceResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveApiWorkspace
{
    public function __construct(private readonly BusinessWorkspaceResolver $workspaceResolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        $abilities = is_array($token?->abilities) ? $token->abilities : [];
        $companyAbility = collect($abilities)->first(
            fn ($ability) => is_string($ability) && str_starts_with($ability, 'company:')
        );
        $companyId = (int) str($companyAbility ?? '')->after('company:')->toString();

        if (! $user || $companyId < 1 || ! $this->workspaceResolver->canAccess($user, $companyId)) {
            return response()->json([
                'message' => 'This API token is not bound to an accessible business workspace.',
            ], 403);
        }

        $request->attributes->set('api_company_id', $companyId);

        return $next($request);
    }
}
