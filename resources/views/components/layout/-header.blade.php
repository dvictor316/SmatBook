<?php

// app/View/Components/Layout/Header.php

namespace App\View\Components\Layout;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;

class Header extends Component
{
    public $user;
    public $notifications;

    public function __construct()
    {
        $this->user = Auth::user();
        
        // Fetch notifications using Eloquent relationship
        $this->notifications = $this->user 
            ? $this->user->notifications()->latest()->limit(5)->get() 
            : collect([]);
    }

    public function render()
    {
        return view('components.layout.header');
    }
}