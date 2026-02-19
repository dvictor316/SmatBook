<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * This triggers automatically after a user is saved to the database.
     */
    public function created(User $user)
    {
        // 1. Safety Check: Only create a company if the user doesn't already have one
        if ($user->companies()->count() === 0) {
            
            Company::create([
                'user_id' => $user->id,
                'name'    => $user->name . ' Company',
                'email'   => $user->email, // Works now because we made the DB column nullable
                'domain'  => Str::slug($user->name) . '-' . rand(100, 999),
                'plan'    => $user->role === 'admin' ? 'accelerate' : 'basic',
                'status'  => 'pending_payment',
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user)
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user)
    {
        // Optional: Delete companies if the user is deleted
        // $user->companies()->delete();
    }
}