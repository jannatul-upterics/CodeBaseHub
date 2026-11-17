<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\PageSyncService;

class PageObserver
{
    public function created(Page $page): void
    {
        if (PageSyncService::isSyncing()) {
            return;
        }

        app(PageSyncService::class)->syncPageToTenant($page);
    }

    public function updated(Page $page): void
    {
        if (PageSyncService::isSyncing()) {
            return;
        }

        app(PageSyncService::class)->syncPageToTenant($page);
    }

    public function deleted(Page $page): void
    {
        if (PageSyncService::isSyncing()) {
            return;
        }

        app(PageSyncService::class)->deletePageFromTenant($page);
    }
}
