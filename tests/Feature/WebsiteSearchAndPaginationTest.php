<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteSearchAndPaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test server-side search by website name.
     */
    public function test_can_search_websites_by_name(): void
    {
        $user = User::factory()->create();

        Website::factory()->create([
            'website_name' => 'Alpha Velocity Hub',
            'domain' => 'alphavelocity.io',
            'template_name' => 'B2B SaaS Minimal',
        ]);

        Website::factory()->create([
            'website_name' => 'Beta Quantum Store',
            'domain' => 'betaquantum.com',
            'template_name' => 'Storefront Pro',
        ]);

        $response = $this->actingAs($user)->get(route('websites.index', ['search' => 'Velocity']));

        $response->assertStatus(200);
        $response->assertSee('Alpha Velocity Hub');
        $response->assertDontSee('Beta Quantum Store');
    }

    /**
     * Test server-side search by template name.
     */
    public function test_can_search_websites_by_template_name(): void
    {
        $user = User::factory()->create();

        Website::factory()->create([
            'website_name' => 'Design Studio One',
            'template_name' => 'Creative Portfolio',
            'domain' => 'designstudio.agency',
        ]);

        Website::factory()->create([
            'website_name' => 'Medical Clinic App',
            'template_name' => 'Healthcare Plus',
            'domain' => 'medicalclinic.org',
        ]);

        $response = $this->actingAs($user)->get(route('websites.index', ['search' => 'Creative Portfolio']));

        $response->assertStatus(200);
        $response->assertSee('Design Studio One');
        $response->assertDontSee('Medical Clinic App');
    }

    /**
     * Test server-side search by domain name.
     */
    public function test_can_search_websites_by_domain(): void
    {
        $user = User::factory()->create();

        Website::factory()->create([
            'website_name' => 'First Tech Node',
            'domain' => 'special-domain-xyz.net',
            'template_name' => 'Startup Showcase v2',
        ]);

        Website::factory()->create([
            'website_name' => 'Second Tech Node',
            'domain' => 'other-domain-abc.net',
            'template_name' => 'Startup Showcase v2',
        ]);

        $response = $this->actingAs($user)->get(route('websites.index', ['search' => 'special-domain-xyz']));

        $response->assertStatus(200);
        $response->assertSee('special-domain-xyz.net');
        $response->assertDontSee('other-domain-abc.net');
    }

    /**
     * Test search matches case-insensitively and on partial substrings.
     */
    public function test_search_is_case_insensitive_and_matches_substrings(): void
    {
        $user = User::factory()->create();

        Website::factory()->create([
            'website_name' => 'TechSprint Portal',
            'domain' => 'techsprint.io',
        ]);

        $response = $this->actingAs($user)->get(route('websites.index', ['search' => 'techsprint']));

        $response->assertStatus(200);
        $response->assertSee('TechSprint Portal');
    }

    /**
     * Test combined search and template filtering.
     */
    public function test_combined_search_and_template_filtering(): void
    {
        $user = User::factory()->create();

        Website::factory()->create([
            'website_name' => 'Nova Portal',
            'template_name' => 'Startup Showcase v2',
            'domain' => 'novaportal-one.io',
        ]);

        Website::factory()->create([
            'website_name' => 'Nova Store',
            'template_name' => 'Storefront Pro',
            'domain' => 'novastore-two.io',
        ]);

        $response = $this->actingAs($user)->get(route('websites.index', [
            'search' => 'Nova',
            'template' => 'Startup Showcase v2',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Nova Portal');
        $response->assertDontSee('Nova Store');
    }

    /**
     * Test pagination limits output to 10 records per page.
     */
    public function test_pagination_limits_records_per_page(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 15; $i++) {
            Website::factory()->create([
                'website_name' => sprintf('Site %02d Record', $i),
                'domain' => sprintf('site-%02d.org', $i),
            ]);
        }

        $page1 = $this->actingAs($user)->get(route('websites.index', ['page' => 1]));
        $page1->assertStatus(200);
        $page1->assertSee('Site 15 Record');
        $page1->assertSee('Site 06 Record');
        $page1->assertDontSee('Site 05 Record');

        $page2 = $this->actingAs($user)->get(route('websites.index', ['page' => 2]));
        $page2->assertStatus(200);
        $page2->assertSee('Site 05 Record');
        $page2->assertSee('Site 01 Record');
    }

    /**
     * Test that search filter parameters are preserved in pagination links.
     */
    public function test_pagination_preserves_search_query_strings(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 14; $i++) {
            Website::factory()->create([
                'website_name' => "FilteredBatch {$i}",
                'domain' => "batch-{$i}.com",
            ]);
        }

        $response = $this->actingAs($user)->get(route('websites.index', [
            'search' => 'FilteredBatch',
            'page' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('search=FilteredBatch');
        $response->assertSee('page=2');
    }

    /**
     * Test empty state when a search returns no matching records.
     */
    public function test_empty_search_results_shows_clear_option_and_empty_state(): void
    {
        $user = User::factory()->create();

        Website::factory()->create([
            'website_name' => 'Existing Portal',
            'domain' => 'existing.io',
        ]);

        $response = $this->actingAs($user)->get(route('websites.index', [
            'search' => 'NonExistentTermXYZ',
        ]));

        $response->assertStatus(200);
        $response->assertSee('No matching websites found');
        $response->assertSee('Clear Search Filters');
        $response->assertSee('Active Filters:');
        $response->assertSee('NonExistentTermXYZ');
    }

    /**
     * Test empty state when no websites exist in the database.
     */
    public function test_empty_state_when_database_has_no_records(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('websites.index'));

        $response->assertStatus(200);
        $response->assertSee('No websites registered yet');
        $response->assertSee('Add Your First Website');
    }

    /**
     * Test dashboard statistics reflect actual database totals.
     */
    public function test_dashboard_displays_database_backed_statistics(): void
    {
        $user = User::factory()->create();

        Website::factory()->create([
            'website_name' => 'Portal 1',
            'template_name' => 'Theme Alpha',
            'domain' => 'domain1.com',
        ]);

        Website::factory()->create([
            'website_name' => 'Portal 2',
            'template_name' => 'Theme Alpha',
            'domain' => 'domain2.com',
        ]);

        Website::factory()->create([
            'website_name' => 'Portal 3',
            'template_name' => 'Theme Beta',
            'domain' => 'domain3.com',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('3'); // 3 total websites
        $response->assertSee('2'); // 2 distinct templates
        $response->assertSee('Database-backed inventory');
    }
}
