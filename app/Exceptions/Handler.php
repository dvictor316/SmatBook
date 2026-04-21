<?php

namespace App\Exceptions;

use App\Support\ActiveBranchResolver;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
            $message = 'Your session expired. Please login again to continue.';

            if ($this->shouldReturnJson($request)) {
                return response()->json(['message' => $message], 419);
            }

            app(\App\Http\Controllers\AuthController::class)->clearClientAuthState($request);

            return redirect()
                ->guest($this->resolveLoginRedirect($request, ['expired' => 1, 'flush' => 1]))
                ->withErrors(['login' => $message])
                ->withCookie($this->makeExpiredCookie((string) config('session.cookie')))
                ->withCookie($this->makeExpiredCookie('XSRF-TOKEN'));
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            $message = 'Your session expired. Please login again to continue.';

            if ($this->shouldReturnJson($request)) {
                return response()->json(['message' => $message], 401);
            }

            if ($request->hasSession()) {
                app(\App\Http\Controllers\AuthController::class)->clearClientAuthState($request);
            }

            return redirect()
                ->guest($this->resolveLoginRedirect($request, ['expired' => 1, 'flush' => 1]))
                ->withErrors(['login' => $message])
                ->withCookie($this->makeExpiredCookie((string) config('session.cookie')))
                ->withCookie($this->makeExpiredCookie('XSRF-TOKEN'));
        });

        $this->renderable(function (ValidationException $e, $request) {
            if (!$this->shouldReturnJson($request)) {
                return null;
            }

            $errors = $e->errors();
            $message = collect($errors)->flatten()->first() ?: 'The submitted data is invalid.';

            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], $e->status);
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if (!Auth::check()) {
                return null;
            }

            if (app(ActiveBranchResolver::class)->ensureSession(Auth::user())) {
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

            $message = 'Dashboard loaded without branch selection. You can choose a branch anytime from Settings.';

            if ($this->shouldReturnJson($request)) {
                return response()->json(['message' => $message], 404);
            }

            return redirect()
                ->route('home')
                ->with('info', $message);
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if (!Auth::check()) {
                return null;
            }

            $path = ltrim($request->path(), '/');

            if (str_starts_with($path, 'expenses/')) {
                if ($this->shouldReturnJson($request)) {
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

        $this->renderable(function (ModelNotFoundException $e, $request) {
            $model = class_basename($e->getModel());
            $ids = $e->getIds();
            $idText = $ids ? implode(',', $ids) : 'unknown';
            $message = "Record not found: {$model} ({$idText}).";

            if ($this->shouldReturnJson($request)) {
                return response()->json(['message' => $message], 404);
            }

            return back()->withInput()->with('error', $message);
        });

        $this->renderable(function (QueryException $e, $request) {
            // Handle duplicate entry (unique constraint violation) gracefully
            $errorCode = $e->errorInfo[1] ?? null;
            if ($errorCode == 1062) {
                $message = 'Duplicate entry detected. Please use a unique value.';
            } else {
                $message = $this->formatQueryExceptionMessage($e);
            }
            Log::error('Database error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            if ($this->shouldReturnJson($request)) {
                return response()->json(['message' => $message], 500);
            }

            if ($request->isMethod('get')) {
                return response()->view('errors.500', ['errorMessage' => $message], 500);
            }

            return back()->withInput()->with('error', $message);
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($e instanceof HttpException
                || $e instanceof NotFoundHttpException
                || $e instanceof TokenMismatchException
                || $e instanceof AuthenticationException
                || $e instanceof QueryException
                || $e instanceof ModelNotFoundException) {
                return null;
            }

            $raw = trim((string) $e->getMessage());
            $base = class_basename($e);
            $message = $raw !== '' ? "Unexpected error ({$base}): {$raw}" : "Unexpected error ({$base}).";

            Log::error('Unhandled exception', [
                'message' => $e->getMessage(),
                'exception' => $base,
            ]);

            if ($this->shouldReturnJson($request)) {
                return response()->json(['message' => $message], 500);
            }

            if ($request->isMethod('get')) {
                return response()->view('errors.500', ['errorMessage' => $message], 500);
            }

            return back()->withInput()->with('error', $message);
        });
    }

    private function formatQueryExceptionMessage(QueryException $e): string
    {
        $raw = $e->getMessage();

        if (preg_match("/Unknown column '([^']+)'/i", $raw, $matches)) {
            return "Database column missing: {$matches[1]}.";
        }

        if (preg_match("/Table '([^']+)' doesn't exist/i", $raw, $matches)) {
            return "Database table missing: {$matches[1]}.";
        }

        if (preg_match("/Integrity constraint violation: (\\d+)/i", $raw, $matches)) {
            return "Database constraint violation ({$matches[1]}).";
        }

        return 'Database error occurred. Please contact support if it persists.';
    }

    private function resolveLoginRedirect($request, array $query = []): string
    {
        $host = (string) $request->getHost();
        $mainDomain = ltrim((string) (config('app.domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'smartprobook.com'), '.');

        if ($host !== $mainDomain && str_contains($host, $mainDomain)) {
            return route('login', $query);
        }

        return route('saas-login', $query);
    }

    protected function shouldReturnJson($request, Throwable $e = null): bool
    {
        if ($request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest') {
            return true;
        }

        $path = trim((string) $request->path(), '/');

        return $path === 'inventory/products/category' || $path === 'categories' || $path === 'categories/store';
    }

    /**
     * Build a properly-attributed expired cookie so that Safari and other
     * mobile browsers actually delete the named cookie.
     *
     * Cookie::forget() uses domain=null by default, which does NOT match a
     * cookie that was originally set with Domain=.smartprobook.com — meaning
     * the browser keeps the old cookie alive and the 419 loop persists.
     * We must mirror the original session.domain, session.secure, and
     * session.same_site values so the attributes match exactly.
     */
    private function makeExpiredCookie(string $name): \Symfony\Component\HttpFoundation\Cookie
    {
        $domain   = (string) config('session.domain', '');
        $secure   = (bool)   config('session.secure', false);
        $sameSite = (string) config('session.same_site', 'lax');

        return \Symfony\Component\HttpFoundation\Cookie::create(
            $name,
            '',
            1,           // expires in the past
            '/',
            $domain !== '' ? $domain : null,
            $secure,
            true,        // httpOnly
            false,       // raw
            $sameSite ?: 'lax'
        );
    }
}
