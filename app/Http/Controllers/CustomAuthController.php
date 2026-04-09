<?php

namespace App\Http\Controllers;

use App\Models\{User, Company, Plan, Invoice};
use App\Support\DeviceSessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{
    Auth,
    DB,
    Hash,
    Log,
    Password,
    Redirect,
    Schema,
    Session,
    Storage
};
use Laravel\Socialite\Facades\Socialite;
use Carbon\Carbon;

class CustomAuthController extends Controller
{
    /* ===============================
     * AUTHENTICATION - LOGIN
     * =============================== */

    /**
     * Display the login form.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $request->session()->regenerateToken();

        return response()
            ->view('Pages.Authentication.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /**
     * Handle custom login request.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function customLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            $deviceSession = app(DeviceSessionManager::class)->ensureCurrentSession($request, Auth::user());
            if (($deviceSession['allowed'] ?? true) !== true) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->withErrors(['email' => (string) ($deviceSession['message'] ?? 'This account cannot be used on another device right now.')])
                    ->withInput($request->except('password'));
            }

            return redirect()
                ->intended(route('home'))
                ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => 'The provided credentials do not match our records.'])
            ->withInput($request->except('password'));
    }

    /**
     * Handle signout request.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function signOut(Request $request)
    {
        app(DeviceSessionManager::class)->forgetCurrentSession($request);
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'You have been logged out successfully.');
    }

    /* ===============================
     * AUTHENTICATION - REGISTRATION
     * =============================== */

    /**
     * Display the registration form.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function showRegistrationForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $request->session()->regenerateToken();

        return response()
            ->view('Pages.Authentication.register')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /**
     * Handle custom registration request.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function customRegistration(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'confirmed',
                \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers(),
            ],
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'terms' => 'accepted',
        ], [
            'password.*' => 'Password must be at least 8 characters and include letters and numbers.',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(), // Auto-verify or send verification email
            ]);

            if ($request->hasFile('profile_photo')) {
                $user->profile_photo = $request->file('profile_photo')->store('users/profiles', 'public');
                $user->save();
            }

            DB::commit();

            // Log the user in automatically
            Auth::login($user);

            return redirect()
                ->intended(route('home'))
                ->with('success', 'Registration successful! Welcome to our platform.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration error: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors(['error' => 'Registration failed. Please try again.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Alternative registration method (legacy support).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function registerCustom(Request $request)
    {
        return $this->customRegistration($request);
    }

    /* ===============================
     * AUTHENTICATION - PASSWORD RESET
     * =============================== */

    /**
     * Show forgot password form.
     *
     * @return \Illuminate\View\View
     */
    public function showForgotPasswordForm()
    {
        return view('Pages.Authentication.forgot-password');
    }

    /**
     * Send reset link email.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Update password from reset token.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                // Optionally invalidate all sessions
                // $user->tokens()->delete();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    /* ===============================
     * AUTHENTICATION - SOCIAL LOGIN
     * =============================== */

    /**
     * Redirect to social provider.
     *
     * @param string $provider
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToProvider(string $provider)
    {
        $allowedProviders = ['google', 'facebook', 'twitter', 'github'];

        if (!in_array($provider, $allowedProviders)) {
            return redirect()
                ->route('login')
                ->withErrors(['error' => 'Invalid social provider.']);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the callback from the social provider.
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                    $provider . '_id' => $socialUser->getId(),
                    'email_verified_at' => now(),
                    'password' => Hash::make(str()->random(24)), // Random password for social users
                    'avatar_url' => $socialUser->getAvatar(),
                ]
            );

            Auth::login($user);

            return redirect()
                ->intended(route('home'))
                ->with('success', 'Successfully logged in via ' . ucfirst($provider) . '!');

        } catch (\Exception $e) {
            Log::error("Social Login Error ({$provider}): " . $e->getMessage());

            return redirect()
                ->route('login')
                ->withErrors(['error' => "Unable to login with {$provider}. Please try again."]);
        }
    }

    /* ===============================
     * AUTHENTICATION - SCREEN LOCK
     * =============================== */

    /**
     * Lock the screen.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function lockScreen(Request $request)
    {
        $user = Auth::user();

        // Store user info in session before logging out
        Session::put([
            'lock_user_id' => $user->id,
            'lock_user_name' => $user->name,
            'lock_user_email' => $user->email,
            'lock_user_avatar' => $user->avatar_url ?? $user->profile_photo_url,
            'lock_time' => now(),
        ]);

        Auth::logout();

        return view('Pages.Authentication.lock-screen', [
            'userName' => Session::get('lock_user_name'),
            'userAvatar' => Session::get('lock_user_avatar'),
        ]);
    }

    /**
     * Unlock the screen.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unlock(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $email = Session::get('lock_user_email');

        if (!$email) {
            return redirect()
                ->route('login')
                ->withErrors(['error' => 'Session expired. Please login again.']);
        }

        if (Auth::attempt(['email' => $email, 'password' => $request->password])) {
            // Clear lock session data
            Session::forget([
                'lock_user_id',
                'lock_user_name',
                'lock_user_email',
                'lock_user_avatar',
                'lock_time'
            ]);

            return redirect()
                ->intended(route('home'))
                ->with('success', 'Screen unlocked successfully!');
        }

        return back()->withErrors([
            'password' => 'Invalid password. Please try again.'
        ]);
    }

    /* ===============================
     * DASHBOARD & ANALYTICS
     * =============================== */

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        try {
            $userName = Auth::user()->name ?? 'Admin';

            // Company Statistics
            $companies = Company::all();
            $totalCompanies = $companies->count();
            $activeCompanies = $companies->where('status', 'active')->count();
            $inactiveCompanies = $companies->where('status', 'inactive')->count();
            $newCompaniesSubscribed = Company::whereDate('created_at', today())->count();

            // Company Growth Rate
            $previousMonthCompanies = Company::whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])->count();

            $currentMonthCompanies = Company::whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])->count();

            $growthRate = $previousMonthCompanies > 0
                ? round((($currentMonthCompanies - $previousMonthCompanies) / $previousMonthCompanies) * 100, 1) . '%'
                : '0%';

            // Active Plans
            $activePlans = Schema::hasTable('plans')
                ? Plan::where('status', 'active')->count()
                : 0;

            // Latest Companies
            $latestCompanies = Company::with('plan')
                ->latest()
                ->take(5)
                ->get();

            // Company Growth Chart Data
            $companiesPerMonth = Company::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($data) => [
                'month' => Carbon::create()->month($data->month)->format('F'),
                'count' => (int) $data->count,
            ]);

            // Top Plans by Orders
            $topPlans = Schema::hasTable('plans')
                ? Plan::withCount('orders')
                    ->orderByDesc('orders_count')
                    ->take(5)
                    ->get()
                : collect([]);

            // Top Companies
            $topCompanies = Company::with('users')
                ->withCount('users')
                ->orderByDesc('users_count')
                ->take(5)
                ->get();

            // Top Domains
            $topDomains = Schema::hasTable('domains')
                ? DB::table('domains')
                    ->select('company_name', 'company_image_url', 'plan_count', 'domain_name', 'user_count')
                    ->orderByDesc('user_count')
                    ->limit(5)
                    ->get()
                : collect([]);

            // Recent Invoices
            $recentInvoices = Schema::hasTable('invoices')
                ? Invoice::with(['company', 'plan'])
                    ->latest()
                    ->take(5)
                    ->get()
                : collect([]);

            // Recently Expired Plans
            $recentExpiredPlans = Schema::hasTable('plans')
                ? Plan::with('company')
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', now())
                    ->orderByDesc('expiry_date')
                    ->take(5)
                    ->get()
                : collect([]);

            // Filter Options
            $years = range(now()->year - 4, now()->year);
            $planTimeframes = ['This Month', 'Last Month', 'This Year'];
            $companyTimeframes = ['Today', 'This Week', 'This Month'];
            $domainTimeframes = ['This Week', 'This Month', 'This Year'];

            // Default Values
            $year = now()->year;
            $planTimeframe = 'This Month';
            $companyTimeframe = 'Today';
            $domainTimeframe = 'This Week';

            return view('SuperAdmin.dashboard', compact(
                'userName',
                'companies',
                'totalCompanies',
                'activeCompanies',
                'inactiveCompanies',
                'newCompaniesSubscribed',
                'growthRate',
                'activePlans',
                'latestCompanies',
                'companiesPerMonth',
                'topPlans',
                'topCompanies',
                'topDomains',
                'recentInvoices',
                'recentExpiredPlans',
                'years',
                'planTimeframes',
                'companyTimeframes',
                'domainTimeframes',
                'year',
                'planTimeframe',
                'companyTimeframe',
                'domainTimeframe'
            ));

        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Something went wrong while loading the dashboard. Please try again.');
        }
    }

    /**
     * Alias for index() method.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function dashboard()
    {
        return $this->index();
    }

    /* ===============================
     * MAP & LOCATIONS
     * =============================== */

    /**
     * Show map with vectors/locations.
     *
     * @return \Illuminate\View\View
     */
    public function showMapVectors()
    {
        // In production, fetch this from your database
        $messages = [
            [
                'Name' => 'Lagos Corporate Office',
                'Content' => 'New subscription activated.',
                'Time' => 'Just now',
                'Class' => 'unread',
                'lat' => 6.5244,
                'lng' => 3.3792,
                'HasAttachment' => true
            ],
            [
                'Name' => 'London Branch',
                'Content' => 'Monthly report generated.',
                'Time' => '45 mins ago',
                'Class' => 'read',
                'lat' => 51.5074,
                'lng' => -0.1278,
                'HasAttachment' => false
            ],
            [
                'Name' => 'New York Site',
                'Content' => 'Payment received for Invoice #102',
                'Time' => '2 hours ago',
                'Class' => 'unread',
                'lat' => 40.7128,
                'lng' => -74.0060,
                'HasAttachment' => true
            ]
        ];

        return view('map-vectors', compact('messages'));
    }

    /* ===============================
     * PROFILE MANAGEMENT
     * =============================== */

    /**
     * Update user profile images (profile photo and cover photo).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfileImages(Request $request)
    {
        $request->validate([
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        try {
            $user = Auth::user();

            DB::beginTransaction();

            // Process Profile Photo
            if ($request->hasFile('profile_photo')) {
                // Delete old photo
                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }

                $path = $request->file('profile_photo')->store('profiles', 'public');
                $user->profile_photo = $path;
            }

            // Process Cover Photo
            if ($request->hasFile('cover_photo')) {
                // Delete old cover
                if ($user->cover_photo && Storage::disk('public')->exists($user->cover_photo)) {
                    Storage::disk('public')->delete($user->cover_photo);
                }

                $path = $request->file('cover_photo')->store('covers', 'public');
                $user->cover_photo = $path;
            }

            $user->save();

            DB::commit();

            return back()->with('success', 'Profile images updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profile image update error: ' . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);

            return back()->withErrors([
                'error' => 'Failed to update images. Please try again.'
            ]);
        }
    }

    /**
     * Update user profile information.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
        ]);

        try {
            $user = Auth::user();
            $user->update($validated);

            return back()->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());

            return back()->withErrors([
                'error' => 'Failed to update profile. Please try again.'
            ]);
        }
    }

    /**
     * Change user password.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed|different:current_password',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.'
            ]);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return back()->with('success', 'Password changed successfully!');

        } catch (\Exception $e) {
            Log::error('Password change error: ' . $e->getMessage());

            return back()->withErrors([
                'error' => 'Failed to change password. Please try again.'
            ]);
        }
    }

public function logout(Request $request)
{
    app(DeviceSessionManager::class)->forgetCurrentSession($request);
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login')->with('success', 'You have been logged out successfully.');
}
}
