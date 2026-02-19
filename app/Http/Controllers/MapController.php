<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class MapController extends Controller
{
    public function index()
    {
        $path = public_path('assets/json/inbox.json');
        
        // Use Laravel's File facade for cleaner syntax
        $rawData = File::exists($path) ? json_decode(File::get(path), true) : [];

        // Ensure we always have an array and add coordinates for the map
        $messages = collect($rawData)->map(function ($item) {
            return [
                'Name'          => $item['Name'] ?? 'Unknown',
                'Content'       => $item['Content'] ?? '',
                'Time'          => $item['Time'] ?? '',
                'Class'         => $item['Class'] ?? '',
                'StarClass'     => $item['StarClass'] ?? 'far fa-star',
                'HasAttachment' => $item['HasAttachment'] ?? false,
                // Assign consistent coordinates based on name or random for demo
                'lat'           => $item['lat'] ?? (float) ('0.' . substr(crc32($item['Name'] ?? 'a'), 0, 2)) * 100 - 20,
                'lng'           => $item['lng'] ?? (float) ('0.' . substr(crc32($item['Content'] ?? 'b'), 0, 2)) * 200 - 100,
            ];
        });

        return view('maps-vector', ['messages' => $messages]);
    }
}