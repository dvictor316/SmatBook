<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CalendarController extends Controller
{
    // Add this method so the route doesn't crash
    public function index()
    {
        return view('calendar.index'); 
    }
}