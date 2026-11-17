<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Website;
use App\Services\VocabotPageLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VocabotImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\WebsiteSeeder::class);
    }

    public function test_master_pages_are_created_once_and_are_reusable()
    {
        $svc = $this->app->make(VocabotPageLibraryService::class);
        $svc->importToCentralDatabase(); // first run
        $this->assertEquals(7, Page::whereNull('website_id')->count());

        $svc->importToCentralDatabase(); // second run – no duplicates
        $this->assertEquals(7, Page::whereNull('website_id')->count());
        $this->assertEquals(46, \App\Models\PageSection::whereIn('page_id',
                Page::whereNull('website_id')->pluck('id'))->count());
    }

    public function test_two_websites_can_independently_reuse_master_pages()
    {
        $siteA = Website::factory()->create(['website_name' => 'SiteA']);
        $siteB = Website::factory()->create(['website_name' => 'SiteB']);

        $svc = $this->app->make(VocabotPageLibraryService::class);
        $svc->importToCentralDatabase($siteA);
        $svc->importToCentralDatabase($siteB);

        $this->assertEquals(7, Page::where('website_id', $siteA->id)->count());
        $this->assertEquals(7, Page::where('website_id', $siteB->id)->count());

        // verify linkage
        $derived = Page::where('website_id', $siteA->id)->first();
        $this->assertNotNull($derived->master_page_id);
        $this->assertEquals(
            Page::whereNull('website_id')->where('slug', $derived->slug)->first()->id,
            $derived->master_page_id
        );

        // re‑run for SiteA – should not create duplicates
        $svc->importToCentralDatabase($siteA);
        $this->assertEquals(7, Page::where('website_id', $siteA->id)->count());
    }

    public function test_existing_custom_pages_for_website_one_remain_untouched()
    {
        $site = Website::factory()->create(['website_name' => 'CustomSite']);

        // custom page that is not a derived master (master_page_id stays null)
        $custom = Page::create([
            'website_id' => $site->id,
            'title'      => 'My Custom Page',
            'slug'       => 'my-custom',
            'page_type'  => 'standard',
            'status'     => 1,
        ]);

        $svc = $this->app->make(VocabotPageLibraryService::class);
        $svc->importToCentralDatabase($site);

        $this->assertDatabaseHas('pages', [
            'id'            => $custom->id,
            'title'         => 'My Custom Page',
            'master_page_id'=> null,
        ]);
    }

    public function test_derived_page_customisation_is_preserved_across_imports()
    {
        $site = Website::factory()->create(['website_name' => 'SiteC']);

        $svc = $this->app->make(VocabotPageLibraryService::class);
        $svc->importToCentralDatabase($site);

        // pick one derived page and change its title/content
        $derived = Page::where('website_id', $site->id)->first();
        $derived->update(['title' => 'CUSTOM TITLE', 'content' => 'CUSTOM CONTENT']);

        // re‑run import – title & content must stay the same
        $svc->importToCentralDatabase($site);
        $derived->refresh();

        $this->assertEquals('CUSTOM TITLE', $derived->title);
        $this->assertEquals('CUSTOM CONTENT', $derived->content);
    }

    public function test_derived_section_customisation_is_preserved_across_imports()
    {
        $site = Website::factory()->create(['website_name' => 'SiteD']);

        $svc = $this->app->make(VocabotPageLibraryService::class);
        $svc->importToCentralDatabase($site);

        // pick a derived page, then a section inside it and modify its JSON content
        $derived = Page::where('website_id', $site->id)->first();
        $section = PageSection::where('page_id', $derived->id)->first();
        $section->update(['content' => json_encode(['custom' => 'new value'])]);

        // re‑run import – the section's content must stay what we set
        $svc->importToCentralDatabase($site);
        $section->refresh();

        $this->assertEquals(['custom' => 'new value'], $section->content);
    }

    public function test_website_one_original_pages_and_sections_stay_untouched()
    {
        // website_id = 1 already exists with 6 pages / 33 sections in the seed data
        $site = Website::find(1);
        $this->assertNotNull($site, 'Website with ID 1 must exist in the fixture data');

        // 1️⃣ Snapshot original pages (ids + relevant attributes)
        $originalPages = Page::where('website_id', 1)
            ->orderBy('id')
            ->get()
            ->map(function ($p) {
                return [
                    'id'               => $p->id,
                    'title'            => $p->title,
                    'slug'             => $p->slug,
                    'content'          => $p->content,
                    'meta_title'       => $p->meta_title,
                    'meta_description' => $p->meta_description,
                    'canonical_url'    => $p->canonical_url,
                    'og_image'         => $p->og_image,
                ];
            })
            ->keyBy('id')
            ->toArray();

        // 2️⃣ Snapshot original sections (ids + page_id + name + content)
        $originalSections = PageSection::whereIn('page_id', array_keys($originalPages))
            ->orderBy('id')
            ->get()
            ->map(function ($s) {
                return [
                    'id'           => $s->id,
                    'page_id'      => $s->page_id,
                    'section_name' => $s->section_name,
                    'content'      => $s->content,
                ];
            })
            ->keyBy('id')
            ->toArray();

        // 3️⃣ Run the import for website 1 (adds derived copies)
        $svc = $this->app->make(VocabotPageLibraryService::class);
        $svc->importToCentralDatabase($site);

        // 4️⃣ Verify every original page still has exactly the same values
        foreach (Page::whereIn('id', array_keys($originalPages))->orderBy('id')->get() as $p) {
            $snapshot = $originalPages[$p->id];
            $this->assertEquals($snapshot['title'], $p->title, "Page ID {$p->id} title changed");
            $this->assertEquals($snapshot['slug'], $p->slug, "Page ID {$p->id} slug changed");
            $this->assertEquals($snapshot['content'], $p->content, "Page ID {$p->id} content changed");
            $this->assertEquals($snapshot['meta_title'], $p->meta_title, "Page ID {$p->id} meta_title changed");
            $this->assertEquals($snapshot['meta_description'], $p->meta_description, "Page ID {$p->id} meta_description changed");
            $this->assertEquals($snapshot['canonical_url'], $p->canonical_url, "Page ID {$p->id} canonical_url changed");
            $this->assertEquals($snapshot['og_image'], $p->og_image, "Page ID {$p->id} og_image changed");
        }

        // 5️⃣ Verify every original section still has exactly the same values
        foreach (PageSection::whereIn('page_id', array_keys($originalPages))->orderBy('id')->get() as $s) {
            $snapshot = $originalSections[$s->id];
            $this->assertEquals($snapshot['section_name'], $s->section_name, "Section ID {$s->id} name changed");
            $this->assertEquals($snapshot['content'], $s->content, "Section ID {$s->id} content changed");
        }

        // 6️⃣ Verify derived pages and sections counts added
        $totalPages    = Page::where('website_id', 1)->count();
        $totalSections = PageSection::whereIn('page_id', Page::where('website_id', 1)->pluck('id'))->count();
        $this->assertEquals(count($originalPages) + 7, $totalPages, 'Website 1 should have original + 7 derived pages');
        $this->assertEquals(count($originalSections) + 46, $totalSections, 'Website 1 should have original + 46 derived sections');
    }
}
