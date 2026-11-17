<?php

namespace App\Observers;

use App\Models\Metadata;
use App\Services\PageSyncService;

class MetadataObserver
{
    public function saved(Metadata $meta): void
    {
        if (PageSyncService::isSyncing()) {
            return;
        }

        if ($meta->page) {
            app(PageSyncService::class)->syncPageToTenant($meta->page);
        }
    }

    public function deleted(Metadata $meta): void
    {
        if (PageSyncService::isSyncing()) {
            return;
        }

        if ($meta->page) {
            app(PageSyncService::class)->syncPageToTenant($meta->page);
        }
    }
}
