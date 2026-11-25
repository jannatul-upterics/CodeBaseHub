<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Domain;
use App\Models\Media;
use App\Models\Metadata;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Setting;
use App\Models\Template;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use App\Models\WebsiteThemeSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

class CentralAndTenantDatabaseSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected string $testDir;

    /** @var list<string> */
    protected array $createdDbFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = storage_path('framework/testing/separation_dbs');
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
     * Create an isolated SQLite tenant database containing all tenant-scoped tables.
     */
    protected function createFullTenantDatabase(string $filename): string
    {
        $path = "{$this->testDir}/{$filename}.sqlite";
        touch($path);
        $this->createdDbFiles[] = $path;

        $pdo = new PDO("sqlite:{$path}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec('CREATE TABLE pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            website_id INTEGER NULL,
            tenant_page_id INTEGER NULL,
            title TEXT NOT NULL,
            slug TEXT NOT NULL,
            page_type TEXT NOT NULL DEFAULT "standard",
            is_homepage INTEGER NOT NULL DEFAULT 0,
            is_template INTEGER NOT NULL DEFAULT 0,
            status INTEGER NOT NULL DEFAULT 1,
            content TEXT NULL,
            meta_title TEXT NULL,
            meta_description TEXT NULL,
            canonical_url TEXT NULL,
            og_image TEXT NULL,
            sections_data TEXT NULL,
            sync_hash TEXT NULL,
            parent_id INTEGER NULL,
            template_id INTEGER NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_indexable INTEGER NOT NULL DEFAULT 1,
            master_page_id INTEGER NULL,
            deleted_at TEXT NULL,
            created_at TEXT NULL,
            updated_at TEXT NULL
        )');

        $pdo->exec('CREATE TABLE page_sections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            section_name TEXT NOT NULL,
            section_type TEXT NOT NULL,
            content TEXT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            status INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NULL,
            updated_at TEXT NULL
        )');

        $pdo->exec('CREATE TABLE media (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            website_id INTEGER NULL,
            file_name TEXT NOT NULL,
            file_path TEXT NOT NULL,
            file_type TEXT NOT NULL,
            mime_type TEXT NULL,
            file_size INTEGER NULL,
            alt_text TEXT NULL,
            status INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NULL,
            updated_at TEXT NULL
        )');

        $pdo->exec('CREATE TABLE settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            website_id INTEGER NULL,
            setting_key TEXT NOT NULL,
            setting_value TEXT NULL,
            created_at TEXT NULL,
            updated_at TEXT NULL
        )');

        $pdo->exec('CREATE TABLE metadata (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            website_id INTEGER NULL,
            page_id INTEGER NULL,
            meta_title TEXT NULL,
            meta_description TEXT NULL,
            canonical_url TEXT NULL,
            robots TEXT DEFAULT "index, follow",
            og_image TEXT NULL,
            created_at TEXT NULL,
            updated_at TEXT NULL
        )');

        return $path;
    }

    /**
     * Create a Website tenant with Customer, Template, Theme, Domain, and WebsiteDatabaseConnection in central DB.
     */
    protected function createCentralWebsite(string $domain, string $dbPath): Website
    {
        $customer = Customer::factory()->create();
        $template = Template::factory()->create(['template_name' => "Template {$domain}"]);
        $theme = Theme::factory()->create(['theme_name' => "Theme {$domain}"]);

        $website = Website::create([
            'customer_id' => $customer->id,
            'template_id' => $template->id,
            'website_name' => "Website {$domain}",
            'domain' => $domain,
            'provider' => 'cloud',
            'status' => 1,
            'provisioning_status' => 'provisioned',
        ]);

        WebsiteThemeSetting::create([
            'website_id' => $website->id,
            'theme_id' => $theme->id,
            'setting_key' => 'active_theme',
            'setting_value' => 'active',
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
            'password' => '',
        ]);

        return $website;
    }

    public function test_central_models_always_use_central_connection_even_when_tenancy_is_initialized(): void
    {
        $user = User::factory()->create(['email' => 'admin@central.test']);
        $dbPath = $this->createFullTenantDatabase('central_check');
        $website = $this->createCentralWebsite('central-check.test', $dbPath);

        $centralConn = config('tenancy.database.central_connection');

        tenancy()->initialize($website);
        $this->assertSame('tenant', DB::getDefaultConnection());

        // Central models still report central connection and resolve from central DB
        $this->assertSame($centralConn, (new Website)->getConnectionName());
        $this->assertSame($centralConn, (new Domain)->getConnectionName());
        $this->assertSame($centralConn, (new WebsiteDatabaseConnection)->getConnectionName());
        $this->assertSame($centralConn, (new Template)->getConnectionName());
        $this->assertSame($centralConn, (new Theme)->getConnectionName());
        $this->assertSame($centralConn, (new WebsiteThemeSetting)->getConnectionName());
        $this->assertSame($centralConn, (new Customer)->getConnectionName());
        $this->assertSame($centralConn, (new User)->getConnectionName());

        $this->assertSame('admin@central.test', User::where('id', $user->id)->value('email'));
        $this->assertSame('Template central-check.test', $website->template->template_name);
        $this->assertSame('Theme central-check.test', $website->themes->first()->theme_name);
        $this->assertNotNull($website->customer);
        $this->assertNotNull($website->domains->first());
        $this->assertNotNull($website->databaseConnection);

        // Provisioning error helpers on Website still target central settings table
        $website->setProvisioningError('Sample central error');
        $this->assertSame('Sample central error', $website->getProvisioningError());
        $this->assertSame(0, Setting::count(), 'Tenant settings table must remain empty');

        tenancy()->end();
    }

    public function test_tenant_models_use_active_tenant_connection_and_isolate_across_websites(): void
    {
        $dbA = $this->createFullTenantDatabase('tenant_a');
        $dbB = $this->createFullTenantDatabase('tenant_b');

        $websiteA = $this->createCentralWebsite('site-a.test', $dbA);
        $websiteB = $this->createCentralWebsite('site-b.test', $dbB);

        // Populate Tenant A data
        tenancy()->initialize($websiteA);
        $pageA = Page::create([
            'title' => 'Alpha Page',
            'slug' => 'alpha-home',
            'page_type' => 'landing',
            'status' => 1,
        ]);
        PageSection::create([
            'page_id' => $pageA->id,
            'section_name' => 'Alpha Hero',
            'section_type' => 'hero',
            'content' => ['heading' => 'Welcome Alpha'],
        ]);
        Media::create([
            'file_name' => 'alpha-logo.png',
            'file_path' => 'alpha/logo.png',
            'file_type' => 'image',
        ]);
        Setting::create([
            'setting_key' => 'site_name',
            'setting_value' => 'Alpha Brand',
        ]);
        Metadata::create([
            'page_id' => $pageA->id,
            'meta_title' => 'Alpha SEO Title',
        ]);
        tenancy()->end();

        // Populate Tenant B data and ensure Tenant A data is completely isolated
        tenancy()->initialize($websiteB);
        $this->assertSame(0, Page::count());
        $this->assertSame(0, PageSection::count());
        $this->assertSame(0, Media::count());
        $this->assertSame(0, Setting::count());
        $this->assertSame(0, Metadata::count());

        $pageB = Page::create([
            'title' => 'Beta Page',
            'slug' => 'beta-home',
            'page_type' => 'landing',
            'status' => 1,
        ]);
        Setting::create([
            'setting_key' => 'site_name',
            'setting_value' => 'Beta Brand',
        ]);

        // Switching directly from Tenant B to Tenant A purges stale PDO connection
        tenancy()->initialize($websiteA);
        $this->assertSame('Alpha Page', Page::first()->title);
        $this->assertSame('Alpha Hero', PageSection::first()->section_name);
        $this->assertSame('alpha-logo.png', Media::first()->file_name);
        $this->assertSame('Alpha Brand', Setting::where('setting_key', 'site_name')->value('setting_value'));
        $this->assertSame('Alpha SEO Title', Metadata::first()->meta_title);
        $this->assertNull(Page::where('slug', 'beta-home')->first());

        tenancy()->end();

        // Central DB must not have any of the tenant-created records
        $this->assertSame(0, Page::count());
        $this->assertSame(0, PageSection::count());
        $this->assertSame(0, Media::count());
        $this->assertSame(0, Metadata::count());
        $this->assertNull(Config::get('database.connections.tenant'));
        $this->assertArrayNotHasKey('tenant', DB::getConnections());
    }
}
