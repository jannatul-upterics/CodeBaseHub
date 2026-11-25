<?php

use App\Http\Controllers\Api\PageApiController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Page API Routes
|--------------------------------------------------------------------------
|
| These routes expose published page data from CodeBaseHub to external
| website frontends. No authentication is required. The website is
| identified via the X-Website-Domain header or ?domain= query param.
|
*/

Route::prefix('v1')->group(function () {
    Route::get('/pages', [PageApiController::class, 'index']);
    Route::get('/pages/homepage', [PageApiController::class, 'homepage']);
    Route::get('/pages/{slug}', [PageApiController::class, 'show']);
});

Route::get('/templates/{template}/pages', [TemplateController::class, 'pages'])->name('api.templates.pages');

Route::middleware('tenant')->group(function () {
    Route::post('/websites/{website}/sync-page', [PageApiController::class, 'syncFromTenant'])->name('api.websites.sync-page');
});
