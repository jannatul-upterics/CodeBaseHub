<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\TenantDatabaseConnectionException;
use App\Models\Customer;
use App\Models\Domain;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use App\Tenancy\Bootstrappers\WebsiteDatabaseTenancyBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PDO;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Tests\TestCase;

class StanclTenancyDatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected string $testDir;

    /** @var list<string> */
    protected array $createdDbFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = storage_path('framework/testing/stancl_tenancy_dbs');
        if (! File::exists($this->testDir)) {
            File::makeDirectory($this->testDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        DB::purge('tenant');

        foreach ($this->createdDbFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        if (File::exists($this->testDir)) {
            File::deleteDirectory($this->testDir);
        }

        parent::tearDown();
    }

    /**
     * Create an isolated SQLite tenant database file with a pages table and seed row.
     */
    protected function createTenantSqliteDb(string $filename, string $pageTitle): string
    {
        $path = "{$this->testDir}/{$filename}.sqlite";
        touch($path);
        $this->createdDbFiles[] = $path;

        $pdo = new PDO("sqlite:{$path}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec('CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT NOT NULL)');
        $stmt = $pdo->prepare('INSERT INTO pages (title, slug) VALUES (?, ?)');
        $stmt->execute([$pageTitle, 'home']);

        return $path;
    }

    /**
     * Helper to create a Website with a Domain and WebsiteDatabaseConnection.
     */
    protected function createWebsiteTenant(string $domain, string $dbPath): Website
    {
        $customer = Customer::factory()->create();

        $website = Website::create([
            'customer_id' => $customer->id,
            'website_name' => "Site {$domain}",
            'domain' => $domain,
            'provider' => 'cloud',
            'status' => 1,
            'provisioning_status' => 'provisioned',
        ]);

        Domain::create([
            'tenant_id' => $website->id,
            'domain' => $domain,
            'is_primary' => true,
            'status' => 1,
        ]);

        WebsiteDatabaseConnection::create([
            'website_id' => $website->id,
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $dbPath,
            'username' => 'root',
            'password' => 'secret_pw',
        ]);

        return $website;
    }

    public function test_tenancy_initialize_and_end_switches_and_restores_database_connection(): void
    {
        $tenantDbPath = $this->createTenantSqliteDb('tenant_one', 'Tenant One Home');
        $website = $this->createWebsiteTenant('tenant-one.test', $tenantDbPath);

        $centralDefault = Config::get('database.default');
        $this->assertNotEquals('tenant', $centralDefault);

        // Initialize tenancy via Stancl/Tenancy
        tenancy()->initialize($website);

        $this->assertTrue(tenancy()->initialized);
        $this->assertSame('tenant', Config::get('database.default'));
        $this->assertSame('tenant', DB::getDefaultConnection());
        $this->assertSame($tenantDbPath, Config::get('database.connections.tenant.database'));

        // Tenant-specific query hits the tenant database
        $tenantPage = DB::table('pages')->where('slug', 'home')->first();
        $this->assertNotNull($tenantPage);
        $this->assertSame('Tenant One Home', $tenantPage->title);

        // End tenancy and verify central connection is restored
        tenancy()->end();

        $this->assertFalse(tenancy()->initialized);
        $this->assertSame($centralDefault, Config::get('database.default'));
        $this->assertSame($centralDefault, DB::getDefaultConnection());
        $this->assertNull(Config::get('database.connections.tenant'));

        // Central tables are accessible again
        $this->assertTrue(Schema::hasTable('websites'));
        $this->assertSame(1, Website::where('id', $website->id)->count());
    }

    public function test_website_run_scopes_queries_to_tenant_and_reverts_to_central(): void
    {
        $tenantDbPath = $this->createTenantSqliteDb('tenant_run', 'Tenant Run Page');
        $website = $this->createWebsiteTenant('tenant-run.test', $tenantDbPath);

        $centralDefault = Config::get('database.default');

        $title = $website->run(function (Website $tenant): string {
            $this->assertSame('tenant', DB::getDefaultConnection());

            return (string) DB::table('pages')->where('slug', 'home')->value('title');
        });

        $this->assertSame('Tenant Run Page', $title);
        $this->assertFalse(tenancy()->initialized);
        $this->assertSame($centralDefault, Config::get('database.default'));
        $this->assertSame($centralDefault, DB::getDefaultConnection());
    }

    public function test_switching_between_multiple_website_tenants_isolates_queries(): void
    {
        $dbOne = $this->createTenantSqliteDb('tenant_a', 'Alpha Site');
        $dbTwo = $this->createTenantSqliteDb('tenant_b', 'Beta Site');

        $websiteA = $this->createWebsiteTenant('alpha.test', $dbOne);
        $websiteB = $this->createWebsiteTenant('beta.test', $dbTwo);

        tenancy()->initialize($websiteA);
        $this->assertSame('Alpha Site', DB::table('pages')->value('title'));

        tenancy()->initialize($websiteB);
        $this->assertSame('Beta Site', DB::table('pages')->value('title'));

        tenancy()->end();
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_bootstrapper_supports_driver_host_port_database_username_and_password(): void
    {
        $customer = Customer::factory()->create();
        $website = Website::create([
            'customer_id' => $customer->id,
            'website_name' => 'MySQL Config Site',
            'domain' => 'mysql-config.test',
            'provider' => 'cloud',
            'status' => 1,
        ]);

        $conn = WebsiteDatabaseConnection::create([
            'website_id' => $website->id,
            'driver' => 'pgsql',
            'host' => '10.0.0.25',
            'port' => 5433,
            'database' => 'cbh_pgsql_tenant',
            'username' => 'tenant_user',
            'password' => 'super_secret_pw',
        ]);

        $bootstrapper = new class extends WebsiteDatabaseTenancyBootstrapper
        {
            /** @return array<string, mixed> */
            public function exposeConfig(WebsiteDatabaseConnection $connection): array
            {
                return $this->buildConnectionConfig($connection);
            }
        };

        $built = $bootstrapper->exposeConfig($conn->fresh());

        $this->assertSame('pgsql', $built['driver']);
        $this->assertSame('10.0.0.25', $built['host']);
        $this->assertSame(5433, $built['port']);
        $this->assertSame('cbh_pgsql_tenant', $built['database']);
        $this->assertSame('tenant_user', $built['username']);
        $this->assertSame('super_secret_pw', $built['password']);
    }

    public function test_missing_database_connection_record_fails_gracefully_and_preserves_central_db(): void
    {
        $customer = Customer::factory()->create();
        $website = Website::create([
            'customer_id' => $customer->id,
            'website_name' => 'Unconfigured Site',
            'domain' => 'unconfigured.test',
            'provider' => 'cloud',
            'status' => 1,
        ]);

        $centralDefault = Config::get('database.default');

        try {
            tenancy()->initialize($website);
            $this->fail('Expected TenantDatabaseConnectionException was not thrown.');
        } catch (TenantDatabaseConnectionException $e) {
            $this->assertStringContainsString('not configured', $e->getMessage());
        }

        $this->assertFalse(tenancy()->initialized);
        $this->assertSame($centralDefault, Config::get('database.default'));
        $this->assertSame($centralDefault, DB::getDefaultConnection());
        $this->assertNull(Config::get('database.connections.tenant'));
    }

    public function test_unreachable_database_fails_gracefully_and_renders_503_json(): void
    {
        $customer = Customer::factory()->create();
        $website = Website::create([
            'customer_id' => $customer->id,
            'website_name' => 'Broken DB Site',
            'domain' => 'broken-db.test',
            'provider' => 'cloud',
            'status' => 1,
        ]);

        Domain::create([
            'tenant_id' => $website->id,
            'domain' => 'broken-db.test',
            'is_primary' => true,
            'status' => 1,
        ]);

        WebsiteDatabaseConnection::create([
            'website_id' => $website->id,
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $this->testDir.'/nonexistent_dir/missing.sqlite',
            'username' => 'root',
            'password' => 'leaked_secret_check',
        ]);

        Route::middleware([InitializeTenancyByDomain::class])
            ->get('/_test-tenant-broken', fn () => response()->json(['ok' => true]));

        $response = $this->getJson('http://broken-db.test/_test-tenant-broken');

        $response->assertStatus(503)
            ->assertJson(['success' => false]);

        $this->assertStringNotContainsString('leaked_secret_check', (string) $response->json('message'));
        $this->assertFalse(tenancy()->initialized);
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_initialize_tenancy_by_domain_middleware_resolves_website_and_connects(): void
    {
        $tenantDbPath = $this->createTenantSqliteDb('tenant_middleware', 'Middleware Tenant Page');
        $this->createWebsiteTenant('middleware-tenant.test', $tenantDbPath);

        Route::middleware([InitializeTenancyByDomain::class])
            ->get('/_test-tenant-domain', function () {
                return response()->json([
                    'tenant_id' => tenant('id'),
                    'page_title' => DB::table('pages')->where('slug', 'home')->value('title'),
                ]);
            });

        $response = $this->getJson('http://middleware-tenant.test/_test-tenant-domain');

        $response->assertOk()
            ->assertJson([
                'page_title' => 'Middleware Tenant Page',
            ]);
    }
}
