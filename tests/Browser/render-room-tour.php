<?php
// Render a viewer fixture with synthetic media URLs; never connects to a production database.
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
if ($app->configurationIsCached()) {
    throw new RuntimeException('Use a local checkout without cached production configuration.');
}
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['app.url' => 'http://localhost']);
$room = new App\Models\HotelRoom(['room_number' => '101', 'panorama_image' => 'tour-panorama.png']);
$room->setRelation('images', collect([
    new App\Models\HotelRoomImage(['path' => 'tour-photo.png', 'caption' => 'Window view']),
    new App\Models\HotelRoomImage(['path' => 'missing.png', 'caption' => 'Missing photo']),
]));
$viewer = view('hotel.partials.room-image-viewer', ['room' => $room, 'imageUrl' => 'http://localhost/storage/tour-panorama.png', 'imageAlt' => 'Room 101 panorama'])->render(function ($view, $contents) use (&$tourStyles, &$tourScripts) {
    $tourStyles = view()->yieldPushContent('styles');
    $tourScripts = view()->yieldPushContent('scripts');
    return $contents;
});
$html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>*{box-sizing:border-box}body{margin:0;padding:16px;background:#eef4f8;font-family:Arial,sans-serif}button{cursor:pointer}button:disabled{opacity:.4}.visually-hidden{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}</style>'.$tourStyles.'</head><body>'.$viewer.$tourScripts.'</body></html>';
$output = getenv('RESPONSIVE_FIXTURES') ?: sys_get_temp_dir().'/smartprobook-responsive';
if (!is_dir($output)) mkdir($output, 0700, true);
file_put_contents($output.'/room-tour.html', $html);
echo "Room tour fixture rendered.\n";
