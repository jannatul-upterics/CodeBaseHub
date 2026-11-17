<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Website;
use App\Services\PageSyncService;
use Illuminate\Console\Command;

/**
 * Command that synchronizes Vocabot pages and sections between CodeBaseHub
 * and the connected website's tenant database.
 *
 * It preserves existing pages and sections, preventing duplicates and overwrites.
 */
class SyncVocabotPages extends Command
{
    protected $signature = 'vocabot:sync-pages {--website_id= : Sync only a specific website}';

    protected $description = 'Bidirectionally sync Vocabot pages, sections, and SEO metadata with the tenant database';

    /** Default page list – keep in sync with Vocabot routes */
    private const PAGES = [
        ['slug' => 'home',          'title' => 'Home',               'page_type' => 'static'],
        ['slug' => 'product',       'title' => 'Product',            'page_type' => 'static'],
        ['slug' => 'how-it-works',  'title' => 'How It Works',       'page_type' => 'static'],
        ['slug' => 'solutions',     'title' => 'Solutions',          'page_type' => 'static'],
        ['slug' => 'integrations',  'title' => 'Integrations',       'page_type' => 'static'],
        ['slug' => 'contact',       'title' => 'Contact',            'page_type' => 'static'],
        ['slug' => 'policy',        'title' => 'Privacy Policy',     'page_type' => 'static'],
    ];

    /** Very simple placeholder sections – you can enrich later */
    private const SECTION_TEMPLATES = [
        'hero' => ['section_type' => 'hero',       'content' => ['heading' => '', 'subheading' => '', 'image' => '']],
        'features' => ['section_type' => 'features', 'content' => ['items' => []]],
        'cta' => ['section_type' => 'cta',        'content' => ['text' => '', 'button_text' => '', 'button_url' => '']],
    ];

    public function handle()
    {
        // -------------------------------------------------------------
        // 1️⃣ Determine which websites we have to process.
        // -------------------------------------------------------------
        $websiteQuery = Website::query()
            ->whereHas('template', fn ($q) => $q->where('slug', 'vocabot'));

        if ($websiteId = $this->option('website_id')) {
            $websiteQuery->where('id', $websiteId);
        }

        $websites = $websiteQuery->get();

        if ($websites->isEmpty()) {
            $this->info('No Vocabot‑templated websites found.');

            return 0;
        }

        // -------------------------------------------------------------
        // 2️⃣ Loop through each website and create missing pages.
        // -------------------------------------------------------------
        foreach ($websites as $website) {
            $this->info("Syncing website: {$website->website_name} (ID {$website->id})");

            foreach (self::PAGES as $pageData) {
                /** @var Page $page */
                $page = $website->pages()
                    ->firstOrCreate(
                        ['slug' => $pageData['slug']],
                        [
                            'title' => $pageData['title'],
                            'page_type' => $pageData['page_type'],
                            'is_homepage' => $pageData['slug'] === 'home',
                            'status' => 1,
                        ]
                    );

                // -------------------------------------------------
                // 3️⃣ If the page was just created, add placeholder sections.
                // -------------------------------------------------
                if ($page->wasRecentlyCreated) {
                    $this->info("  • Created page '{$page->title}'");

                    $order = 1;
                    foreach (self::SECTION_TEMPLATES as $name => $tmpl) {
                        $section = new PageSection([
                            'section_name' => $name,
                            'section_type' => $tmpl['section_type'],
                            'content' => $tmpl['content'],
                            'sort_order' => $order++,
                            'status' => 1,
                        ]);
                        $page->sections()->save($section);
                    }
                }
            }

            // -------------------------------------------------
            // 4️⃣ Synchronize all pages, sections, and metadata to tenant database.
            // -------------------------------------------------
            $syncService = app(PageSyncService::class);
            $syncResult = $syncService->syncAllPagesForWebsite($website);

            $this->info("  → Synced to tenant: {$syncResult['synced_to_tenant']}, Synced from tenant: {$syncResult['synced_from_tenant']}");
            if (! empty($syncResult['errors'])) {
                foreach ($syncResult['errors'] as $err) {
                    $this->warn("  ⚠ {$err}");
                }
            }
        }

        $this->info('Vocabot page sync complete.');

        return 0;
    }
}
