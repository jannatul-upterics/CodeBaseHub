<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->website = Website::factory()->create(['website_name' => 'Acme Online', 'domain' => 'acmeonline.org']);
    }

    /**
     * 1. Pages Index: displays pages and statistics.
     */
    public function test_user_can_view_website_pages_index(): void
    {
        Page::factory()->create([
            'website_id' => $this->website->id,
            'title' => 'Landing Page Alpha',
            'slug' => 'landing-alpha',
            'is_homepage' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('websites.pages.index', $this->website));

        $response->assertStatus(200);
        $response->assertSee('Landing Page Alpha');
        $response->assertSee('/landing-alpha');
        $response->assertSee('Homepage');
    }

    /**
     * 2. Page Creation: stores new page and enforces homepage exclusivity.
     */
    public function test_user_can_create_page_and_homepage_is_exclusive(): void
    {
        $firstPage = Page::factory()->create([
            'website_id' => $this->website->id,
            'title' => 'Original Home',
            'slug' => 'original-home',
            'is_homepage' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('websites.pages.store', $this->website), [
            'title' => 'Brand New Home',
            'slug' => 'brand-new-home',
            'page_type' => 'landing',
            'is_homepage' => '1',
            'status' => 1,
        ]);

        $newPage = Page::where('slug', 'brand-new-home')->firstOrFail();
        $response->assertRedirect(route('websites.pages.show', [$this->website, $newPage]));

        $this->assertDatabaseHas('pages', [
            'id' => $newPage->id,
            'website_id' => $this->website->id,
            'is_homepage' => true,
        ]);

        // Previous homepage should now be false
        $firstPage->refresh();
        $this->assertFalse($firstPage->is_homepage);
    }

    /**
     * 3. Page Slug Validation: must be valid format and unique within the same website.
     */
    public function test_page_slug_validation(): void
    {
        Page::factory()->create([
            'website_id' => $this->website->id,
            'slug' => 'pricing',
        ]);

        // Duplicate slug on same website fails
        $response = $this->actingAs($this->user)->post(route('websites.pages.store', $this->website), [
            'title' => 'New Pricing',
            'slug' => 'pricing',
            'page_type' => 'standard',
        ]);

        $response->assertSessionHasErrors('slug');

        // Invalid slug characters fail
        $invalidResponse = $this->actingAs($this->user)->post(route('websites.pages.store', $this->website), [
            'title' => 'Bad Slug Page',
            'slug' => 'invalid slug with spaces!',
            'page_type' => 'standard',
        ]);

        $invalidResponse->assertSessionHasErrors('slug');
    }

    /**
     * 4. Page Sections: can create, edit, reorder, and delete sections.
     */
    public function test_page_section_lifecycle_and_reordering(): void
    {
        $page = Page::factory()->create(['website_id' => $this->website->id]);

        // Create Section 1
        $this->actingAs($this->user)->post(route('websites.pages.sections.store', [$this->website, $page]), [
            'section_name' => 'Hero Banner',
            'section_type' => 'hero',
            'heading' => 'Welcome to Acme',
            'subheading' => 'Leading innovation',
            'body' => 'High performance tech solutions.',
            'status' => 1,
        ]);

        // Create Section 2
        $this->actingAs($this->user)->post(route('websites.pages.sections.store', [$this->website, $page]), [
            'section_name' => 'Feature Grid',
            'section_type' => 'features',
            'heading' => 'Why Choose Us',
            'status' => 1,
        ]);

        $section1 = PageSection::where('section_name', 'Hero Banner')->firstOrFail();
        $section2 = PageSection::where('section_name', 'Feature Grid')->firstOrFail();

        $this->assertEquals(1, $section1->sort_order);
        $this->assertEquals(2, $section2->sort_order);

        // Reorder Section 2 up
        $this->actingAs($this->user)->post(route('websites.pages.sections.reorder', [$this->website, $page]), [
            'section_id' => $section2->id,
            'direction' => 'up',
        ]);

        $section1->refresh();
        $section2->refresh();
        $this->assertEquals(2, $section1->sort_order);
        $this->assertEquals(1, $section2->sort_order);

        // Update Section 1
        $this->actingAs($this->user)->put(route('websites.pages.sections.update', [$this->website, $page, $section1]), [
            'section_name' => 'Updated Hero Banner',
            'section_type' => 'hero',
            'heading' => 'Welcome to Acme Premier',
            'status' => 1,
        ]);

        $section1->refresh();
        $this->assertEquals('Updated Hero Banner', $section1->section_name);
        $this->assertEquals('Welcome to Acme Premier', $section1->content['heading']);

        // Delete Section 2
        $this->actingAs($this->user)->delete(route('websites.pages.sections.destroy', [$this->website, $page, $section2]));
        $this->assertDatabaseMissing('page_sections', ['id' => $section2->id]);
    }

    /**
     * 5. Theme Settings Workspace: switch active theme and save design overrides.
     */
    public function test_theme_workspace_switching_and_customization(): void
    {
        $theme1 = Theme::factory()->create(['theme_name' => 'Vanguard Dark']);
        $theme2 = Theme::factory()->create(['theme_name' => 'Minimal Light']);

        // Set active theme
        $this->actingAs($this->user)->post(route('websites.theme.set_active', $this->website), [
            'theme_id' => $theme2->id,
        ]);

        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $this->website->id,
            'theme_id' => $theme2->id,
            'setting_key' => 'active_theme',
            'setting_value' => 'active',
        ]);

        // Save custom theme overrides
        $this->actingAs($this->user)->post(route('websites.theme.settings.update', $this->website), [
            'theme_id' => $theme2->id,
            'primary_color' => '#1d4ed8',
            'accent_color' => '#10b981',
            'font_family' => 'Instrument Sans',
            'layout_mode' => 'boxed',
            'navbar_style' => 'sticky',
            'custom_css' => '.btn-custom { border-radius: 99px; }',
        ]);

        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $this->website->id,
            'theme_id' => $theme2->id,
            'setting_key' => 'primary_color',
            'setting_value' => '#1d4ed8',
        ]);

        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $this->website->id,
            'theme_id' => $theme2->id,
            'setting_key' => 'custom_css',
            'setting_value' => '.btn-custom { border-radius: 99px; }',
        ]);
    }

    /**
     * 6. Domain Management: registration, primary toggle, and verification.
     */
    public function test_domain_management_and_primary_switching(): void
    {
        // Add vanity domain
        $response = $this->actingAs($this->user)->post(route('websites.domains.store', $this->website), [
            'domain_name' => 'store.acmeonline.org',
            'is_primary' => '1',
            'status' => 1,
        ]);

        $response->assertRedirect(route('websites.domains.index', $this->website));

        $domain = Domain::where('domain_name', 'store.acmeonline.org')->firstOrFail();
        $this->assertTrue($domain->is_primary);

        // Website's primary domain property updated
        $this->website->refresh();
        $this->assertEquals('store.acmeonline.org', $this->website->domain);

        // Verify DNS and SSL with force_verify option
        $this->actingAs($this->user)->post(route('websites.domains.verify', [$this->website, $domain]), [
            'force_verify' => '1',
        ]);

        $domain->refresh();
        $this->assertNotNull($domain->verified_at);
        $this->assertTrue($domain->ssl_enabled);
    }

    /**
     * 7. Media Library: file upload, streaming, metadata update, and disk cleanup.
     */
    public function test_media_upload_stream_and_deletion(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('company-logo.png', 400, 400);

        // Upload
        $response = $this->actingAs($this->user)->post(route('websites.media.store', $this->website), [
            'file' => $file,
            'alt_text' => 'Official Company Emblem',
        ]);

        $response->assertRedirect(route('websites.media.index', $this->website));

        $media = $this->website->media()->firstOrFail();
        $this->assertEquals('image', $media->file_type);
        $this->assertEquals('Official Company Emblem', $media->alt_text);
        Storage::disk('public')->assertExists($media->file_path);

        // Safe streaming response
        $streamResponse = $this->actingAs($this->user)->get(route('media.file', $media));
        $streamResponse->assertStatus(200);
        $this->assertEquals('image/png', $streamResponse->headers->get('Content-Type'));

        // Update alt text
        $this->actingAs($this->user)->put(route('websites.media.update', [$this->website, $media]), [
            'file_name' => 'renamed-logo.png',
            'alt_text' => 'Updated Brand Logo',
            'status' => 1,
        ]);

        $media->refresh();
        $this->assertEquals('renamed-logo.png', $media->file_name);
        $this->assertEquals('Updated Brand Logo', $media->alt_text);

        // Delete media removes record and disk file
        $path = $media->file_path;
        $this->actingAs($this->user)->delete(route('websites.media.destroy', [$this->website, $media]));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * 8. Website Settings: key/value CRUD and unique key per website.
     */
    public function test_website_settings_key_value_crud(): void
    {
        // Add setting
        $response = $this->actingAs($this->user)->post(route('websites.settings.store', $this->website), [
            'setting_key' => 'contact_email',
            'setting_value' => 'support@acmeonline.org',
        ]);

        $response->assertRedirect(route('websites.settings.index', $this->website));

        $this->assertDatabaseHas('settings', [
            'website_id' => $this->website->id,
            'setting_key' => 'contact_email',
            'setting_value' => 'support@acmeonline.org',
        ]);

        // Duplicate key for same website rejected
        $dupResponse = $this->actingAs($this->user)->post(route('websites.settings.store', $this->website), [
            'setting_key' => 'contact_email',
            'setting_value' => 'alternate@acmeonline.org',
        ]);
        $dupResponse->assertSessionHasErrors('setting_key');

        // Update value
        $setting = Setting::where('website_id', $this->website->id)->firstOrFail();
        $this->actingAs($this->user)->put(route('websites.settings.update', [$this->website, $setting]), [
            'setting_value' => 'helpdesk@acmeonline.org',
        ]);

        $setting->refresh();
        $this->assertEquals('helpdesk@acmeonline.org', $setting->setting_value);

        // Delete setting
        $this->actingAs($this->user)->delete(route('websites.settings.destroy', [$this->website, $setting]));
        $this->assertDatabaseMissing('settings', ['id' => $setting->id]);
    }

    /**
     * 9. SEO & Metadata: website defaults and page-specific overrides.
     */
    public function test_seo_metadata_defaults_and_page_overrides(): void
    {
        $page = Page::factory()->create(['website_id' => $this->website->id, 'title' => 'About Team']);

        // Save website-level default SEO
        $this->actingAs($this->user)->post(route('websites.seo.save_website', $this->website), [
            'meta_title' => 'Acme Inc Global Portal',
            'meta_description' => 'Global leader in enterprise cloud infrastructure.',
            'canonical_url' => 'https://acmeonline.org',
            'robots' => 'index, follow',
        ]);

        $this->assertDatabaseHas('metadata', [
            'website_id' => $this->website->id,
            'page_id' => null,
            'meta_title' => 'Acme Inc Global Portal',
        ]);

        // Save page-specific override
        $this->actingAs($this->user)->post(route('websites.seo.save_page', [$this->website, $page]), [
            'meta_title' => 'Meet Our Engineering Team — Acme Inc',
            'meta_description' => 'Discover the talented engineers behind Acme.',
            'canonical_url' => 'https://acmeonline.org/about-team',
            'robots' => 'index, follow',
        ]);

        $this->assertDatabaseHas('metadata', [
            'website_id' => $this->website->id,
            'page_id' => $page->id,
            'meta_title' => 'Meet Our Engineering Team — Acme Inc',
        ]);

        // Reset page-specific metadata falls back cleanly
        $pageMeta = $page->metadata()->firstOrFail();
        $this->actingAs($this->user)->delete(route('websites.seo.destroy', [$this->website, $pageMeta]));

        $this->assertDatabaseMissing('metadata', ['id' => $pageMeta->id]);
        // Website-level meta remains intact
        $this->assertDatabaseHas('metadata', ['website_id' => $this->website->id, 'page_id' => null]);
    }
}
