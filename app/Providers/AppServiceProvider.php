<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
