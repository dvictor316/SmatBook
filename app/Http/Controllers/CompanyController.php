<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    /**
     * Review: The statistics logic is sound, but ensure 'address' 
     * is not just a whitespace string in your DB.
     */
    protected function getStatistics(): array
    {
        return [
            'totalCompanies' => Company::count(),
            'activeCompanies' => Company::where('status', 'active')->count(),
            'inactiveCompanies' => Company::where('status', 'inactive')->count(),
            'companiesWithAddress' => Company::whereNotNull('address')->where('address', '!=', '')->count(),
        ];
    }

    public function index(): View
    {
        $companies = Company::orderByDesc('created_at')->get();
        return view('SuperAdmin.companies', array_merge($this->getStatistics(), [
            'userName' => Auth::user()->name ?? 'Admin',
            'companies' => $companies,
        ]));
    }

    public function create(): View
    {
        return view('SuperAdmin.create-company', $this->getStatistics());
    }

    /**
     * CRITICAL FIX: Changed redirect() to use path string to avoid 
     * naming conflicts during the "MethodNotAllowed" troubleshooting.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCompany($request);
        
        Company::create($data);

        return redirect('/superadmin/companies')->with('success', 'Company created successfully!');
    }

    public function edit($id): View
    {
        $company = Company::findOrFail($id);
        return view('SuperAdmin.edit-company', array_merge(['company' => $company], $this->getStatistics()));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $company = Company::findOrFail($id);
        $data = $this->validateCompany($request, $company->id);

        // Logic Review: Clean up old logo if a new one is uploaded
        if ($request->hasFile('logo') && $company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $company->update($data);

        return redirect('/superadmin/companies')->with('success', 'Company updated successfully!');
    }

    public function destroy($id): RedirectResponse
    {
        $company = Company::findOrFail($id);
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }
        $company->delete();

        return redirect('/superadmin/companies')->with('success', 'Company deleted successfully!');
    }

    /**
     * Review: Added 'nullable' to ownership IDs to prevent 
     * foreign key crashes if no user is assigned yet.
     */
    private function validateCompany(Request $request, $id = null): array
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:191',
            'subdomain'       => ['nullable', 'string', 'max:191', Rule::unique('companies')->ignore($id)],
            'domain'          => 'nullable|string|max:191',
            'plan'            => 'required|string|in:basic,premium,enterprise',
            'email'           => 'nullable|email|max:191',
            'phone'           => 'nullable|string|max:191',
            'address'         => 'nullable|string',
            'country'         => 'nullable|string|max:191',
            'currency_symbol' => 'required|string|max:5',
            'currency_code'   => 'nullable|string|max:10',
            'status'          => 'required|string|in:active,inactive,suspended',
            'user_id'         => 'nullable|integer',
            'owner_id'        => 'nullable|integer',
            'logo'            => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        return $validated;
    }

    /**
     * Review: Impersonation logic requires the session to share 
     * the domain. Ensure your .env has SESSION_DOMAIN=.yourdomain.com
     */
    public function impersonate($id): RedirectResponse
    {
        if (!(bool) env('TEMP_OPEN_ACCESS', false)) {
            return redirect()->route('super_admin.dashboard')
                ->with('error', 'Temporary impersonation mode is disabled.');
        }

        $actor = Auth::user();
        if (!$actor || !in_array(strtolower((string) $actor->role), ['super_admin', 'superadmin'], true)) {
            abort(403, 'Unauthorized impersonation request.');
        }

        $company = Company::findOrFail($id);
        $user = User::find($company->user_id);

        if (!$user) {
            return redirect()->back()->with('error', 'This company has no associated admin user.');
        }

        if ((int) $user->id === (int) $actor->id) {
            return redirect()->route('super_admin.dashboard');
        }

        session()->put('impersonate_admin_id', $actor->id);
        session()->put('impersonator_user_id', $actor->id);

        Auth::login($user, true);
        request()->session()->regenerate();
        session()->put('is_impersonating', true);

        $sessionDomain = ltrim((string) env('SESSION_DOMAIN', ''), '.');
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $rootDomain = $sessionDomain !== '' ? $sessionDomain : $appHost;
        $scheme = request()->secure() || Str::lower((string) request()->header('X-Forwarded-Proto')) === 'https'
            ? 'https://'
            : 'http://';
        $targetDomain = $company->subdomain && $rootDomain !== ''
            ? $company->subdomain . '.' . $rootDomain
            : $rootDomain;

        if ($targetDomain === '') {
            return redirect()->route('home');
        }

        return redirect()->to($scheme . $targetDomain . '/home');
    }
}
