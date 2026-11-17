<?php

namespace App\Providers;

use App\Models\Metadata;
use App\Models\Page;
use App\Models\PageSection;
use App\Observers\MetadataObserver;
use App\Observers\PageObserver;
use App\Observers\PageSectionObserver;
use App\Services\PageSyncService;
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
        $this->app->singleton(PageSyncService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Page::observe(PageObserver::class);
        PageSection::observe(PageSectionObserver::class);
        Metadata::observe(MetadataObserver::class);

        $this->app['url']->resolveMissingNamedRoutesUsing(function (string $name, mixed $parameters, bool $absolute) {
            if ($name === 'websites') {
                return route('websites.index', $parameters, $absolute);
            }

            return null;
        });
    }
}
