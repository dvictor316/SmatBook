<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\EmailVerificationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRegistrationEmailVerification
{
    public function __construct(private readonly EmailVerificationService $verification)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $this->verification->hasPending($user)) {
            return $next($request);
        }

        if ($request->routeIs('email-verification.*', 'logout')) {
            return $next($request);
        }

        return redirect()->route('email-verification.notice');
    }
}
