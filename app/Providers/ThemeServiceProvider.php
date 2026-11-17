<?php

namespace App\Providers;

use App\Models\Website;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register a view composer that will set the theme namespace
        View::composer('*', function ($view) {
            // Determine the current website via route model binding or session
            $website = request()->route('website') ?? null;
            if (! $website instanceof Website) {
                // Fallback to a default theme defined in config or env
                $themeSlug = Config::get('app.default_theme', 'gothic');
            } else {
                $activeTheme = $website->activeTheme();
                $themeSlug = $activeTheme ? $activeTheme->slug : Config::get('app.default_theme', 'gothic');
            }

            // Register the theme view namespace
            $themePath = resource_path('views/themes/'.$themeSlug);
            if (is_dir($themePath)) {
                View::addNamespace('theme', $themePath);
            }
        });
    }
}
