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
