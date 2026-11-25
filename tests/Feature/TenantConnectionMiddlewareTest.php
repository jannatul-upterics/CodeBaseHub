<?php

namespace Tests\Feature;

use App\Http\Middleware\TenantConnection;
use App\Models\Customer;
use App\Models\Domain;
use App\Models\Page;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantConnectionMiddlewareTest extends TestCase
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

        $this->tenantDbPath = "{$testDir}/tenant_middleware_test.sqlite";
        if (File::exists($this->tenantDbPath)) {
            File::delete($this->tenantDbPath);
        }
        touch($this->tenantDbPath);

        // Setup tenant database schema using TenantConnection middleware helper
        TenantConnection::configureRaw('sqlite', '127.0.0.1', 3306, $this->tenantDbPath, 'root', '');

        Schema::connection('tenant')->create('pages', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('central_page_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('page_type')->default('standard');
            $table->boolean('is_homepage')->default(false);
            $table->tinyInteger('status')->default(1);
            $table->longText('content')->nullable();
            $table->timestamps();
        });

        // Insert distinct page directly in tenant database
        DB::connection('tenant')->table('pages')->insert([
            'title' => 'Tenant Isolated Page',
            'slug' => 'tenant-isolated',
            'page_type' => 'landing',
            'is_homepage' => 1,
            'status' => 1,
            'content' => 'Content existing exclusively in tenant database.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create central models
        $customer = Customer::factory()->create();
        $this->website = Website::create([
            'customer_id' => $customer->id,
            'website_name' => 'Acme Tenant Store',
            'domain' => 'acmetenant.test',
            'provider' => 'cloud',
            'status' => 1,
            'provisioning_status' => 'provisioned',
        ]);

        Domain::create([
            'tenant_id' => $this->website->id,
            'domain' => 'acmetenant.test',
            'is_primary' => true,
            'status' => 1,
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
     * Test TenantConnection middleware resolves website by domain and connects to tenant database.
     */
    public function test_tenant_connection_middleware_resolves_and_switches_connection(): void
    {
        $response = $this->get('http://acmetenant.test/site-page/tenant-isolated');

        $response->assertStatus(200);
        $response->assertSee('Tenant Isolated Page');

        // Central database connection should still be intact
        $this->assertDatabaseHas('websites', ['domain' => 'acmetenant.test']);
    }

    /**
     * Test TenantConnection returns 404 when domain is not found.
     */
    public function test_tenant_connection_middleware_returns_404_for_unknown_domain(): void
    {
        $response = $this->get('http://unknown-domain-xyz.com/site-page/any');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Domain not found']);
    }

    /**
     * Test TenantConnection returns 500 when tenant DB is not configured.
     */
    public function test_tenant_connection_middleware_returns_500_when_db_not_configured(): void
    {
        $unconfiguredWebsite = Website::factory()->create(['domain' => 'nodb.test']);
        Domain::create([
            'tenant_id' => $unconfiguredWebsite->id,
            'domain' => 'nodb.test',
            'is_primary' => true,
            'status' => 1,
        ]);

        $response = $this->get('http://nodb.test/site-page/home');

        $response->assertStatus(500);
        $response->assertJson(['error' => 'Tenant DB not configured']);
    }

    /**
     * Test TenantConnection::configure static method configures connection without leaking default.
     */
    public function test_tenant_connection_configure_reusable_method(): void
    {
        $configured = TenantConnection::configure($this->website, switchDefault: false);
        $this->assertTrue($configured);

        $this->assertTrue(Schema::connection('tenant')->hasTable('pages'));
        $this->assertTrue(DB::connection('tenant')->table('pages')->where('slug', 'tenant-isolated')->exists());

        // Default connection is preserved as central
        $this->assertNotEquals('tenant', Config::get('database.default'));
    }
}
