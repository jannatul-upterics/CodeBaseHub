<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Domain;
use App\Models\Template;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that root URL loads the dashboard successfully for authenticated admin.
     */
    public function test_root_loads_dashboard_successfully(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('CodeBaseHub');
        $response->assertSee('Admin Dashboard');
    }

    /**
     * Test that the dashboard route renders successfully with summary cards.
     */
    public function test_dashboard_renders_successfully_with_summary_cards(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('CodeBaseHub');
        $response->assertSee('Websites');
        $response->assertSee('Customers');
        $response->assertSee('Themes');
        $response->assertSee('Templates');
        $response->assertSee('Domains');
    }

    /**
     * Test that empty database displays onboarding guidance.
     */
    public function test_dashboard_renders_onboarding_guidance_when_database_is_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee("Welcome to CodeBaseHub — Let's Get Your Platform Started!", false);
        $response->assertSee('Add a Customer');
        $response->assertSee('Create a Website');
        $response->assertSee('Configure Domains &amp; Themes', false);
    }

    /**
     * Test that populated database calculates and renders real database metrics and feeds.
     */
    public function test_dashboard_displays_real_calculated_metrics_when_populated(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()->create(['name' => 'Acme Corporation']);
        $site = Website::factory()->create([
            'customer_id' => $customer->id,
            'website_name' => 'Acme Flagship Store',
            'domain' => 'acmeflagship.com',
            'provider' => 'cloud',
            'status' => 1,
        ]);

        Domain::factory()->create([
            'website_id' => $site->id,
            'domain_name' => 'acmeflagship.com',
            'verified_at' => now(),
            'is_primary' => true,
            'ssl_enabled' => true,
        ]);

        Theme::factory()->create(['theme_name' => 'Nordic Minimalist']);
        Template::factory()->create(['template_name' => 'Startup Showcase']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Acme Corporation');
        $response->assertSee('Acme Flagship Store');
        $response->assertSee('acmeflagship.com');
        $response->assertSee('Verified DNS');
        $response->assertSee('Cloud');
        $response->assertDontSee("Welcome to CodeBaseHub — Let's Get Your Platform Started!");
    }

    /**
     * Test that all 10 application navigation modules are implemented and return 200 OK.
     */
    public function test_all_sidebar_navigation_modules_are_accessible_for_admin(): void
    {
        $user = User::factory()->create();

        $routes = [
            route('dashboard'),
            route('customers.index'),
            route('websites.index'),
            route('themes.index'),
            route('templates.index'),
            route('domains.index'),
            route('pages.index'),
            route('media.index'),
            route('settings.index'),
            route('metadata.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertStatus(200);
        }
    }

    /**
     * Test that customer creation works from the dashboard quick action.
     */
    public function test_customer_creation_works(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Globex Corporation',
            'email' => 'contact@globex.org',
            'password' => 'secretPassword123',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Globex Corporation',
            'email' => 'contact@globex.org',
        ]);
    }

    /**
     * Test that the websites route renders successfully with columns.
     */
    public function test_websites_page_renders_successfully_with_required_columns_and_controls(): void
    {
        $user = User::factory()->create();

        Website::factory()->create([
            'website_name' => 'TechSprint Portal',
            'domain' => 'techsprint.io',
            'provider' => 'cloud',
        ]);

        $response = $this->actingAs($user)->get(route('websites.index'));

        $response->assertStatus(200);
        $response->assertSee('CodeBaseHub');
        $response->assertSee('Websites');
        $response->assertSee('Add Website');
        $response->assertSee('Website');
        $response->assertSee('Domain');
        $response->assertSee('Actions');
        $response->assertSee('TechSprint Portal');
        $response->assertSee('techsprint.io');
    }

    /**
     * Test that unauthenticated users are redirected from the dashboard.
     */
    public function test_unauthenticated_users_are_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get('/')->assertRedirect(route('login'));
    }
}
