<?php

// FILE: database/seeders/SuperAdminSeeder.php
// REWRITTEN to ensure Victor Yusuf is the unique Super Admin

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('internal.super_admin_email', 'donvictorlive@gmail.com');

        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        if (method_exists($user, 'trashed') && $user->trashed()) {
            $user->restore();
        }

        $attributes = [
            'name' => 'Victor Yusuf',
            'email' => $email,
            'password' => Hash::make('@Dononim1'),
            'role' => 'super_admin',
        ];

        foreach ([
            'role_id' => null,
            'status' => 'active',
            'is_verified' => 1,
            'email_verified_at' => now(),
            'verified_at' => now(),
            'allow_login' => 1,
            'is_protected_super_admin' => 1,
        ] as $column => $value) {
            if (Schema::hasColumn('users', $column)) {
                $attributes[$column] = $value;
            }
        }

        $user->forceFill($attributes)->save();

        echo "Protected super admin {$email} is ready.\n";
    }
}
