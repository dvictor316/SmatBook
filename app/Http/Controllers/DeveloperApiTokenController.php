<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeveloperApiTokenController extends Controller
{
    public function index(): View
    {
        $companyId = $this->companyId();
        $tokens = Auth::user()->tokens()
            ->latest()
            ->get()
            ->filter(fn ($token) => in_array('company:' . $companyId, $token->abilities ?? [], true));

        return view('settings.developer-api', [
            'tokens' => $tokens,
            'plainTextToken' => session('plain_text_api_token'),
            'companyId' => $companyId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'expires_in' => 'required|in:30,90,365,never',
            'abilities' => 'required|array|min:1',
            'abilities.*' => 'in:products:read,customers:read,invoices:read',
        ]);
        $companyId = $this->companyId();
        $expiresAt = $validated['expires_in'] === 'never'
            ? null
            : Carbon::now()->addDays((int) $validated['expires_in']);

        $token = Auth::user()->createToken(
            $validated['name'],
            array_values(array_unique(array_merge($validated['abilities'], ['company:' . $companyId]))),
            $expiresAt
        );

        return redirect()->route('developer-api.index')
            ->with('plain_text_api_token', $token->plainTextToken)
            ->with('success', 'API token created. Copy it now; it will not be shown again.');
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $accessToken = Auth::user()->tokens()->findOrFail($token);
        abort_unless(in_array('company:' . $this->companyId(), $accessToken->abilities ?? [], true), 404);
        $accessToken->delete();

        return back()->with('success', 'API token revoked immediately.');
    }

    private function companyId(): int
    {
        $companyId = (int) (session('current_tenant_id') ?? Auth::user()?->company_id ?? 0);
        abort_if($companyId < 1, 403, 'An active business workspace is required.');
        return $companyId;
    }
}
