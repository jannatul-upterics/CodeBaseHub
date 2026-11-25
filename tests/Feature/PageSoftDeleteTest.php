<?php

namespace Tests\Feature;

use App\Http\Middleware\TenantConnection;
use App\Models\Domain;
use App\Models\Metadata;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PageSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Website $websiteA;

    private Website $websiteB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(WebsiteSeeder::class);

        $this->user = User::factory()->create();

        $this->websiteA = Website::factory()->create([
            'website_name' => 'Website Alpha',
            'domain' => 'alpha.test',
            'status' => 1,
        ]);

        Domain::factory()->create([
            'tenant_id' => $this->websiteA->id,
            'domain' => 'alpha.test',
            'is_primary' => true,
            'status' => 1,
        ]);

        $this->websiteB = Website::factory()->create([
            'website_name' => 'Website Beta',
            'domain' => 'beta.test',
            'status' => 1,
        ]);

        Domain::factory()->create([
            'tenant_id' => $this->websiteB->id,
            'domain' => 'beta.test',
            'is_primary' => true,
            'status' => 1,
        ]);
    }

    public function test_admin_deleting_page_performs_soft_delete(): void
    {
        $page = Page::factory()->create([
            'website_id' => $this->websiteA->id,
            'title' => 'Features Page',
            'slug' => 'features',
            'status' => 1,
        ]);

        $section = PageSection::create([
            'page_id' => $page->id,
            'section_name' => 'hero',
            'section_type' => 'hero',
            'content' => ['headline' => 'Welcome to Features'],
            'sort_order' => 1,
            'status' => 1,
        ]);

        $metadata = Metadata::create([
            'website_id' => $this->websiteA->id,
            'page_id' => $page->id,
            'meta_title' => 'Features SEO Title',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('websites.pages.destroy', [$this->websiteA, $page]));

        $response->assertRedirect(route('websites.pages.index', $this->websiteA));
        $response->assertSessionHas('success');

        // Page still exists in database with a deleted_at timestamp
        $this->assertSoftDeleted('pages', [
            'id' => $page->id,
            'slug' => 'features',
        ]);

        // Relationships (sections & metadata) are preserved, not permanently deleted
        $this->assertDatabaseHas('page_sections', [
            'id' => $section->id,
            'page_id' => $page->id,
        ]);

        $this->assertDatabaseHas('metadata', [
            'id' => $metadata->id,
            'page_id' => $page->id,
        ]);
    }

    public function test_soft_deleted_pages_do_not_appear_in_active_pages_list(): void
    {
        $activePage = Page::factory()->create([
            'website_id' => $this->websiteA->id,
            'title' => 'Active Page',
            'slug' => 'active-page',
            'status' => 1,
        ]);

        $trashedPage = Page::factory()->create([
            'website_id' => $this->websiteA->id,
            'title' => 'Trashed Page',
            'slug' => 'trashed-page',
            'status' => 1,
        ]);
        $trashedPage->delete();

        $response = $this->actingAs($this->user)
            ->get(route('websites.pages.index', $this->websiteA));

        $response->assertOk();
        $response->assertSee('Active Page');
        $response->assertDontSee('Trashed Page');
    }

    public function test_trash_section_displays_deleted_pages(): void
    {
        $trashedPage = Page::factory()->create([
            'website_id' => $this->websiteA->id,
            'title' => 'Trash Can Page',
            'slug' => 'trash-can',
            'status' => 1,
        ]);
        $trashedPage->delete();

        $response = $this->actingAs($this->user)
            ->get(route('websites.pages.trash', $this->websiteA));

        $response->assertOk();
        $response->assertSee('Trash Can Page');
        $response->assertSee('/trash-can');
        $response->assertSee('Restore');
        $response->assertSee('Delete Permanently');
    }

    public function test_restore_option_recovers_deleted_page(): void
    {
        $page = Page::factory()->create([
            'website_id' => $this->websiteA->id,
            'title' => 'Recoverable Page',
            'slug' => 'recoverable',
            'status' => 1,
        ]);

        $section = PageSection::create([
            'page_id' => $page->id,
            'section_name' => 'intro',
            'section_type' => 'text',
            'content' => ['body' => 'Hello World'],
            'sort_order' => 1,
            'status' => 1,
        ]);

        $page->delete();
        $this->assertTrue($page->fresh()->trashed());

        // Restore the page
        $response = $this->actingAs($this->user)
            ->post(route('websites.pages.restore', [$this->websiteA, $page->id]));

        $response->assertRedirect(route('websites.pages.trash', $this->websiteA));
        $response->assertSessionHas('success');

        // Verify page is active again
        $refreshed = $page->fresh();
        $this->assertFalse($refreshed->trashed());
        $this->assertNull($refreshed->deleted_at);

        // Verify sections relationship is intact
        $this->assertEquals(1, $refreshed->sections()->count());
        $this->assertEquals($section->id, $refreshed->sections()->first()->id);
    }

    public function test_restoration_prevents_duplicate_slug_conflict(): void
    {
        $page1 = Page::factory()->create([
            'website_id' => $this->websiteA->id,
            'title' => 'About Us Old',
            'slug' => 'about-us',
            'status' => 1,
        ]);
        $page1->delete();

        // Admin creates a new page with the same slug while old is in trash
        $page2 = Page::factory()->create([
            'website_id' => $this->websiteA->id,
            'title' => 'About Us New',
            'slug' => 'about-us',
            'status' => 1,
        ]);

        // Attempt to restore old page with duplicate slug
        $response = $this->actingAs($this->user)
            ->post(route('websites.pages.restore', [$this->websiteA, $page1->id]));

        $response->assertRedirect(route('websites.pages.trash', $this->websiteA));
        $response->assertSessionHas('error');

        // Page 1 remains trashed
        $this->assertTrue($page1->fresh()->trashed());
        // Page 2 remains active
        $this->assertFalse($page2->fresh()->trashed());
    }

    public function test_force_delete_option_permanently_removes_page_and_related_records(): void
    {
        $page = Page::factory()->create([
            'website_id' => $this->websiteA->id,
            'title' => 'Doomed Page',
            'slug' => 'doomed',
            'status' => 1,
        ]);

        $section = PageSection::create([
            'page_id' => $page->id,
            'section_name' => 'body',
            'section_type' => 'content',
            'content' => ['text' => 'Soon gone'],
            'sort_order' => 1,
            'status' => 1,
        ]);

        $page->delete();

        $response = $this->actingAs($this->user)
            ->delete(route('websites.pages.force_delete', [$this->websiteA, $page->id]));

        $response->assertRedirect(route('websites.pages.trash', $this->websiteA));
        $response->assertSessionHas('success');

        // Completely gone from database
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        $this->assertDatabaseMissing('page_sections', ['id' => $section->id]);
    }

    public function test_tenant_isolation_prevents_cross_website_manipulation(): void
    {
        $pageB = Page::factory()->create([
            'website_id' => $this->websiteB->id,
            'title' => 'Beta Secret',
            'slug' => 'secret',
            'status' => 1,
        ]);
        $pageB->delete();

        // Website A admin cannot restore page belonging to Website B
        $response = $this->actingAs($this->user)
            ->post(route('websites.pages.restore', [$this->websiteA, $pageB->id]));

        $response->assertNotFound();

        // Website A admin cannot force delete page belonging to Website B
        $response2 = $this->actingAs($this->user)
            ->delete(route('websites.pages.force_delete', [$this->websiteA, $pageB->id]));

        $response2->assertNotFound();

        // Website B page remains untouched in trash
        $this->assertTrue($pageB->fresh()->trashed());
    }

    public function test_api_returns_only_active_pages_and_returns_404_for_deleted_pages(): void
    {
        $activePage = Page::factory()->create([
            'website_id' => $this->websiteA->id,
            'title' => 'API Active',
            'slug' => 'api-active',
            'status' => 1,
        ]);

        $deletedPage = Page::factory()->create([
            'website_id' => $this->websiteA->id,
            'title' => 'API Deleted',
            'slug' => 'api-deleted',
            'status' => 1,
        ]);
        $deletedPage->delete();

        $headers = ['X-Website-Domain' => 'alpha.test'];

        // GET /api/v1/pages: only active pages returned
        $indexResponse = $this->getJson('/api/v1/pages', $headers);
        $indexResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'api-active');

        // GET /api/v1/pages/api-deleted: returns 404
        $deletedResponse = $this->getJson('/api/v1/pages/api-deleted', $headers);
        $deletedResponse->assertNotFound();

        // GET /api/v1/pages/api-active: returns 200
        $activeResponse = $this->getJson('/api/v1/pages/api-active', $headers);
        $activeResponse->assertOk()
            ->assertJsonPath('data.slug', 'api-active');

        // Restore deleted page: now accessible via API
        $deletedPage->restore();

        $restoredResponse = $this->getJson('/api/v1/pages/api-deleted', $headers);
        $restoredResponse->assertOk()
            ->assertJsonPath('data.slug', 'api-deleted');

        $indexResponse2 = $this->getJson('/api/v1/pages', $headers);
        $indexResponse2->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_tenant_database_sync_handles_soft_delete_and_restore(): void
    {
        $testDir = storage_path('framework/testing');
        if (! File::exists($testDir)) {
            File::makeDirectory($testDir, 0755, true);
        }

        $tenantDbPath = "{$testDir}/tenant_softdelete_test.sqlite";
        if (File::exists($tenantDbPath)) {
            File::delete($tenantDbPath);
        }
        touch($tenantDbPath);

        // Configure tenant connection
        TenantConnection::configureRaw('sqlite', '127.0.0.1', 3306, $tenantDbPath, 'root', '');

        Schema::connection('tenant')->create('pages', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('central_page_id')->nullable()->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('page_type')->default('standard');
            $table->boolean('is_homepage')->default(false);
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_indexable')->default(true);
            $table->longText('content')->nullable();
            $table->string('sync_hash', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('page_sections', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('page_id')->index();
            $table->string('section_name');
            $table->string('section_type');
            $table->json('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        $this->websiteA->update(['provisioning_status' => 'provisioned']);

        WebsiteDatabaseConnection::create([
            'website_id' => $this->websiteA->id,
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $tenantDbPath,
            'username' => 'root',
            'password' => '',
        ]);

        // Create page and section
        $page = Page::create([
            'website_id' => $this->websiteA->id,
            'title' => 'Sync Test Page',
            'slug' => 'sync-test',
            'page_type' => 'standard',
            'status' => 1,
            'content' => 'Initial content',
        ]);

        $section = PageSection::create([
            'page_id' => $page->id,
            'section_name' => 'hero',
            'section_type' => 'hero',
            'content' => ['headline' => 'Sync headline'],
            'sort_order' => 1,
            'status' => 1,
        ]);

        // Verify page exists in tenant DB and deleted_at is null
        $tenantRecord = \DB::connection('tenant')->table('pages')->where('slug', 'sync-test')->first();
        $this->assertNotNull($tenantRecord);
        $this->assertNull($tenantRecord->deleted_at);

        // Soft delete the page
        $this->actingAs($this->user)
            ->delete(route('websites.pages.destroy', [$this->websiteA, $page]));

        // Verify tenant DB page is now soft-deleted (deleted_at is set) and sections are preserved
        $tenantRecordAfterDelete = \DB::connection('tenant')->table('pages')->where('slug', 'sync-test')->first();
        $this->assertNotNull($tenantRecordAfterDelete);
        $this->assertNotNull($tenantRecordAfterDelete->deleted_at);

        // Section still exists in tenant DB
        $tenantSection = \DB::connection('tenant')->table('page_sections')->where('page_id', $tenantRecord->id)->first();
        $this->assertNotNull($tenantSection);

        // Restore the page
        $this->actingAs($this->user)
            ->post(route('websites.pages.restore', [$this->websiteA, $page->id]));

        // Verify tenant DB page is restored (deleted_at is null)
        $tenantRecordAfterRestore = \DB::connection('tenant')->table('pages')->where('slug', 'sync-test')->first();
        $this->assertNotNull($tenantRecordAfterRestore);
        $this->assertNull($tenantRecordAfterRestore->deleted_at);
    }
}
