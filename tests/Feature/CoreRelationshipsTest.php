<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteThemeSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Customer-to-Website: Website can be created with customer association.
     */
    public function test_website_can_be_associated_with_customer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Apex Enterprises']);

        $response = $this->actingAs($user)->post(route('websites.store'), [
            'website_name' => 'Apex Official Portal',
            'domain' => 'apexofficial.org',
            'customer_id' => $customer->id,
            'provider' => 'cloud',
            'status' => 1,
        ]);

        $response->assertRedirect(route('websites.index'));

        $this->assertDatabaseHas('websites', [
            'website_name' => 'Apex Official Portal',
            'domain' => 'apexofficial.org',
            'customer_id' => $customer->id,
        ]);

        // Verify Eloquent relationship
        $website = Website::where('domain', 'apexofficial.org')->firstOrFail();
        $this->assertEquals($customer->id, $website->customer->id);
        $this->assertTrue($customer->websites->contains($website));
    }

    /**
     * Customer-to-Website: Customer detail page renders customer's websites.
     */
    public function test_customer_detail_page_renders_all_associated_websites(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Stark Industries']);

        $site1 = Website::factory()->create([
            'customer_id' => $customer->id,
            'website_name' => 'Stark Clean Energy',
            'domain' => 'starkenergy.io',
        ]);

        $site2 = Website::factory()->create([
            'customer_id' => $customer->id,
            'website_name' => 'Stark Security Systems',
            'domain' => 'starksecurity.io',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));
        $response->assertStatus(200);
        $response->assertSee('Stark Clean Energy');
        $response->assertSee('starkenergy.io');
        $response->assertSee('Stark Security Systems');
        $response->assertSee('starksecurity.io');
    }

    /**
     * Customer deletion sets null on website.customer_id without deleting website.
     */
    public function test_customer_deletion_protects_website_records(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Wayne Tech']);
        $website = Website::factory()->create([
            'customer_id' => $customer->id,
            'website_name' => 'Wayne Enterprises Main',
            'domain' => 'wayne.corp',
        ]);

        $this->actingAs($user)->delete(route('customers.destroy', $customer));

        // Customer is gone
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);

        // Website exists, customer_id is null
        $website->refresh();
        $this->assertNull($website->customer_id);
        $this->assertEquals('Wayne Enterprises Main', $website->website_name);
    }

    /**
     * Website-to-Theme: Website can be created with initial theme assignment.
     */
    public function test_website_creation_with_theme_stores_website_theme_settings(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->create(['theme_name' => 'Titan Dark Theme']);

        $this->actingAs($user)->post(route('websites.store'), [
            'website_name' => 'Titan Portal',
            'domain' => 'titanportal.com',
            'theme_id' => $theme->id,
            'provider' => 'aws',
            'status' => 1,
        ]);

        $website = Website::where('domain', 'titanportal.com')->firstOrFail();

        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'theme_id' => $theme->id,
            'setting_key' => 'active_theme',
            'setting_value' => 'active',
        ]);

        $this->assertTrue($website->themes->contains($theme));
        $this->assertTrue($theme->websites->contains($website));
    }

    /**
     * Website-to-Theme: Theme can be updated or switched via assignTheme endpoint.
     */
    public function test_theme_can_be_reassigned_via_theme_endpoint(): void
    {
        $user = User::factory()->create();
        $theme1 = Theme::factory()->create(['theme_name' => 'Theme Light']);
        $theme2 = Theme::factory()->create(['theme_name' => 'Theme Dark']);
        $website = Website::factory()->create();

        // Assign theme 1
        WebsiteThemeSetting::create([
            'website_id' => $website->id,
            'theme_id' => $theme1->id,
            'setting_key' => 'active_theme',
            'setting_value' => 'active',
        ]);

        // Switch to theme 2
        $response = $this->actingAs($user)->post(route('websites.theme', $website), [
            'theme_id' => $theme2->id,
            'setting_key' => 'active_theme',
            'setting_value' => 'active',
        ]);

        $response->assertRedirect(route('websites.show', $website));

        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'theme_id' => $theme2->id,
            'setting_key' => 'active_theme',
        ]);

        $website->refresh();
        $this->assertEquals($theme2->id, $website->themeSettings()->where('setting_key', 'active_theme')->value('theme_id'));
    }

    /**
     * Website deletion cascades and cleans up website_theme_settings.
     */
    public function test_website_deletion_cascades_theme_settings(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->create();
        $website = Website::factory()->create();

        $setting = WebsiteThemeSetting::create([
            'website_id' => $website->id,
            'theme_id' => $theme->id,
            'setting_key' => 'active_theme',
            'setting_value' => 'active',
        ]);

        $this->actingAs($user)->delete(route('websites.destroy', $website));

        $this->assertDatabaseMissing('websites', ['id' => $website->id]);
        $this->assertDatabaseMissing('website_theme_settings', ['id' => $setting->id]);
        // Theme itself remains intact
        $this->assertDatabaseHas('themes', ['id' => $theme->id]);
    }
}
