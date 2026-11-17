<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Page;
use App\Models\Template;
use App\Models\User;
use App\Models\Website;
use App\Services\VocabotPageLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VocabotCentralPagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test that all 7 Vocabot pages are imported into CodeBaseHub central database.
     */
    public function test_all_seven_vocabot_pages_are_imported_into_central_database(): void
    {
        $service = app(VocabotPageLibraryService::class);
        $result = $service->importToCentralDatabase();

        $this->assertEquals(7, $result['imported_pages']);
        $this->assertEquals(46, $result['total_sections']);

        $expectedSlugs = [
            'home',
            'product',
            'how-it-works',
            'solutions',
            'integrations',
            'contact',
            'policy',
        ];

        foreach ($expectedSlugs as $slug) {
            $this->assertDatabaseHas('pages', [
                'slug' => $slug,
                'is_template' => true,
            ]);
        }

        // Verify homepage exclusivity
        $homepage = Page::where('slug', 'home')->whereNotNull('website_id')->first();
        $this->assertNotNull($homepage);
        $this->assertTrue((bool) $homepage->is_homepage);
        $this->assertEquals('landing', $homepage->page_type);
        $this->assertCount(13, $homepage->sections);

        // Verify SEO fields
        $this->assertEquals('Vocabot | 24/7 AI Voice Receptionist for Inbound Calls & Bookings', $homepage->meta_title);
        $this->assertNotEmpty($homepage->meta_description);
        $this->assertNotNull($homepage->canonical_url);
        $this->assertNotNull($homepage->og_image);
    }

    /**
     * Test section content, ordering and structure preservation.
     */
    public function test_page_sections_preserve_order_type_and_structured_content(): void
    {
        $service = app(VocabotPageLibraryService::class);
        $service->importToCentralDatabase();

        $productPage = Page::where('slug', 'product')->whereNotNull('website_id')->first();
        $this->assertNotNull($productPage);
        $this->assertCount(9, $productPage->sections);

        // First section must be hero
        $heroSection = $productPage->sections->first();
        $this->assertEquals('hero', $heroSection->section_name);
        $this->assertEquals('hero', $heroSection->section_type);
        $this->assertEquals(1, $heroSection->sort_order);
        $this->assertIsArray($heroSection->content);
        $this->assertArrayHasKey('heading', $heroSection->content);

        // Sections must be sorted in ascending sort_order
        $sortOrders = $productPage->sections->pluck('sort_order')->toArray();
        $sortedCopy = $sortOrders;
        sort($sortedCopy);
        $this->assertEquals($sortedCopy, $sortOrders);
    }

    /**
     * Test that master reusable blueprint pages are available without website locking.
     */
    public function test_reusable_master_library_blueprints_exist_with_null_website_id(): void
    {
        $service = app(VocabotPageLibraryService::class);
        $service->importToCentralDatabase();

        $template = Template::where('slug', 'vocabot')->first();
        $this->assertNotNull($template);

        $blueprints = Page::whereNull('website_id')
            ->where('template_id', $template->id)
            ->where('is_template', true)
            ->get();

        $this->assertCount(7, $blueprints);

        foreach ($blueprints as $bp) {
            $this->assertGreaterThan(0, $bp->sections->count());
        }
    }

    /**
     * Test idempotence: importing multiple times does not produce duplicates.
     */
    public function test_import_is_idempotent_and_prevents_duplicate_records(): void
    {
        $service = app(VocabotPageLibraryService::class);

        $firstResult = $service->importToCentralDatabase();
        $secondResult = $service->importToCentralDatabase();

        $this->assertEquals($firstResult['imported_pages'], $secondResult['imported_pages']);
        $this->assertEquals($firstResult['total_sections'], $secondResult['total_sections']);

        $vocabotWebsite = Website::where('website_name', 'Vocabot')->first();
        $websitePagesCount = Page::where('website_id', $vocabotWebsite->id)->count();
        $this->assertEquals(7, $websitePagesCount);

        $blueprintPagesCount = Page::whereNull('website_id')->count();
        $this->assertEquals(7, $blueprintPagesCount);
    }

    /**
     * Test central preview retrieval route and API retrieval.
     */
    public function test_page_records_can_be_retrieved_via_central_preview_and_api(): void
    {
        $service = app(VocabotPageLibraryService::class);
        $service->importToCentralDatabase();

        $template = Template::where('slug', 'vocabot')->first();

        // 1. Authenticated user viewing preview
        $response = $this->actingAs($this->user)->get(route('pages.preview', 'how-it-works'));
        $response->assertStatus(200);
        $response->assertSee('How Vocabot Works');
        $response->assertSee('Content Sections');

        // 2. Index listing displays imported pages
        $indexResponse = $this->actingAs($this->user)->get(route('pages.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('/how-it-works');
        $indexResponse->assertSee('/solutions');

        // 3. API endpoint returns template pages
        $apiResponse = $this->getJson(route('api.templates.pages', $template));
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonPath('success', true);
        $this->assertCount(7, $apiResponse->json('pages'));
    }

    /**
     * Test website creation with selected template pages copies pages and sections.
     */
    public function test_website_creation_with_selected_pages_imports_template_pages(): void
    {
        $service = app(VocabotPageLibraryService::class);
        $service->importToCentralDatabase();

        $customer = Customer::factory()->create();
        $template = Template::where('slug', 'vocabot')->first();

        $postData = [
            'website_name' => 'Artisan Bistro AI',
            'domain' => 'artisanbistro.com',
            'customer_id' => $customer->id,
            'template_id' => $template->id,
            'provider' => 'cloud',
            'status' => 1,
            'selected_pages' => ['home', 'contact', 'product'],
        ];

        $response = $this->actingAs($this->user)->post(route('websites.store'), $postData);
        $response->assertRedirect(route('websites.index'));

        $newWebsite = Website::where('domain', 'artisanbistro.com')->first();
        $this->assertNotNull($newWebsite);

        // Verify that only the selected 3 pages were created under the new website
        $pages = Page::where('website_id', $newWebsite->id)->get();
        $this->assertCount(3, $pages);
        $this->assertTrue($pages->contains('slug', 'home'));
        $this->assertTrue($pages->contains('slug', 'contact'));
        $this->assertTrue($pages->contains('slug', 'product'));

        // Verify sections were copied
        $homePage = $pages->firstWhere('slug', 'home');
        $this->assertCount(13, $homePage->sections);
    }
}
