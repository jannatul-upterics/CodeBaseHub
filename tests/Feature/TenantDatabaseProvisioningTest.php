<?php

namespace Tests\Feature;

use App\Exceptions\WebsiteProvisioningException;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use App\Services\WebsiteProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PDO;
use PDOException;
use Tests\TestCase;

class TenantDatabaseProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected string $testDbDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDbDir = storage_path('framework/testing/tenant_dbs');
        if (! File::exists($this->testDbDir)) {
            File::makeDirectory($this->testDbDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testDbDir)) {
            File::deleteDirectory($this->testDbDir);
        }

        parent::tearDown();
    }

    /**
     * Helper to configure service to use isolated sqlite files for testing.
     */
    protected function configureServiceForSqlite(WebsiteProvisioningService $service): void
    {
        $testDir = $this->testDbDir;

        $service->setPdoFactory(function ($driver, $host, $port, $database, $username, $password) use ($testDir) {
            if ($database === null || $database === '') {
                $dbFile = ':memory:';
            } elseif ($database === ':memory:' || str_contains($database, '/') || str_contains($database, '\\')) {
                $dbFile = $database;
            } else {
                $dbFile = "{$testDir}/{$database}.sqlite";
            }

            return new PDO("sqlite:{$dbFile}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        });
    }

    /**
     * 1. Test creating a website with a newly generated tenant database.
     */
    public function test_1_create_website_with_newly_generated_tenant_database(): void
    {
        $user = User::factory()->create();
        $service = app(WebsiteProvisioningService::class);
        $this->configureServiceForSqlite($service);

        $payload = [
            'website_name' => 'Auto Provisioned Site',
            'domain' => 'autosite.io',
            'provider' => 'cloud',
            'tenant_db_mode' => 'create',
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_name' => '', // Empty: trigger automatic generation
            'db_username' => 'root',
            'db_password' => 'secret',
        ];

        // Override default driver to sqlite in dynamic connections
        config(['database.default' => 'sqlite']);

        $response = $this->actingAs($user)->post(route('websites.store'), $payload);

        $response->assertRedirect(route('websites.index'));
        $response->assertSessionHas('success');

        $website = Website::where('domain', 'autosite.io')->firstOrFail();

        $this->assertEquals('provisioned', $website->provisioning_status);
        $this->assertTrue($website->isProvisioned());
        $this->assertNotNull($website->databaseConnection);
        $this->assertNotEmpty($website->databaseConnection->database);
        $this->assertStringContainsString('cbh_', $website->databaseConnection->database);

        // Verify tenant migrations ran and tables exist
        $service->configureTenantConnection(
            $website->databaseConnection->driver,
            $website->databaseConnection->host,
            $website->databaseConnection->port,
            $this->testDbDir.'/'.$website->databaseConnection->database.'.sqlite',
            $website->databaseConnection->username,
            $website->databaseConnection->password
        );

        $this->assertTrue(Schema::connection('tenant')->hasTable('pages'));
        $this->assertTrue(Schema::connection('tenant')->hasTable('media'));
    }

    /**
     * 2. Test creating a website with a manually specified database name.
     */
    public function test_2_create_website_with_manually_specified_database_name(): void
    {
        $user = User::factory()->create();
        $service = app(WebsiteProvisioningService::class);
        $this->configureServiceForSqlite($service);
        config(['database.default' => 'sqlite']);

        $customDbName = 'custom_tenant_alpha';

        $payload = [
            'website_name' => 'Custom DB Site',
            'domain' => 'customsite.io',
            'provider' => 'aws',
            'tenant_db_mode' => 'create',
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_name' => $customDbName,
            'db_username' => 'root',
            'db_password' => 'secret',
        ];

        $response = $this->actingAs($user)->post(route('websites.store'), $payload);

        $response->assertRedirect(route('websites.index'));
        $website = Website::where('domain', 'customsite.io')->firstOrFail();

        $this->assertEquals('provisioned', $website->provisioning_status);
        $this->assertDatabaseHas('website_database_connections', [
            'website_id' => $website->id,
            'database' => $customDbName,
        ]);
    }

    /**
     * 3. Test connecting an existing tenant database.
     */
    public function test_3_connect_existing_tenant_database(): void
    {
        $user = User::factory()->create();
        $service = app(WebsiteProvisioningService::class);
        $this->configureServiceForSqlite($service);
        config(['database.default' => 'sqlite']);

        $existingDbName = 'existing_crm_db';
        $dbPath = "{$this->testDbDir}/{$existingDbName}.sqlite";

        // Pre-create database with existing custom table and tenant tables
        $existingPdo = new PDO("sqlite:{$dbPath}");
        $existingPdo->exec('CREATE TABLE custom_tenant_articles (id INTEGER PRIMARY KEY, title TEXT)');
        $existingPdo->exec("INSERT INTO custom_tenant_articles (title) VALUES ('Preserved Article 1')");
        $existingPdo->exec('CREATE TABLE pages (id INTEGER PRIMARY KEY, title TEXT)');
        $existingPdo->exec('CREATE TABLE media (id INTEGER PRIMARY KEY, file_name TEXT)');

        $payload = [
            'website_name' => 'Connected Existing Site',
            'domain' => 'connected.io',
            'provider' => 'digitalocean',
            'tenant_db_mode' => 'connect',
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_name' => $existingDbName,
            'db_username' => 'root',
            'db_password' => 'secret',
            'run_migrations' => 1,
        ];

        $response = $this->actingAs($user)->post(route('websites.store'), $payload);

        $response->assertRedirect(route('websites.index'));
        $website = Website::where('domain', 'connected.io')->firstOrFail();

        $this->assertEquals('provisioned', $website->provisioning_status);
        $this->assertDatabaseHas('website_database_connections', [
            'website_id' => $website->id,
            'database' => $existingDbName,
        ]);

        // Verify existing custom table and data were preserved completely!
        $verifyPdo = new PDO("sqlite:{$dbPath}");
        $stmt = $verifyPdo->query('SELECT title FROM custom_tenant_articles WHERE id = 1');
        $this->assertEquals('Preserved Article 1', $stmt->fetchColumn());
    }

    /**
     * 4. Test connecting with invalid credentials.
     */
    public function test_4_connect_with_invalid_credentials(): void
    {
        $user = User::factory()->create();
        $service = app(WebsiteProvisioningService::class);

        // Inject PDO factory that simulates MySQL access denied
        $service->setPdoFactory(function () {
            throw new PDOException("SQLSTATE[HY000] [1045] Access denied for user 'baduser'@'localhost' (using password: YES)");
        });

        // 1. Test AJAX endpoint
        $testResponse = $this->actingAs($user)->postJson(route('websites.test_database'), [
            'mode' => 'connect',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'some_db',
            'username' => 'baduser',
            'password' => 'wrongpass',
        ]);

        $testResponse->assertStatus(422);
        $testResponse->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Access denied', $testResponse->json('message'));

        // 2. Test form submission with invalid credentials
        $payload = [
            'website_name' => 'Failed Auth Site',
            'domain' => 'failedauth.io',
            'provider' => 'cloud',
            'tenant_db_mode' => 'connect',
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_name' => 'some_db',
            'db_username' => 'baduser',
            'db_password' => 'wrongpass',
        ];

        $response = $this->actingAs($user)->post(route('websites.store'), $payload);

        $website = Website::where('domain', 'failedauth.io')->firstOrFail();
        $response->assertRedirect(route('websites.show', $website));

        $this->assertEquals('failed', $website->provisioning_status);
        $this->assertTrue($website->isProvisioningFailed());
        $this->assertNotNull($website->getProvisioningError());
        $this->assertStringContainsString('Access denied', $website->getProvisioningError());
    }

    /**
     * 5. Test connecting to a database that does not exist.
     */
    public function test_5_connect_to_database_that_does_not_exist(): void
    {
        $user = User::factory()->create();
        $service = app(WebsiteProvisioningService::class);
        $this->configureServiceForSqlite($service);
        config(['database.default' => 'sqlite']);

        $nonExistentDb = 'ghost_database_does_not_exist';

        // 1. Test AJAX endpoint
        $testResponse = $this->actingAs($user)->postJson(route('websites.test_database'), [
            'mode' => 'connect',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $nonExistentDb,
            'username' => 'root',
            'password' => '',
        ]);

        $testResponse->assertStatus(422);
        $testResponse->assertJson([
            'success' => false,
            'database_exists' => false,
        ]);
        $this->assertStringContainsString('does not exist', $testResponse->json('message'));

        // 2. Test Store Form
        $payload = [
            'website_name' => 'Ghost DB Site',
            'domain' => 'ghostdb.io',
            'provider' => 'cloud',
            'tenant_db_mode' => 'connect',
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_name' => $nonExistentDb,
            'db_username' => 'root',
            'db_password' => '',
        ];

        $response = $this->actingAs($user)->post(route('websites.store'), $payload);

        $website = Website::where('domain', 'ghostdb.io')->firstOrFail();
        $this->assertEquals('failed', $website->provisioning_status);
        $this->assertStringContainsString('does not exist', $website->getProvisioningError());
    }

    /**
     * 6. Handling insufficient database permissions.
     */
    public function test_6_handling_insufficient_database_permissions(): void
    {
        $user = User::factory()->create();
        $service = app(WebsiteProvisioningService::class);

        $dbPath = "{$this->testDbDir}/protected_db.sqlite";
        touch($dbPath);

        // Inject factory where database exists but permission verification throws
        $service->setPdoFactory(function ($driver, $host, $port, $database) use ($dbPath) {
            if ($database === null) {
                return new PDO('sqlite::memory:');
            }

            // Readonly mock PDO that throws on table creation / write
            return new class("sqlite:{$dbPath}") extends PDO
            {
                public function exec(string $statement): int|false
                {
                    if (str_contains($statement, 'CREATE')) {
                        throw new PDOException('SQLSTATE[42000]: Access denied; you need (at least one of) the CREATE privilege(s) for this operation');
                    }

                    return parent::exec($statement);
                }
            };
        });

        $website = Website::factory()->create(['provisioning_status' => 'pending']);

        $this->expectException(WebsiteProvisioningException::class);
        $this->expectExceptionMessage('CREATE privilege');

        $service->connectExistingDatabase($website, [
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'protected_db',
            'username' => 'readonly_user',
            'password' => '',
        ]);
    }

    /**
     * 7. Running tenant migrations safely.
     */
    public function test_7_running_tenant_migrations_safely_on_existing_database(): void
    {
        $service = app(WebsiteProvisioningService::class);
        $this->configureServiceForSqlite($service);
        config(['database.default' => 'sqlite']);

        $dbName = 'partial_tenant_db';
        $dbPath = "{$this->testDbDir}/{$dbName}.sqlite";
        if (file_exists($dbPath)) {
            @unlink($dbPath);
        }

        // Pre-create database with existing user table and media table, but missing pages table
        $existingPdo = new PDO("sqlite:{$dbPath}");
        $existingPdo->exec('CREATE TABLE custom_company_info (id INTEGER PRIMARY KEY, note TEXT)');
        $existingPdo->exec("INSERT INTO custom_company_info (note) VALUES ('Valuable Data')");
        $existingPdo->exec('CREATE TABLE media (id INTEGER PRIMARY KEY, file_name TEXT)');

        $website = Website::factory()->create(['provisioning_status' => 'pending']);

        $service->connectExistingDatabase($website, [
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $dbName,
            'username' => 'root',
            'password' => '',
        ], true);

        $website->refresh();
        $this->assertEquals('provisioned', $website->provisioning_status);

        // Verify custom table and its rows remained untouched
        $verifyPdo = new PDO("sqlite:{$dbPath}");
        $stmt = $verifyPdo->query('SELECT note FROM custom_company_info WHERE id = 1');
        $this->assertEquals('Valuable Data', $stmt->fetchColumn());

        // Verify missing pages table was safely migrated
        $service->configureTenantConnection('sqlite', '127.0.0.1', 3306, $dbPath, 'root', '');
        $this->assertTrue(Schema::connection('tenant')->hasTable('pages'));
    }

    /**
     * 8. Preventing duplicate database creation.
     */
    public function test_8_preventing_duplicate_database_creation(): void
    {
        $user = User::factory()->create();
        $service = app(WebsiteProvisioningService::class);
        $this->configureServiceForSqlite($service);
        config(['database.default' => 'sqlite']);

        // Create initial website with database "shared_name_db"
        $existingWebsite = Website::factory()->create();
        WebsiteDatabaseConnection::create([
            'website_id' => $existingWebsite->id,
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'shared_name_db',
            'username' => 'root',
            'password' => 'secret',
        ]);

        // Attempt to create second website with same database name
        $payload = [
            'website_name' => 'Duplicate Attempt Site',
            'domain' => 'duplicate.io',
            'provider' => 'cloud',
            'tenant_db_mode' => 'create',
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_name' => 'shared_name_db',
            'db_username' => 'root',
            'db_password' => 'secret',
        ];

        $response = $this->actingAs($user)->post(route('websites.store'), $payload);

        $newWebsite = Website::where('domain', 'duplicate.io')->firstOrFail();
        $this->assertEquals('failed', $newWebsite->provisioning_status);
        $this->assertStringContainsString('already exists or is already assigned', $newWebsite->getProvisioningError());

        // Also test AJAX endpoint returns warning for create mode with existing DB
        touch("{$this->testDbDir}/shared_name_db.sqlite");
        $testResponse = $this->actingAs($user)->postJson(route('websites.test_database'), [
            'mode' => 'create',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'shared_name_db',
            'username' => 'root',
            'password' => '',
        ]);

        $testResponse->assertStatus(422);
        $this->assertStringContainsString('already exists', $testResponse->json('message'));
    }

    /**
     * 9. Retrying failed provisioning.
     */
    public function test_9_retrying_failed_provisioning(): void
    {
        $user = User::factory()->create();
        $service = app(WebsiteProvisioningService::class);
        $this->configureServiceForSqlite($service);
        config(['database.default' => 'sqlite']);

        // Create website initially with failed status
        $website = Website::factory()->create([
            'provisioning_status' => 'failed',
        ]);
        $website->setProvisioningError('Initial network timeout during provisioning');

        $this->assertEquals('failed', $website->provisioning_status);
        $this->assertNotNull($website->getProvisioningError());

        // Perform retry provisioning request
        $retryPayload = [
            'tenant_db_mode' => 'create',
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_name' => 'retried_success_db',
            'db_username' => 'root',
            'db_password' => 'secret',
        ];

        $response = $this->actingAs($user)->post(route('websites.retry_provisioning', $website), $retryPayload);

        $response->assertRedirect(route('websites.show', $website));
        $response->assertSessionHas('success');

        $website->refresh();
        $this->assertEquals('provisioned', $website->provisioning_status);
        $this->assertNull($website->getProvisioningError());
        $this->assertDatabaseHas('website_database_connections', [
            'website_id' => $website->id,
            'database' => 'retried_success_db',
        ]);
    }

    /**
     * 10. Verifying that two websites can use separate tenant databases.
     */
    public function test_10_verifying_two_websites_use_separate_tenant_databases(): void
    {
        $service = app(WebsiteProvisioningService::class);
        $this->configureServiceForSqlite($service);
        config(['database.default' => 'sqlite']);

        $website1 = Website::factory()->create(['website_name' => 'Tenant One', 'domain' => 'tenant1.io']);
        $website2 = Website::factory()->create(['website_name' => 'Tenant Two', 'domain' => 'tenant2.io']);

        $db1Path = "{$this->testDbDir}/tenant_db_one.sqlite";
        $db2Path = "{$this->testDbDir}/tenant_db_two.sqlite";

        $conn1 = $service->provisionNewDatabase($website1, [
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'tenant_db_one',
            'username' => 'root',
            'password' => '',
        ]);

        $conn2 = $service->provisionNewDatabase($website2, [
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'tenant_db_two',
            'username' => 'root',
            'password' => '',
        ]);

        $website1->refresh();
        $website2->refresh();

        $this->assertEquals('provisioned', $website1->provisioning_status);
        $this->assertEquals('provisioned', $website2->provisioning_status);

        // Verify distinct connection records
        $this->assertNotEquals($conn1->id, $conn2->id);
        $this->assertNotEquals($conn1->database, $conn2->database);

        // Insert distinctive data into Tenant 1 database
        $pdo1 = new PDO("sqlite:{$db1Path}");
        $pdo1->exec("INSERT INTO pages (title, slug, page_type) VALUES ('Site 1 Homepage', 'home', 'standard')");

        // Insert distinctive data into Tenant 2 database
        $pdo2 = new PDO("sqlite:{$db2Path}");
        $pdo2->exec("INSERT INTO pages (title, slug, page_type) VALUES ('Site 2 Homepage', 'home', 'standard')");

        // Assert data isolation: Tenant 1 sees its page, Tenant 2 sees its page
        $stmt1 = $pdo1->query("SELECT title FROM pages WHERE slug = 'home'");
        $this->assertEquals('Site 1 Homepage', $stmt1->fetchColumn());

        $stmt2 = $pdo2->query("SELECT title FROM pages WHERE slug = 'home'");
        $this->assertEquals('Site 2 Homepage', $stmt2->fetchColumn());
    }
}
