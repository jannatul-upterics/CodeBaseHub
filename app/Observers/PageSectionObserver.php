<?php

namespace App\Observers;

use App\Models\PageSection;
use App\Services\PageSyncService;

class PageSectionObserver
{
    public function saved(PageSection $section): void
    {
        if (PageSyncService::isSyncing()) {
            return;
        }

        if ($section->page) {
            $section->page->load('sections');
            app(PageSyncService::class)->syncPageToTenant($section->page);
        }
    }

    public function deleted(PageSection $section): void
    {
        if (PageSyncService::isSyncing()) {
            return;
        }

        if ($section->page) {
            $section->page->load('sections');
            app(PageSyncService::class)->syncPageToTenant($section->page);
        }
    }
}
