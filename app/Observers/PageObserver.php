<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\PageSyncService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\TaggableStore;

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

        // Log the deletion event
        Log::info('Page soft‑deleted, syncing to tenant', [
            'website_id' => $page->website_id,
            'page_id' => $page->id,
            'action' => 'delete',
        ]);

        // Invalidate relevant caches
        $this->invalidatePageCache($page);

        app(PageSyncService::class)->deletePageFromTenant($page);
    }

    public function restored(Page $page): void
    {
        if (PageSyncService::isSyncing()) {
            return;
        }

        // Log the restoration event
        Log::info('Page restored, syncing to tenant', [
            'website_id' => $page->website_id,
            'page_id' => $page->id,
            'action' => 'restore',
        ]);

        // Invalidate relevant caches so the page re‑appears immediately
        $this->invalidatePageCache($page);

        app(PageSyncService::class)->restorePageOnTenant($page);
    }

    public function forceDeleted(Page $page): void
    {
        if (PageSyncService::isSyncing()) {
            return;
        }

        // Log the permanent deletion event
        Log::info('Page permanently deleted from tenant', [
            'website_id' => $page->website_id,
            'page_id' => $page->id,
            'action' => 'forceDelete',
        ]);

        // Invalidate caches as the page will be completely removed
        $this->invalidatePageCache($page);

        app(PageSyncService::class)->forceDeletePageFromTenant($page);
    }
    /**
     * Invalidate caches related to page navigation and sitemap.
     * Uses cache tags when the driver supports them; otherwise falls back
     * to explicit keys.
     */
    protected function invalidatePageCache(Page $page): void
    {
        $websiteId = $page->website_id;
        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(['pages', "website_{$websiteId}"])->flush();
        } else {
            Cache::forget("navigation_json_{$websiteId}");
            Cache::forget("sitemap_{$websiteId}");
        }
    }
}
