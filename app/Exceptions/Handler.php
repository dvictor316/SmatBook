<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if (!Auth::check()) {
                return null;
            }

            $branchId = trim((string) session('active_branch_id', ''));
            $branchName = trim((string) session('active_branch_name', ''));

            if ($branchId !== '' || $branchName !== '') {
                return null;
            }

            $path = ltrim($request->path(), '/');
            $allow = [
                'settings/branches',
                'settings/branches/activate',
                'branches',
                'settings',
            ];

            foreach ($allow as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                    return null;
                }
            }

            $message = 'Please select an active branch to continue.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 404);
            }

            $target = route('branches.index', [], false);
            if (!$target) {
                $target = url('/settings/branches');
            }

            return redirect()
                ->to($target)
                ->with('error', $message);
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if (!Auth::check()) {
                return null;
            }

            $path = ltrim($request->path(), '/');

            if (str_starts_with($path, 'expenses/')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Open the Expenses page and use the Edit action from the list.',
                    ], 404);
                }

                return redirect()
                    ->route('expenses.index')
                    ->with('info', 'Open the Expenses page and use the Edit action from the list.');
            }

            return null;
        });
    }
}
