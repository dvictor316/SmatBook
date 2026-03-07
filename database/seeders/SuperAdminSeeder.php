<?php

// FILE: database/seeders/SuperAdminSeeder.php
// REWRITTEN to ensure Victor Yusuf is the unique Super Admin

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Remove any existing user with this email to prevent "Duplicate Entry"
        User::where('email', 'donvictorlive@gmail.com')->delete();

        // 2. Create the unique Super Admin account
        $user = User::create([
            'name' => 'Victor Yusuf',
            'email' => 'donvictorlive@gmail.com',
            'password' => Hash::make('@Dononim1'),
            'role' => 'super_admin', // Ensure your 'users' table has a 'role' column
            'email_verified_at' => now(),
        ]);

        echo "Super Admin Victor Yusuf created successfully.\n";
    }
}