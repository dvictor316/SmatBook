<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * Display the calendar view.
     */
    public function index()
    {
        return view('calendar'); // Ensure this matches your blade filename
    }

    /**
     * Fetch events for FullCalendar JSON feed.
     */
    public function getEvents()
    {
        $events = Event::where('user_id', Auth::id())->get()->map(function($event) {
            return [
                'id'        => $event->id,
                'title'     => $event->title,
                'start'     => $event->start,
                'end'       => $event->end,
                'className' => $event->category_color, // This applies the CSS class for colors
                'allDay'    => true
            ];
        });

        return response()->json($events);
    }

public function store(Request $request) 
{
    // 1. Validate the incoming data
    $validated = $request->validate([
        'title' => 'required|string',
        'start' => 'required',
        'category_color' => 'nullable|string'
    ]);

    // 2. Create the event using the validated data + authenticated user ID
    $event = Event::create([
        'title'          => $validated['title'],
        'start'          => $validated['start'],
        // Ensure end is handled even if null
        'end'            => $request->end, 
        // Default to bg-primary if category_color is missing
        'category_color' => $request->category_color ?? 'bg-primary',
        'user_id'        => auth()->id()
    ]);

    // 3. Return the created event as JSON
    return response()->json($event);
}

public function update(Request $request, string $id)
{
    $event = Event::where('user_id', Auth::id())->findOrFail($id);
    
    $event->update([
        // If title is sent, update it; otherwise keep the old one
        'title' => $request->title ?? $event->title,
        'start' => $request->start ?? $event->start,
        'end'   => $request->end   ?? $event->end,
    ]);

    return response()->json(['status' => 'success']);
}

    /**
     * Remove the specified event from storage.
     */
    public function destroy(string $id)
    {
        $event = Event::where('user_id', Auth::id())->findOrFail($id);
        $event->delete();

        return response()->json(['status' => 'success']);
    }
}