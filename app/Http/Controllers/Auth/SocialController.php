<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    /**
     * Redirect the user to the Provider's authentication page.
     */
    public function redirectToProvider($provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return redirect()->route('login')->with('error', 'Login provider not supported.');
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from the Provider.
     */
    public function handleProviderCallback($provider)
    {
        // 1. Handle user cancellation or provider errors
        if (request()->has('error') || request()->has('error_code')) {
            return redirect()->route('login')
                ->with('error', 'Login was cancelled or failed.');
        }

        try {
            // 2. Get user data from Socialite
            $socialUser = Socialite::driver($provider)->user();
        } catch (Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Authentication failed. Please try again.');
        }

        // 3. Email Safety Check
        // If the provider doesn't give an email, we create a unique one to prevent SQL errors.
        $email = $socialUser->getEmail() ?: $provider . '_' . $socialUser->getId() . '@example.com';

        // 4. Find or Create User
        $user = User::where('email', $email)->first();

        if ($user) {
            // Update provider info if this is their first time using this social login
            if (!$user->provider_id) {
                $user->update([
                    'provider_id'   => $socialUser->getId(),
                    'provider_name' => $provider,
                ]);
            }
            Auth::login($user);
        } else {
            // Create a new user (This will also trigger any UserObservers for Companies)
            $newUser = User::create([
                'name'              => $socialUser->getName() ?? 'User_' . Str::random(5),
                'email'             => $email,
                'provider_id'       => $socialUser->getId(),
                'provider_name'     => $provider,
                'password'          => Hash::make(Str::random(24)),
                'email_verified_at' => now(),
            ]);
            
            Auth::login($newUser);
        }

        // 5. Redirect to Dashboard
        return redirect()->route('home')->with('success', "Logged in successfully via " . ucfirst($provider));
    }
}