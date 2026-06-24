<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use LogicException;

class UserObserver
{
    public function saving(User $user): void
    {
        if (!$this->isProtectedSuperAdminAccount($user)) {
            return;
        }

        $protectedEmail = $this->protectedSuperAdminEmail();

        $user->email = $protectedEmail;
        $user->role = 'super_admin';

        if (Schema::hasColumn('users', 'role_id')) {
            $user->role_id = null;
        }

        if (Schema::hasColumn('users', 'status')) {
            $user->status = 'active';
        }

        if (Schema::hasColumn('users', 'is_verified')) {
            $user->is_verified = true;
        }

        if (Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
            $user->email_verified_at = now();
        }

        if (Schema::hasColumn('users', 'verified_at') && empty($user->verified_at)) {
            $user->verified_at = now();
        }

        if (Schema::hasColumn('users', 'allow_login')) {
            $user->allow_login = 1;
        }

        if (Schema::hasColumn('users', 'is_protected_super_admin')) {
            $user->is_protected_super_admin = true;
        }
    }

    /**
     * Handle the User "created" event.
     * This triggers automatically after a user is saved to the database.
     */
    public function created(User $user)
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

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

    public function deleting(User $user): void
    {
        if ($this->isProtectedSuperAdminAccount($user)) {
            throw new LogicException('The protected super admin account cannot be deleted.');
        }
    }

    private function isProtectedSuperAdminAccount(User $user): bool
    {
        $protectedEmail = $this->protectedSuperAdminEmail();
        $currentEmail = strtolower((string) ($user->email ?? ''));
        $originalEmail = strtolower((string) ($user->getOriginal('email') ?? ''));

        return $currentEmail === $protectedEmail
            || $originalEmail === $protectedEmail
            || (bool) ($user->is_protected_super_admin ?? false);
    }

    private function protectedSuperAdminEmail(): string
    {
        return strtolower((string) config('internal.super_admin_email', 'donvictorlive@gmail.com'));
    }
}
