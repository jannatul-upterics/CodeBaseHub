<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Domain;
use App\Models\Media;
use App\Models\Metadata;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndReleaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Executes the complete Step 1 through Step 8 workflow specified in Step 5:
     * 1. Create a customer.
     * 2. Create a website and associate the customer where applicable.
     * 3. Assign an existing theme and save its settings.
     * 4. Configure the website's domain.
     * 5. Create pages and page sections.
     * 6. Upload media and associate it with the correct website.
     * 7. Save website settings and page metadata.
     * 8. Edit and delete records while preserving relationship integrity.
     * Verifies that saved data remains available after reloading the relevant pages.
     */
    public function test_complete_eight_step_end_to_end_workflow(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        // -------------------------------------------------------------
        // STEP 1. Create a customer
        // -------------------------------------------------------------
        $customerResponse = $this->actingAs($admin)->post(route('customers.store'), [
            'name' => 'Nova Horizon Group',
            'email' => 'contact@novahorizon.com',
            'password' => 'SecurePass123!',
        ]);
        $customerResponse->assertRedirect(route('customers.index'));

        $customer = Customer::where('email', 'contact@novahorizon.com')->firstOrFail();
        $this->assertEquals('Nova Horizon Group', $customer->name);

        // Verify customer page reloads with saved data
        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertStatus(200)
            ->assertSee('Nova Horizon Group')
            ->assertSee('contact@novahorizon.com');

        // -------------------------------------------------------------
        // STEP 2. Create a website and associate the customer
        // -------------------------------------------------------------
        $websiteResponse = $this->actingAs($admin)->post(route('websites.store'), [
            'customer_id' => $customer->id,
            'website_name' => 'Nova Horizon Digital',
            'domain' => 'novahorizon.com',
            'provider' => 'cloud',
            'description' => 'Flagship enterprise platform for Nova Horizon.',
            'status' => 1,
        ]);
        $websiteResponse->assertRedirect(route('websites.index'));

        $website = Website::where('domain', 'novahorizon.com')->firstOrFail();
        $this->assertEquals($customer->id, $website->customer_id);
        $this->assertEquals('Nova Horizon Digital', $website->website_name);

        // Reload customer show page: confirm website is listed under customer
        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertStatus(200)
            ->assertSee('Nova Horizon Digital')
            ->assertSee('novahorizon.com');

        // Reload website show page
        $this->actingAs($admin)
            ->get(route('websites.show', $website))
            ->assertStatus(200)
            ->assertSee('Nova Horizon Digital')
            ->assertSee('novahorizon.com');

        // -------------------------------------------------------------
        // STEP 3. Assign an existing theme and save its settings
        // -------------------------------------------------------------
        $theme = Theme::factory()->create([
            'theme_name' => 'Apex Nebula Theme',
            'slug' => 'apex-nebula',
        ]);

        $themeAssignResponse = $this->actingAs($admin)->post(route('websites.theme.set_active', $website), [
            'theme_id' => $theme->id,
        ]);
        $themeAssignResponse->assertRedirect(route('websites.theme.index', $website));

        // Save custom theme overrides
        $themeSettingsResponse = $this->actingAs($admin)->post(route('websites.theme.settings.update', $website), [
            'theme_id' => $theme->id,
            'primary_color' => '#2563eb',
            'accent_color' => '#10b981',
            'font_family' => 'Instrument Sans',
            'layout_mode' => 'boxed',
            'navbar_style' => 'sticky',
            'custom_css' => '/* Nova custom styling */',
        ]);
        $themeSettingsResponse->assertRedirect(route('websites.theme.index', $website));

        // Reload theme configuration workspace page
        $this->actingAs($admin)
            ->get(route('websites.theme.index', $website))
            ->assertStatus(200)
            ->assertSee('Apex Nebula Theme')
            ->assertSee('#2563eb')
            ->assertSee('Instrument Sans');

        // -------------------------------------------------------------
        // STEP 4. Configure the website's domain
        // -------------------------------------------------------------
        $domainResponse = $this->actingAs($admin)->post(route('websites.domains.store', $website), [
            'domain_name' => 'app.novahorizon.com',
            'is_primary' => '1',
            'status' => 1,
        ]);
        $domainResponse->assertRedirect(route('websites.domains.index', $website));

        $domain = Domain::where('domain_name', 'app.novahorizon.com')->firstOrFail();
        $this->assertTrue($domain->is_primary);

        // Verify DNS and SSL
        $this->actingAs($admin)->post(route('websites.domains.verify', [$website, $domain]), [
            'force_verify' => '1',
        ]);
        $domain->refresh();
        $this->assertNotNull($domain->verified_at);
        $this->assertTrue($domain->ssl_enabled);

        // Reload domain workspace page
        $this->actingAs($admin)
            ->get(route('websites.domains.index', $website))
            ->assertStatus(200)
            ->assertSee('app.novahorizon.com')
            ->assertSee('Primary Domain')
            ->assertSee('Active SSL');

        // -------------------------------------------------------------
        // STEP 5. Create pages and page sections
        // -------------------------------------------------------------
        $pageResponse = $this->actingAs($admin)->post(route('websites.pages.store', $website), [
            'title' => 'Home Overview',
            'slug' => 'home-overview',
            'page_type' => 'landing',
            'is_homepage' => '1',
            'status' => 1,
        ]);
        $page = Page::where('slug', 'home-overview')->where('website_id', $website->id)->firstOrFail();
        $pageResponse->assertRedirect(route('websites.pages.show', [$website, $page]));
        $this->assertTrue($page->is_homepage);

        // Add Section 1 (Hero)
        $this->actingAs($admin)->post(route('websites.pages.sections.store', [$website, $page]), [
            'section_name' => 'Hero Banner',
            'section_type' => 'hero',
            'heading' => 'Welcome to Nova Horizon',
            'subheading' => 'Enterprise Architecture at Scale',
            'body' => 'Delivering next-generation cloud infrastructure.',
            'button_text' => 'Get Started',
            'button_url' => '/signup',
            'status' => 1,
        ]);

        // Add Section 2 (Features)
        $this->actingAs($admin)->post(route('websites.pages.sections.store', [$website, $page]), [
            'section_name' => 'Core Capabilities',
            'section_type' => 'features',
            'heading' => 'Built for Scale',
            'status' => 1,
        ]);

        $this->assertEquals(2, $page->sections()->count());

        // Reload page details view
        $this->actingAs($admin)
            ->get(route('websites.pages.show', [$website, $page]))
            ->assertStatus(200)
            ->assertSee('Hero Banner')
            ->assertSee('Welcome to Nova Horizon')
            ->assertSee('Core Capabilities');

        // -------------------------------------------------------------
        // STEP 6. Upload media and associate it with the correct website
        // -------------------------------------------------------------
        $imageFile = UploadedFile::fake()->image('nova-hero.jpg', 800, 600);
        $mediaResponse = $this->actingAs($admin)->post(route('websites.media.store', $website), [
            'file' => $imageFile,
            'alt_text' => 'Nova Horizon Global Network',
        ]);
        $mediaResponse->assertRedirect(route('websites.media.index', $website));

        $media = $website->media()->firstOrFail();
        $this->assertEquals('image', $media->file_type);
        Storage::disk('public')->assertExists($media->file_path);

        // Reload media workspace page
        $this->actingAs($admin)
            ->get(route('websites.media.index', $website))
            ->assertStatus(200)
            ->assertSee($media->file_name)
            ->assertSee('Nova Horizon Global Network');

        // Safe stream endpoint serves file with correct MIME
        $this->actingAs($admin)
            ->get(route('media.file', $media))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/jpeg');

        // -------------------------------------------------------------
        // STEP 7. Save website settings and page metadata
        // -------------------------------------------------------------
        // Save Setting
        $this->actingAs($admin)->post(route('websites.settings.store', $website), [
            'setting_key' => 'support_email',
            'setting_value' => 'helpdesk@novahorizon.com',
        ]);

        // Save Website Default Metadata
        $this->actingAs($admin)->post(route('websites.seo.save_website', $website), [
            'meta_title' => 'Nova Horizon Digital Platform',
            'meta_description' => 'Scalable website fleet management for global teams.',
            'canonical_url' => 'https://app.novahorizon.com',
            'robots' => 'index, follow',
        ]);

        // Save Page-Specific Metadata
        $this->actingAs($admin)->post(route('websites.seo.save_page', [$website, $page]), [
            'meta_title' => 'Home Overview — Nova Horizon',
            'meta_description' => 'Official home of the Nova Horizon Digital Platform.',
            'canonical_url' => 'https://app.novahorizon.com/home-overview',
            'robots' => 'index, follow',
        ]);

        // Reload settings workspace
        $this->actingAs($admin)
            ->get(route('websites.settings.index', $website))
            ->assertStatus(200)
            ->assertSee('support_email')
            ->assertSee('helpdesk@novahorizon.com');

        // Reload SEO workspace
        $this->actingAs($admin)
            ->get(route('websites.seo.index', ['website' => $website, 'page_id' => $page->id]))
            ->assertStatus(200)
            ->assertSee('Nova Horizon Digital Platform')
            ->assertSee('Home Overview — Nova Horizon');

        // -------------------------------------------------------------
        // STEP 8. Edit and delete records while preserving relationship integrity
        // -------------------------------------------------------------
        // Edit Customer
        $this->actingAs($admin)->put(route('customers.update', $customer), [
            'name' => 'Nova Horizon Global Holdings',
            'email' => 'contact@novahorizon.com',
        ]);
        $customer->refresh();
        $this->assertEquals('Nova Horizon Global Holdings', $customer->name);

        // Edit Website
        $this->actingAs($admin)->put(route('websites.update', $website), [
            'website_name' => 'Nova Horizon Global Portal',
            'domain' => 'app.novahorizon.com',
            'provider' => 'cloud',
        ]);
        $website->refresh();
        $this->assertEquals('Nova Horizon Global Portal', $website->website_name);

        // Delete Page Section (Hero)
        $heroSection = $page->sections()->where('section_name', 'Hero Banner')->firstOrFail();
        $this->actingAs($admin)->delete(route('websites.pages.sections.destroy', [$website, $page, $heroSection]));
        $this->assertDatabaseMissing('page_sections', ['id' => $heroSection->id]);
        $this->assertEquals(1, $page->sections()->count());

        // Delete Media Asset
        $mediaPath = $media->file_path;
        $this->actingAs($admin)->delete(route('websites.media.destroy', [$website, $media]));
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($mediaPath);

        // Delete Customer: verify website remains intact with customer_id set to null
        $this->actingAs($admin)->delete(route('customers.destroy', $customer));
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);

        $website->refresh();
        $this->assertNull($website->customer_id);
        $this->assertEquals('Nova Horizon Global Portal', $website->website_name);

        // Reload Website Show: works flawlessly even with null customer
        $this->actingAs($admin)
            ->get(route('websites.show', $website))
            ->assertStatus(200)
            ->assertSee('Nova Horizon Global Portal');

        // Delete Website: cascades related pages, domains, settings, metadata, theme settings
        $this->actingAs($admin)->delete(route('websites.destroy', $website));
        $this->assertDatabaseMissing('websites', ['id' => $website->id]);
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
        $this->assertDatabaseMissing('website_theme_settings', ['website_id' => $website->id]);
        $this->assertDatabaseMissing('settings', ['website_id' => $website->id]);
        $this->assertDatabaseMissing('metadata', ['website_id' => $website->id]);

        // Theme itself remains intact
        $this->assertDatabaseHas('themes', ['id' => $theme->id]);
    }
}
