<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MetadataController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WebsiteDomainController;
use App\Http\Controllers\WebsiteMediaController;
use App\Http\Controllers\WebsiteMetadataController;
use App\Http\Controllers\WebsitePageController;
use App\Http\Controllers\WebsitePageSectionController;
use App\Http\Controllers\WebsiteSettingController;
use App\Http\Controllers\WebsiteThemeController;
use Illuminate\Support\Facades\Route;

// Guest authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Protected administrator routes
Route::middleware('auth')->group(function () {
    // 1. Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Customers
    Route::resource('customers', CustomerController::class);

    // 3. Websites
    Route::resource('websites', WebsiteController::class);
    Route::post('/websites/{website}/theme', [WebsiteController::class, 'assignTheme'])->name('websites.theme');

    // Website Management Dedicated Workspace
    Route::prefix('websites/{website}')->name('websites.')->group(function () {
        // Pages
        Route::get('/pages', [WebsitePageController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [WebsitePageController::class, 'create'])->name('pages.create');
        Route::post('/pages', [WebsitePageController::class, 'store'])->name('pages.store');
        Route::get('/pages/{page}', [WebsitePageController::class, 'show'])->name('pages.show');
        Route::get('/pages/{page}/edit', [WebsitePageController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [WebsitePageController::class, 'update'])->name('pages.update');
        Route::delete('/pages/{page}', [WebsitePageController::class, 'destroy'])->name('pages.destroy');

        // Page Sections
        Route::post('/pages/{page}/sections', [WebsitePageSectionController::class, 'store'])->name('pages.sections.store');
        Route::put('/pages/{page}/sections/{section}', [WebsitePageSectionController::class, 'update'])->name('pages.sections.update');
        Route::delete('/pages/{page}/sections/{section}', [WebsitePageSectionController::class, 'destroy'])->name('pages.sections.destroy');
        Route::post('/pages/{page}/sections/reorder', [WebsitePageSectionController::class, 'reorder'])->name('pages.sections.reorder');

        // Theme Configuration
        Route::get('/theme', [WebsiteThemeController::class, 'index'])->name('theme.index');
        Route::post('/theme/active', [WebsiteThemeController::class, 'setActiveTheme'])->name('theme.set_active');
        Route::post('/theme/settings', [WebsiteThemeController::class, 'updateSettings'])->name('theme.settings.update');

        // Domains
        Route::get('/domains', [WebsiteDomainController::class, 'index'])->name('domains.index');
        Route::post('/domains', [WebsiteDomainController::class, 'store'])->name('domains.store');
        Route::put('/domains/{domain}', [WebsiteDomainController::class, 'update'])->name('domains.update');
        Route::delete('/domains/{domain}', [WebsiteDomainController::class, 'destroy'])->name('domains.destroy');
        Route::post('/domains/{domain}/primary', [WebsiteDomainController::class, 'makePrimary'])->name('domains.primary');
        Route::post('/domains/{domain}/verify', [WebsiteDomainController::class, 'verifyDnsSsl'])->name('domains.verify');

        // Media Library
        Route::get('/media', [WebsiteMediaController::class, 'index'])->name('media.index');
        Route::post('/media', [WebsiteMediaController::class, 'store'])->name('media.store');
        Route::put('/media/{media}', [WebsiteMediaController::class, 'update'])->name('media.update');
        Route::delete('/media/{media}', [WebsiteMediaController::class, 'destroy'])->name('media.destroy');

        // Settings
        Route::get('/settings', [WebsiteSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [WebsiteSettingController::class, 'store'])->name('settings.store');
        Route::put('/settings/{setting}', [WebsiteSettingController::class, 'update'])->name('settings.update');
        Route::delete('/settings/{setting}', [WebsiteSettingController::class, 'destroy'])->name('settings.destroy');

        // SEO & Metadata
        Route::get('/seo', [WebsiteMetadataController::class, 'index'])->name('seo.index');
        Route::post('/seo', [WebsiteMetadataController::class, 'saveWebsiteMeta'])->name('seo.save_website');
        Route::post('/seo/page/{page}', [WebsiteMetadataController::class, 'savePageMeta'])->name('seo.save_page');
        Route::delete('/seo/{metadata}', [WebsiteMetadataController::class, 'destroy'])->name('seo.destroy');
    });

    // Dedicated Media File Delivery (Safe Streaming)
    Route::get('/media/file/{media}', [MediaController::class, 'showFile'])->name('media.file');

    // 4. Themes
    Route::resource('themes', ThemeController::class);

    // 5. Templates
    Route::resource('templates', TemplateController::class);

    // 6. Domains
    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');

    // 7. Pages
    Route::get('/pages', [PageController::class, 'index'])->name('pages.index');

    // 8. Media Library
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');

    // 9. Website Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');

    // 10. SEO and Metadata
    Route::get('/metadata', [MetadataController::class, 'index'])->name('metadata.index');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
