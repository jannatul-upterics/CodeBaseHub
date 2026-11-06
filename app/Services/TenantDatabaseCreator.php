<?php

namespace App\Services;

use App\Models\Website;
use Exception;

class TenantDatabaseCreator
{
    /**
     * Create a tenant database for the given website and run tenant migrations.
     *
     * @throws Exception on failure – caller should handle and roll back.
     */
    public static function createForWebsite(Website $website, array $config = []): void
    {
        /** @var WebsiteProvisioningService $service */
        $service = app(WebsiteProvisioningService::class);
        $service->provisionNewDatabase($website, $config);
    }

    /**
     * Generate a unique database name for a website.
     * Example: codebasehub_website_12
     */
    public static function generateDatabaseName(Website $website): string
    {
        /** @var WebsiteProvisioningService $service */
        $service = app(WebsiteProvisioningService::class);

        return $service->generateUniqueDatabaseName($website, '127.0.0.1');
    }
}
