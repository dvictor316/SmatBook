<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Graceful recovery for "Page Expired" (419), especially on registration flows.
        $this->renderable(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please refresh and try again.',
                ], 419);
            }

            $safeInput = $request->except(['password', 'password_confirmation', 'current_password']);
            $isAuthScreen = $request->routeIs([
                'login',
                'login-account',
                'saas-login',
                'register',
                'saas-register',
                'forgot-password',
                'password.reset',
            ]);

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Your session expired. Please try again.';

            if ($isAuthScreen) {
                return redirect()->to($request->url())
                    ->withInput($safeInput)
                    ->withErrors(['error' => $message]);
            }

            return back()
                ->withInput($safeInput)
                ->withErrors(['error' => $message]);
        });
    }
}
