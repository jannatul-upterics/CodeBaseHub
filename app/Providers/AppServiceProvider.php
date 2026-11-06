<?php

namespace App\Providers;

use App\Services\WebsiteProvisioningService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WebsiteProvisioningService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $this->app['url']->resolveMissingNamedRoutesUsing(function (string $name, mixed $parameters, bool $absolute) {
            if ($name === 'websites') {
                return route('websites.index', $parameters, $absolute);
            }

            return null;
        });
    }
}
