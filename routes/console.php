<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// THIS IS THE NEW SECTION YOU NEED:
Artisan::command('smat:fix', function () {
    $this->info('Starting smat-book system refresh...');

    // 1. Clear all caches (Safe: does not delete database rows)
    $this->call('optimize:clear');

    // 1b. Rebuild package manifests to prevent "Target class [view] does not exist"
    // on environments where chmod on temp cache files may fail intermittently.
    @array_map('unlink', glob(base_path('bootstrap/cache/packages.php*')) ?: []);
    @unlink(base_path('bootstrap/cache/services.php'));
    $this->call('package:discover');
    
    // 2. Fix Storage Link
    if (!file_exists(public_path('storage'))) {
        $this->call('storage:link');
        $this->info('✓ Storage link created.');
    }

    $this->comment('smat-book system is refreshed and ready!');
})->purpose('Clear caches and link storage for the smat-book project');

Artisan::command('hotel:demo-data {--company= : Company ID to seed into} {--branch= : Optional numeric branch ID} {--fresh : Remove previous SmartProbook hotel demo records before seeding} {--cleanup : Delete only SmartProbook hotel demo records}', function () {
    $company = $this->option('company') ? (int) $this->option('company') : null;
    $branch = $this->option('branch');
    $seeder = app(\App\Support\HotelDemoDataSeeder::class);

    if ($this->option('cleanup')) {
        $deleted = $seeder->cleanup($company);
        $this->info('SmartProbook hotel demo data removed.');
        foreach ($deleted as $table => $count) {
            $this->line("{$table}: {$count}");
        }
        return 0;
    }

    $summary = $seeder->seed($company, $branch, (bool) $this->option('fresh'));
    $this->info('SmartProbook hotel demo data seeded successfully.');
    foreach ($summary as $label => $value) {
        $this->line(str_replace('_', ' ', ucfirst($label)) . ': ' . ($value ?? 'none'));
    }

    $this->comment('Use php artisan hotel:demo-data --cleanup to remove these demo records later.');
    return 0;
})->purpose('Seed or clean removable SmartProbook hotel demo PMS data');
