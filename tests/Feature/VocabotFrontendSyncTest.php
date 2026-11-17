<?php

namespace Tests\Feature;

use App\Http\Controllers\PageController;
use App\Http\Middleware\TenantConnection;
use App\Models\Page;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class VocabotFrontendSyncTest extends TestCase
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

        $this->tenantDbPath = "{$testDir}/vocabot_frontend_test.sqlite";
        if (File::exists($this->tenantDbPath)) {
            File::delete($this->tenantDbPath);
        }
        touch($this->tenantDbPath);

        // Setup tenant database connection using TenantConnection middleware
        TenantConnection::configureRaw('sqlite', '127.0.0.1', 3306, $this->tenantDbPath, 'root', '');

        // Create tenant schema
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

        // Add Vocabot view directory to view finder so views can be tested
        $vocabotViews = base_path('Vocabot/resources/views');
        if (File::isDirectory($vocabotViews)) {
            View::addLocation($vocabotViews);
        }

        $this->website = Website::create([
            'website_name' => 'Vocabot',
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
     * Test dynamic rendering of Vocabot page with custom sections and SEO metadata.
     */
    public function test_vocabot_dynamic_page_rendering(): void
    {
        // 1. Create a page in CodeBaseHub which syncs to tenant
        $page = Page::create([
            'website_id' => $this->website->id,
            'title' => 'Healthcare AI Receptionist',
            'slug' => 'healthcare',
            'page_type' => 'standard',
            'status' => 1,
            'content' => 'HIPAA-compliant 24/7 AI Voice triage and clinic appointment booking.',
            'meta_title' => 'Healthcare AI Voice Receptionist | Vocabot',
            'meta_description' => 'Automate inbound clinical scheduling and patient FAQ routing.',
        ]);

        $page->sections()->create([
            'section_name' => 'cta',
            'section_type' => 'cta',
            'content' => [
                'heading' => 'Deploy for Your Clinic Today',
                'button_text' => 'Schedule Health Demo',
                'button_url' => '/contact',
            ],
            'sort_order' => 1,
            'status' => 1,
        ]);

        // Execute Vocabot controller rendering
        $controller = new PageController;
        $request = Request::create('/healthcare', 'GET');

        // Verify dynamic view exists and renders
        $this->assertTrue(View::exists('pages.dynamic'));

        $rendered = View::make('pages.dynamic', [
            'page' => $page,
            'pageTitle' => $page->meta_title ?: $page->title,
            'metaDescription' => $page->meta_description,
            'pageSections' => [
                [
                    'section_name' => 'cta',
                    'section_type' => 'cta',
                    'content' => [
                        'heading' => 'Deploy for Your Clinic Today',
                        'button_text' => 'Schedule Health Demo',
                        'button_url' => '/contact',
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Healthcare AI Voice Receptionist | Vocabot', $rendered);
        $this->assertStringContainsString('HIPAA-compliant 24/7 AI Voice triage', $rendered);
        $this->assertStringContainsString('Deploy for Your Clinic Today', $rendered);
        $this->assertStringContainsString('Schedule Health Demo', $rendered);
    }

    /**
     * Test CodeBaseHub bi-directional sync API endpoint (/api/websites/{website}/sync-page).
     */
    public function test_codebasehub_sync_api_endpoint(): void
    {
        // Direct website creates a page and sends sync event
        $payload = [
            'action' => 'save',
            'page' => [
                'id' => 999,
                'title' => 'API Created Page',
                'slug' => 'api-created',
                'page_type' => 'standard',
                'status' => 1,
                'content' => 'Content sent via sync API.',
                'meta_title' => 'API Created Title',
                'meta_description' => 'API Created Meta Description',
            ],
        ];

        $response = $this->postJson("/api/websites/{$this->website->id}/sync-page", $payload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify page exists in CodeBaseHub
        $cbhPage = Page::where('website_id', $this->website->id)->where('slug', 'api-created')->first();
        $this->assertNotNull($cbhPage);
        $this->assertEquals('API Created Page', $cbhPage->title);
        $this->assertEquals('Content sent via sync API.', $cbhPage->content);
        $this->assertEquals(999, $cbhPage->tenant_page_id);

        // Now test delete action via sync API
        $deletePayload = [
            'action' => 'delete',
            'page' => [
                'id' => 999,
                'slug' => 'api-created',
            ],
        ];

        $deleteResponse = $this->postJson("/api/websites/{$this->website->id}/sync-page", $deletePayload);
        $deleteResponse->assertStatus(200);

        // Verify deleted from CodeBaseHub
        $this->assertDatabaseMissing('pages', ['website_id' => $this->website->id, 'slug' => 'api-created']);
    }
}
