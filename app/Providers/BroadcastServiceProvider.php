<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Explicitly name the broadcasting route so Pusher can find it
        Broadcast::routes(['as' => 'pusher.auth']);

        require base_path('routes/channels.php');
    }
}