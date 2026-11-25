<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Website;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageApiTest extends TestCase
{
    use RefreshDatabase;

    private Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(WebsiteSeeder::class);

        $this->website = Website::factory()->create([
            'website_name' => 'Test Site',
            'domain' => 'testsite.example.com',
            'status' => 1,
        ]);

        Domain::factory()->create([
            'tenant_id' => $this->website->id,
            'domain' => 'testsite.example.com',
            'is_primary' => true,
            'status' => 1,
        ]);
    }

    // ---------------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------------

    private function apiHeaders(): array
    {
        return ['X-Website-Domain' => 'testsite.example.com'];
    }

    private function createPublishedPage(array $overrides = []): Page
    {
        return Page::factory()->create(array_merge([
            'website_id' => $this->website->id,
            'status' => 1,
            'is_homepage' => false,
        ], $overrides));
    }

    // ---------------------------------------------------------------------------
    // GET /api/v1/pages
    // ---------------------------------------------------------------------------

    public function test_index_returns_published_pages_for_known_domain(): void
    {
        $this->createPublishedPage(['title' => 'Home', 'slug' => 'home', 'is_homepage' => true]);
        $this->createPublishedPage(['title' => 'Product', 'slug' => 'product']);
        $this->createPublishedPage(['title' => 'Draft Page', 'slug' => 'draft', 'status' => 0]);

        $response = $this->getJson('/api/v1/pages', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_index_returns_404_when_domain_header_is_missing(): void
    {
        $this->getJson('/api/v1/pages')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_index_returns_404_for_unknown_domain(): void
    {
        $this->getJson('/api/v1/pages', ['X-Website-Domain' => 'unknown.example.com'])
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    // ---------------------------------------------------------------------------
    // GET /api/v1/pages/homepage
    // ---------------------------------------------------------------------------

    public function test_homepage_returns_the_published_homepage(): void
    {
        $homepage = $this->createPublishedPage([
            'title' => 'Home Page',
            'slug' => 'home',
            'is_homepage' => true,
            'meta_title' => 'Test Site | Welcome',
            'meta_description' => 'Welcome to our website.',
        ]);

        PageSection::factory()->create([
            'page_id' => $homepage->id,
            'section_name' => 'hero',
            'section_type' => 'hero',
            'content' => ['heading' => 'Welcome'],
            'sort_order' => 1,
            'status' => 1,
        ]);

        $response = $this->getJson('/api/v1/pages/homepage', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'home')
            ->assertJsonPath('data.is_homepage', true)
            ->assertJsonPath('data.metadata.meta_title', 'Test Site | Welcome')
            ->assertJsonCount(1, 'data.sections');
    }

    public function test_homepage_falls_back_to_slug_home_when_flag_unset(): void
    {
        $this->createPublishedPage(['slug' => 'home', 'is_homepage' => false]);

        $this->getJson('/api/v1/pages/homepage', $this->apiHeaders())
            ->assertOk()
            ->assertJsonPath('data.slug', 'home');
    }

    public function test_homepage_returns_404_when_no_published_homepage_exists(): void
    {
        $this->createPublishedPage(['slug' => 'product', 'is_homepage' => false]);

        $this->getJson('/api/v1/pages/homepage', $this->apiHeaders())
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_homepage_ignores_unpublished_homepage(): void
    {
        $this->createPublishedPage(['slug' => 'home', 'is_homepage' => true, 'status' => 0]);

        $this->getJson('/api/v1/pages/homepage', $this->apiHeaders())
            ->assertNotFound();
    }

    // ---------------------------------------------------------------------------
    // GET /api/v1/pages/{slug}
    // ---------------------------------------------------------------------------

    public function test_show_returns_published_page_by_slug(): void
    {
        $page = $this->createPublishedPage([
            'title' => 'Product',
            'slug' => 'product',
            'page_type' => 'landing',
        ]);

        PageSection::factory()->create([
            'page_id' => $page->id,
            'section_name' => 'features',
            'section_type' => 'features',
            'content' => ['heading' => 'Our Features'],
            'sort_order' => 1,
            'status' => 1,
        ]);

        $response = $this->getJson('/api/v1/pages/product', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'product')
            ->assertJsonPath('data.page_type', 'landing')
            ->assertJsonCount(1, 'data.sections')
            ->assertJsonPath('data.sections.0.section_name', 'features');
    }

    public function test_show_returns_404_for_nonexistent_slug(): void
    {
        $this->getJson('/api/v1/pages/nonexistent', $this->apiHeaders())
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_show_returns_404_for_unpublished_page(): void
    {
        $this->createPublishedPage(['slug' => 'secret', 'status' => 0]);

        $this->getJson('/api/v1/pages/secret', $this->apiHeaders())
            ->assertNotFound();
    }

    public function test_show_excludes_disabled_sections(): void
    {
        $page = $this->createPublishedPage(['slug' => 'solutions']);

        PageSection::factory()->create([
            'page_id' => $page->id,
            'section_name' => 'hero',
            'sort_order' => 1,
            'status' => 1,
        ]);

        PageSection::factory()->create([
            'page_id' => $page->id,
            'section_name' => 'hidden',
            'sort_order' => 2,
            'status' => 0,
        ]);

        $response = $this->getJson('/api/v1/pages/solutions', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data.sections');
    }

    public function test_show_accepts_domain_as_query_param(): void
    {
        $this->createPublishedPage(['slug' => 'integrations']);

        $this->getJson('/api/v1/pages/integrations?domain=testsite.example.com')
            ->assertOk()
            ->assertJsonPath('data.slug', 'integrations');
    }

    public function test_response_includes_metadata_node(): void
    {
        $this->createPublishedPage([
            'slug' => 'policy',
            'meta_title' => 'Privacy Policy',
            'meta_description' => 'Our privacy policy.',
            'canonical_url' => '/policy',
        ]);

        $response = $this->getJson('/api/v1/pages/policy', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'metadata' => ['meta_title', 'meta_description', 'canonical_url', 'og_image'],
                ],
            ])
            ->assertJsonPath('data.metadata.meta_title', 'Privacy Policy');
    }

    public function test_pages_from_different_websites_do_not_leak(): void
    {
        $otherWebsite = Website::factory()->create(['status' => 1]);
        Domain::factory()->create([
            'tenant_id' => $otherWebsite->id,
            'domain' => 'other.example.com',
            'is_primary' => true,
            'status' => 1,
        ]);

        // Create a page on this website and one on another website
        $this->createPublishedPage(['slug' => 'shared-slug']);
        Page::factory()->create([
            'website_id' => $otherWebsite->id,
            'slug' => 'other-secret',
            'status' => 1,
        ]);

        // This website should only see its own page
        $this->getJson('/api/v1/pages', $this->apiHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
