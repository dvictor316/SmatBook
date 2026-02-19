<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     * * We whitelist the login and registration routes so you don't 
     * lock yourself or your users out completely during updates.
     *
     * @var array<int, string>
     */
    protected $except = [
        'saas-login',    // Whitelist the login page
        'saas-register', // Whitelist the registration page
        'login',         // Standard fallback
        'logout'         // Allow users to end sessions
    ];
}