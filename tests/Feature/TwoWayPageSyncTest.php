<?php

namespace Tests\Feature;

use App\Http\Middleware\TenantConnection;
use App\Models\Metadata;
use App\Models\Page;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use App\Services\PageSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TwoWayPageSyncTest extends TestCase
{
    use RefreshDatabase;

    protected string $tenantDbPath;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $testDir = storage_path('framework/testing');
        if (! File::exists($testDir)) {
            File::makeDirectory($testDir, 0755, true);
        }

        $this->tenantDbPath = "{$testDir}/tenant_sync_test.sqlite";
        if (File::exists($this->tenantDbPath)) {
            File::delete($this->tenantDbPath);
        }
        touch($this->tenantDbPath);

        // Setup tenant database schema on this test sqlite file using TenantConnection middleware
        TenantConnection::configureRaw('sqlite', '127.0.0.1', 3306, $this->tenantDbPath, 'root', '');

        // Run migrations on tenant connection
        Schema::connection('tenant')->create('pages', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('central_page_id')->nullable()->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('page_type')->default('standard');
            $table->boolean('is_homepage')->default(false);
            $table->unsignedTinyInteger('status')->default(1);
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();
            $table->json('sections_data')->nullable();
            $table->string('sync_hash', 64)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('template_slug')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_indexable')->default(true);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('page_sections', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('page_id')->index();
            $table->unsignedBigInteger('central_section_id')->nullable()->index();
            $table->string('section_name');
            $table->string('section_type');
            $table->json('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('metadata', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('page_id')->nullable()->index();
            $table->unsignedBigInteger('central_metadata_id')->nullable()->index();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index, follow');
            $table->string('og_image')->nullable();
            $table->timestamps();
        });

        // Create website with database connection in central DB
        $this->website = Website::create([
            'website_name' => 'Vocabot Test',
            'domain' => 'vocabots.com',
            'provider' => 'cloud',
            'status' => 1,
            'provisioning_status' => 'provisioned',
        ]);

        WebsiteDatabaseConnection::create([
            'website_id' => $this->website->id,
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $this->tenantDbPath,
            'username' => 'root',
            'password' => '',
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('tenant');
        if (File::exists($this->tenantDbPath)) {
            File::delete($this->tenantDbPath);
        }

        parent::tearDown();
    }

    /**
     * Scenario 1: Create a page in CodeBaseHub → verify it appears on the website tenant DB.
     */
    public function test_scenario_1_create_page_in_codebasehub_reflects_in_tenant(): void
    {
        $page = Page::create([
            'website_id' => $this->website->id,
            'title' => 'Features Overview',
            'slug' => 'features',
            'page_type' => 'standard',
            'is_homepage' => false,
            'status' => 1,
            'content' => 'Comprehensive feature breakdown of Vocabot AI Voice Receptionist.',
            'meta_title' => 'Features | Vocabot AI',
            'meta_description' => 'Explore the full capabilities of Vocabot AI voice receptionist.',
        ]);

        $page->sections()->create([
            'section_name' => 'hero',
            'section_type' => 'hero',
            'content' => ['heading' => 'All AI Voice Features', 'subheading' => 'Automated call intelligence'],
            'sort_order' => 1,
            'status' => 1,
        ]);

        // Verify page exists in tenant DB
        $tenantPage = DB::connection('tenant')->table('pages')->where('slug', 'features')->first();
        $this->assertNotNull($tenantPage);
        $this->assertEquals('Features Overview', $tenantPage->title);
        $this->assertEquals($page->id, $tenantPage->central_page_id);
        $this->assertEquals('Comprehensive feature breakdown of Vocabot AI Voice Receptionist.', $tenantPage->content);
        $this->assertEquals('Features | Vocabot AI', $tenantPage->meta_title);

        // Verify sections synchronized in tenant DB
        $tenantSection = DB::connection('tenant')->table('page_sections')->where('page_id', $tenantPage->id)->first();
        $this->assertNotNull($tenantSection);
        $this->assertEquals('hero', $tenantSection->section_name);

        // Verify central page was mapped with tenant_page_id
        $this->assertEquals($tenantPage->id, $page->fresh()->tenant_page_id);
    }

    /**
     * Scenario 2: Edit a page in CodeBaseHub → verify changes reflect in tenant DB.
     */
    public function test_scenario_2_edit_page_in_codebasehub_updates_tenant(): void
    {
        $page = Page::create([
            'website_id' => $this->website->id,
            'title' => 'Original Pricing',
            'slug' => 'pricing',
            'page_type' => 'standard',
            'is_homepage' => false,
            'status' => 1,
            'content' => 'Initial pricing details.',
        ]);

        // Verify initial state
        $tenantPage = DB::connection('tenant')->table('pages')->where('slug', 'pricing')->first();
        $this->assertEquals('Original Pricing', $tenantPage->title);

        // Edit page title, content, and SEO metadata
        $page->update([
            'title' => 'Updated 2026 Enterprise Pricing',
            'content' => 'Affordable tiered pricing plans for restaurants and healthcare.',
            'meta_title' => 'Pricing Plans 2026 | Vocabot',
            'meta_description' => 'See all Vocabot enterprise pricing packages.',
        ]);

        Metadata::updateOrCreate(
            ['website_id' => $this->website->id, 'page_id' => $page->id],
            [
                'meta_title' => 'Pricing Plans 2026 | Vocabot',
                'meta_description' => 'See all Vocabot enterprise pricing packages.',
                'canonical_url' => 'https://vocabots.com/pricing',
            ]
        );

        // Verify tenant was updated
        $updatedTenantPage = DB::connection('tenant')->table('pages')->where('slug', 'pricing')->first();
        $this->assertEquals('Updated 2026 Enterprise Pricing', $updatedTenantPage->title);
        $this->assertEquals('Affordable tiered pricing plans for restaurants and healthcare.', $updatedTenantPage->content);
        $this->assertEquals('Pricing Plans 2026 | Vocabot', $updatedTenantPage->meta_title);
        $this->assertEquals('https://vocabots.com/pricing', $updatedTenantPage->canonical_url);
    }

    /**
     * Scenario 3: Delete a page in CodeBaseHub → verify it is removed from tenant DB.
     */
    public function test_scenario_3_delete_page_in_codebasehub_removes_from_tenant(): void
    {
        $page = Page::create([
            'website_id' => $this->website->id,
            'title' => 'Temporary Event Promo',
            'slug' => 'promo-2026',
            'page_type' => 'standard',
            'status' => 1,
        ]);

        $page->sections()->create([
            'section_name' => 'banner',
            'section_type' => 'content',
            'content' => ['heading' => 'Special promotion'],
            'sort_order' => 1,
            'status' => 1,
        ]);

        // Check created
        $this->assertDatabaseHas('pages', ['slug' => 'promo-2026']);
        $this->assertTrue(DB::connection('tenant')->table('pages')->where('slug', 'promo-2026')->exists());

        // Delete page
        $page->sections()->delete();
        $page->delete();

        // Check removed from central and tenant DB
        $this->assertDatabaseMissing('pages', ['slug' => 'promo-2026']);
        $this->assertFalse(DB::connection('tenant')->table('pages')->where('slug', 'promo-2026')->exists());
        $this->assertFalse(DB::connection('tenant')->table('page_sections')->where('section_name', 'banner')->exists());
    }

    /**
     * Scenario 4: Create a page directly in tenant (Vocabot) → verify it synchronizes to CodeBaseHub.
     */
    public function test_scenario_4_create_page_in_tenant_synchronizes_to_codebasehub(): void
    {
        $tenantId = DB::connection('tenant')->table('pages')->insertGetId([
            'title' => 'Direct Website Blog',
            'slug' => 'blog',
            'page_type' => 'standard',
            'is_homepage' => 0,
            'status' => 1,
            'content' => 'Blog articles posted directly from Vocabot website.',
            'meta_title' => 'Vocabot Blog',
            'meta_description' => 'Latest news and updates on AI voice automation.',
            'sections_data' => json_encode([
                ['section_name' => 'recent_posts', 'section_type' => 'features', 'content' => ['items' => ['Post 1', 'Post 2']]],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenantRow = DB::connection('tenant')->table('pages')->where('id', $tenantId)->first();

        // Synchronize from tenant
        $syncService = app(PageSyncService::class);
        $syncedPage = $syncService->syncFromTenant($this->website, $tenantRow, 'save');

        $this->assertNotNull($syncedPage);
        $this->assertEquals('Direct Website Blog', $syncedPage->title);
        $this->assertEquals('blog', $syncedPage->slug);
        $this->assertEquals($tenantId, $syncedPage->tenant_page_id);
        $this->assertEquals('Blog articles posted directly from Vocabot website.', $syncedPage->content);
        $this->assertEquals('Vocabot Blog', $syncedPage->meta_title);

        // Verify section created in CodeBaseHub
        $section = $syncedPage->sections()->where('section_name', 'recent_posts')->first();
        $this->assertNotNull($section);
        $this->assertEquals('features', $section->section_type);
    }

    /**
     * Scenario 5: Edit a page directly in tenant (Vocabot) → verify CodeBaseHub updates.
     */
    public function test_scenario_5_edit_page_in_tenant_updates_codebasehub(): void
    {
        // First create page via central
        $page = Page::create([
            'website_id' => $this->website->id,
            'title' => 'Support Center',
            'slug' => 'support',
            'page_type' => 'standard',
            'status' => 1,
            'content' => 'Old support info.',
        ]);

        $tenantPage = DB::connection('tenant')->table('pages')->where('slug', 'support')->first();

        // Edit directly in tenant
        DB::connection('tenant')->table('pages')->where('id', $tenantPage->id)->update([
            'title' => '24/7 Global Customer Support Hub',
            'content' => 'Reach our dedicated support team via voice agent or live chat.',
            'meta_title' => 'Vocabot Support Hub',
            'meta_description' => 'Fast 24/7 assistance for all Vocabot customers.',
            'updated_at' => now()->addMinutes(5),
        ]);

        $updatedTenantRow = DB::connection('tenant')->table('pages')->where('id', $tenantPage->id)->first();

        // Sync from tenant back to CodeBaseHub
        $syncService = app(PageSyncService::class);
        $syncedPage = $syncService->syncFromTenant($this->website, $updatedTenantRow, 'save');

        $this->assertEquals($page->id, $syncedPage->id);
        $this->assertEquals('24/7 Global Customer Support Hub', $syncedPage->title);
        $this->assertEquals('Reach our dedicated support team via voice agent or live chat.', $syncedPage->content);
        $this->assertEquals('Vocabot Support Hub', $syncedPage->meta_title);
    }

    /**
     * Scenario 6: Delete a page directly in tenant (Vocabot) → verify CodeBaseHub updates.
     */
    public function test_scenario_6_delete_page_in_tenant_removes_from_codebasehub(): void
    {
        $page = Page::create([
            'website_id' => $this->website->id,
            'title' => 'Obsolete Beta Info',
            'slug' => 'beta-info',
            'page_type' => 'standard',
            'status' => 1,
        ]);

        $tenantPage = DB::connection('tenant')->table('pages')->where('slug', 'beta-info')->first();
        $this->assertNotNull($tenantPage);

        // Delete from tenant
        DB::connection('tenant')->table('pages')->where('id', $tenantPage->id)->delete();

        // Sync deletion to CodeBaseHub
        $syncService = app(PageSyncService::class);
        $syncService->syncFromTenant($this->website, (array) $tenantPage, 'delete');

        // Verify page deleted from CodeBaseHub
        $this->assertDatabaseMissing('pages', ['id' => $page->id, 'slug' => 'beta-info']);
    }

    /**
     * Scenario 7: Verify duplicate prevention and synchronization error handling.
     */
    public function test_scenario_7_duplicate_prevention_and_error_handling(): void
    {
        $syncService = app(PageSyncService::class);

        // 1. Run full sync twice on the website: should be idempotent and produce 0 duplicates
        $firstSync = $syncService->syncAllPagesForWebsite($this->website);
        $secondSync = $syncService->syncAllPagesForWebsite($this->website);

        $this->assertEmpty($firstSync['errors']);
        $this->assertEmpty($secondSync['errors']);

        // 2. Unprovisioned website handling: should gracefully fail without uncaught exception
        $unprovisionedWebsite = Website::create([
            'website_name' => 'Unprovisioned Site',
            'domain' => 'unprov.local',
            'provider' => 'cloud',
            'status' => 1,
            'provisioning_status' => 'pending',
        ]);

        $unprovResult = $syncService->syncAllPagesForWebsite($unprovisionedWebsite);
        $this->assertNotEmpty($unprovResult['errors']);
        $this->assertStringContainsString('not provisioned', $unprovResult['errors'][0]);

        // 3. Loop prevention flag verification
        $this->assertFalse(PageSyncService::isSyncing());
        PageSyncService::withoutSyncing(function () {
            $this->assertTrue(PageSyncService::isSyncing());
        });
        $this->assertFalse(PageSyncService::isSyncing());
    }
}
