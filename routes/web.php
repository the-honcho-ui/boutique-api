<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/shareable-media/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    $origin = env('FRONTEND_URL', 'http://localhost:5173');
    $response = response()->file(Storage::disk('public')->path($path), [
        'Access-Control-Allow-Origin' => $origin,
        'Cross-Origin-Resource-Policy' => 'cross-origin',
        'Vary' => 'Origin',
        'Cache-Control' => 'public, max-age=86400',
    ]);

    return $response;
})->where('path', '.*');
