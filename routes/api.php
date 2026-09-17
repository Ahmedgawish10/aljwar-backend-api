<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All APIs are grouped by version: /api/v1, /api/v2, ...
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Al-Gewar API',
        'versions' => ['v1'],
        'current' => 'v1',
        'docs' => [
            'v1' => url('/api/v1'),
        ],
    ]);
});

Route::prefix('v1')->group(base_path('routes/api/v1.php'));
