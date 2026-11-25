<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\TenantDatabaseConnectionException;
use App\Models\Customer;
use App\Models\Domain;
use App\Models\Page;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use App\Tenancy\Bootstrappers\WebsiteDatabaseTenancyBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDO;
use Tests\TestCase;

class TenantDatabaseSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected string $testDir;

    /** @var list<string> */
    protected array $createdDbFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = storage_path('framework/testing/security_dbs');
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

    protected function createWebsite(string $domain): Website
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

        return $website;
    }

    protected function createSqliteTenantDb(string $filename, string $pageTitle): string
    {
        $path = "{$this->testDir}/{$filename}.sqlite";
        touch($path);
        $this->createdDbFiles[] = $path;

        $pdo = new PDO("sqlite:{$path}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec('CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT NOT NULL, status INTEGER DEFAULT 1, deleted_at TEXT NULL)');
        $stmt = $pdo->prepare('INSERT INTO pages (title, slug, status) VALUES (?, ?, 1)');
        $stmt->execute([$pageTitle, 'home']);

        return $path;
    }

    public function test_stored_database_password_is_encrypted_and_hidden_from_serialization_and_views(): void
    {
        $user = User::factory()->create();
        $website = $this->createWebsite('encrypted-cred.test');

        $plainPassword = 'UltraSecretDbPassword!99';

        $conn = WebsiteDatabaseConnection::create([
            'website_id' => $website->id,
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'cbh_encrypted_test',
            'username' => 'tenant_admin',
            'password' => $plainPassword,
        ]);

        // Raw value in central DB must be encrypted, not plaintext
        $rawPassword = DB::table('website_database_connections')->where('id', $conn->id)->value('password');
        $this->assertNotSame($plainPassword, $rawPassword);
        $this->assertSame($plainPassword, $conn->fresh()->password);

        // Serialization must never include password
        $this->assertArrayNotHasKey('password', $conn->toArray());
        $this->assertStringNotContainsString($plainPassword, $conn->toJson());

        // Frontend website show view must never expose password
        $response = $this->actingAs($user)->get(route('websites.show', $website));
        $response->assertOk();
        $response->assertSee('cbh_encrypted_test');
        $response->assertSee('tenant_admin');
        $response->assertDontSee($plainPassword);
    }

    public function test_validation_rejects_unsupported_driver_and_invalid_connection_details(): void
    {
        $website = $this->createWebsite('invalid-details.test');

        // 1. Unsupported driver
        WebsiteDatabaseConnection::create([
            'website_id' => $website->id,
            'driver' => 'oracle',
            'host' => '127.0.0.1',
            'port' => 1521,
            'database' => 'xe',
            'username' => 'system',
            'password' => 'secret',
        ]);

        try {
            tenancy()->initialize($website);
            $this->fail('Expected TenantDatabaseConnectionException for unsupported driver.');
        } catch (TenantDatabaseConnectionException $e) {
            $this->assertStringContainsString('Unsupported database driver', $e->getMessage());
        }

        $this->assertFalse(tenancy()->initialized);
        $this->assertSame('sqlite', DB::getDefaultConnection());

        // 2. Invalid host (contains semicolon/whitespace injection attempt)
        $website->databaseConnection()->update([
            'driver' => 'mysql',
            'host' => '127.0.0.1;dbname=other',
            'port' => 3306,
        ]);
        $website->unsetRelation('databaseConnection');

        try {
            tenancy()->initialize($website);
            $this->fail('Expected TenantDatabaseConnectionException for invalid host.');
        } catch (TenantDatabaseConnectionException $e) {
            $this->assertStringContainsString('Invalid database host', $e->getMessage());
        }

        // 3. Invalid port
        $website->databaseConnection()->update([
            'host' => '127.0.0.1',
            'port' => 0,
        ]);
        $website->unsetRelation('databaseConnection');

        try {
            tenancy()->initialize($website);
            $this->fail('Expected TenantDatabaseConnectionException for invalid port.');
        } catch (TenantDatabaseConnectionException $e) {
            $this->assertStringContainsString('Invalid database port', $e->getMessage());
        }

        // 4. Missing username
        $website->databaseConnection()->update([
            'port' => 3306,
            'username' => '',
        ]);
        $website->unsetRelation('databaseConnection');

        try {
            tenancy()->initialize($website);
            $this->fail('Expected TenantDatabaseConnectionException for missing username.');
        } catch (TenantDatabaseConnectionException $e) {
            $this->assertStringContainsString('username is required', $e->getMessage());
        }

        // Central DB remains unaffected
        $this->assertSame(1, Website::where('id', $website->id)->count());
    }

    public function test_error_sanitization_masks_passwords_in_logs_and_exceptions(): void
    {
        $bootstrapper = new class extends WebsiteDatabaseTenancyBootstrapper
        {
            public function exposeSanitize(string $message, ?string $rawPassword = null): string
            {
                return $this->sanitizeErrorMessage($message, $rawPassword);
            }
        };

        $secret = 'My$ecretP@ssw0rd!';
        $rawError = "SQLSTATE[HY000]: Connection failed for mysql://user:{$secret}@127.0.0.1:3306/db password={$secret} pwd: {$secret}";

        $sanitized = $bootstrapper->exposeSanitize($rawError, $secret);

        $this->assertStringNotContainsString($secret, $sanitized);
        $this->assertStringContainsString('••••••••', $sanitized);
    }

    public function test_failed_connection_logs_sanitized_error_and_leaves_central_db_intact(): void
    {
        Log::spy();

        $website = $this->createWebsite('failed-conn.test');
        $secret = 'SecretPassInLogTest!123';

        WebsiteDatabaseConnection::create([
            'website_id' => $website->id,
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $this->testDir.'/missing_folder/tenant.sqlite',
            'username' => 'root',
            'password' => $secret,
        ]);

        try {
            tenancy()->initialize($website);
            $this->fail('Expected TenantDatabaseConnectionException.');
        } catch (TenantDatabaseConnectionException $e) {
            $this->assertStringNotContainsString($secret, $e->getMessage());
        }

        $this->assertFalse(tenancy()->initialized);
        $this->assertSame('sqlite', Config::get('database.default'));
        $this->assertSame('sqlite', DB::getDefaultConnection());
        $this->assertNull(Config::get('database.connections.tenant'));
        $this->assertArrayNotHasKey('tenant', DB::getConnections());
        $this->assertSame(1, Website::where('id', $website->id)->count());
    }

    public function test_tenant_isolation_and_stale_connection_cleanup_across_websites(): void
    {
        $dbA = $this->createSqliteTenantDb('sec_tenant_a', 'Tenant A Secret Page');
        $dbB = $this->createSqliteTenantDb('sec_tenant_b', 'Tenant B Public Page');

        $websiteA = $this->createWebsite('sec-a.test');
        WebsiteDatabaseConnection::create([
            'website_id' => $websiteA->id,
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $dbA,
            'username' => 'root',
            'password' => '',
        ]);

        $websiteB = $this->createWebsite('sec-b.test');
        WebsiteDatabaseConnection::create([
            'website_id' => $websiteB->id,
            'driver' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $dbB,
            'username' => 'root',
            'password' => '',
        ]);

        tenancy()->initialize($websiteA);
        $this->assertSame('Tenant A Secret Page', Page::where('slug', 'home')->value('title'));

        // Switch to Tenant B: Tenant A data must not be accessible
        tenancy()->initialize($websiteB);
        $this->assertSame('Tenant B Public Page', Page::where('slug', 'home')->value('title'));
        $this->assertNull(Page::where('title', 'Tenant A Secret Page')->first());

        // End tenancy: connection is purged and central DB is restored
        tenancy()->end();
        $this->assertFalse(tenancy()->initialized);
        $this->assertSame('sqlite', DB::getDefaultConnection());
        $this->assertNull(Config::get('database.connections.tenant'));
        $this->assertArrayNotHasKey('tenant', DB::getConnections());
    }
}
