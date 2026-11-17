<?php

use App\Http\Controllers\Api\PageApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vocabot Public API Routes
|--------------------------------------------------------------------------
|
| These routes expose page data from CodeBaseHub to external consumers
| such as Vocabot. No authentication is required. The website is
| identified via the X-Website-Domain header or ?domain= query param.
|
*/

Route::prefix('v1')->group(function () {
    Route::get('/pages', [PageApiController::class, 'index']);
    Route::get('/pages/homepage', [PageApiController::class, 'homepage']);
    Route::get('/pages/{slug}', [PageApiController::class, 'show']);
});
