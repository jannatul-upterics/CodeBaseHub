<?php

namespace App\Providers;

use App\Models\Website;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class TemplateServiceProvider extends ServiceProvider
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
        // Register a view composer that provides the template namespace
        View::composer('*', function ($view) {
            $website = request()->attributes->get('website');
            if (! $website instanceof Website) {
                // No website context – fall back to a default template if needed
                $templateSlug = Config::get('app.default_template', 'vocabot');
            } else {
                $template = $website->template; // relationship on Website model
                $templateSlug = $template ? $template->slug : Config::get('app.default_template', 'vocabot');
            }

            $templatePath = resource_path('views/templates/'.$templateSlug);
            if (is_dir($templatePath)) {
                View::addNamespace('template', $templatePath);
            }
        });
    }
}
