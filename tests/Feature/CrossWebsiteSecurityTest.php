<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Media;
use App\Models\Metadata;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Setting;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrossWebsiteSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Website $websiteA;

    protected Website $websiteB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->websiteA = Website::factory()->create(['website_name' => 'Site Alpha', 'domain' => 'site-alpha.com']);
        $this->websiteB = Website::factory()->create(['website_name' => 'Site Beta', 'domain' => 'site-beta.com']);
    }

    /**
     * Unauthenticated requests are blocked and redirected to login.
     */
    public function test_unauthenticated_requests_are_redirected_to_login(): void
    {
        $page = Page::factory()->create(['website_id' => $this->websiteA->id]);

        $this->get(route('websites.pages.index', $this->websiteA))->assertRedirect(route('login'));
        $this->get(route('websites.pages.show', [$this->websiteA, $page]))->assertRedirect(route('login'));
        $this->get(route('websites.domains.index', $this->websiteA))->assertRedirect(route('login'));
        $this->get(route('websites.theme.index', $this->websiteA))->assertRedirect(route('login'));
        $this->get(route('websites.media.index', $this->websiteA))->assertRedirect(route('login'));
        $this->get(route('websites.settings.index', $this->websiteA))->assertRedirect(route('login'));
        $this->get(route('websites.seo.index', $this->websiteA))->assertRedirect(route('login'));
    }

    /**
     * Cross-website page isolation: Website A route cannot access Website B page.
     */
    public function test_cross_website_page_access_is_forbidden(): void
    {
        $pageB = Page::factory()->create(['website_id' => $this->websiteB->id]);

        // Accessing Page B through Website A URL returns 404
        $this->actingAs($this->user)
            ->get(route('websites.pages.show', [$this->websiteA, $pageB]))
            ->assertStatus(404);

        $this->actingAs($this->user)
            ->get(route('websites.pages.edit', [$this->websiteA, $pageB]))
            ->assertStatus(404);

        $this->actingAs($this->user)
            ->put(route('websites.pages.update', [$this->websiteA, $pageB]), [
                'title' => 'Malicious Hijack',
                'slug' => 'hijack',
                'page_type' => 'standard',
            ])
            ->assertStatus(404);

        $this->actingAs($this->user)
            ->delete(route('websites.pages.destroy', [$this->websiteA, $pageB]))
            ->assertStatus(404);

        // Page B remains unaffected
        $this->assertDatabaseHas('pages', ['id' => $pageB->id, 'website_id' => $this->websiteB->id]);
    }

    /**
     * Cross-website page section isolation: Section belonging to Website B cannot be modified via Website A.
     */
    public function test_cross_website_page_section_isolation(): void
    {
        $pageB = Page::factory()->create(['website_id' => $this->websiteB->id]);
        $sectionB = PageSection::factory()->create(['page_id' => $pageB->id, 'section_name' => 'Beta Hero']);

        // Attempting to modify Section B via Website A
        $this->actingAs($this->user)
            ->put(route('websites.pages.sections.update', [$this->websiteA, $pageB, $sectionB]), [
                'section_name' => 'Compromised Name',
                'section_type' => 'hero',
            ])
            ->assertStatus(404);

        // Attempting to delete Section B via Website A
        $this->actingAs($this->user)
            ->delete(route('websites.pages.sections.destroy', [$this->websiteA, $pageB, $sectionB]))
            ->assertStatus(404);

        $this->assertDatabaseHas('page_sections', ['id' => $sectionB->id, 'section_name' => 'Beta Hero']);
    }

    /**
     * Cross-website domain isolation: Website A cannot alter or delete Website B domains.
     */
    public function test_cross_website_domain_isolation(): void
    {
        $domainB = Domain::factory()->create([
            'website_id' => $this->websiteB->id,
            'domain_name' => 'beta.corp',
            'is_primary' => false,
        ]);

        // Attempt to make primary under Website A
        $this->actingAs($this->user)
            ->post(route('websites.domains.primary', [$this->websiteA, $domainB]))
            ->assertStatus(404);

        // Attempt to delete under Website A
        $this->actingAs($this->user)
            ->delete(route('websites.domains.destroy', [$this->websiteA, $domainB]))
            ->assertStatus(404);

        $this->assertDatabaseHas('domains', ['id' => $domainB->id, 'website_id' => $this->websiteB->id]);
    }

    /**
     * Cross-website media isolation: Website A cannot update or delete Website B media.
     */
    public function test_cross_website_media_isolation(): void
    {
        $mediaB = Media::factory()->create([
            'website_id' => $this->websiteB->id,
            'file_name' => 'secret-contract.pdf',
        ]);

        $this->actingAs($this->user)
            ->put(route('websites.media.update', [$this->websiteA, $mediaB]), [
                'file_name' => 'tampered.pdf',
            ])
            ->assertStatus(404);

        $this->actingAs($this->user)
            ->delete(route('websites.media.destroy', [$this->websiteA, $mediaB]))
            ->assertStatus(404);

        $this->assertDatabaseHas('media', ['id' => $mediaB->id, 'website_id' => $this->websiteB->id]);
    }

    /**
     * Cross-website settings and metadata isolation.
     */
    public function test_cross_website_settings_and_metadata_isolation(): void
    {
        $settingB = Setting::factory()->create([
            'website_id' => $this->websiteB->id,
            'setting_key' => 'api_secret_key',
            'setting_value' => 'supersecret123',
        ]);

        $metaB = Metadata::factory()->create([
            'website_id' => $this->websiteB->id,
            'meta_title' => 'Secret Beta Portal',
        ]);

        // Attempt to update setting of Website B under Website A
        $this->actingAs($this->user)
            ->put(route('websites.settings.update', [$this->websiteA, $settingB]), [
                'setting_value' => 'hacked',
            ])
            ->assertStatus(404);

        // Attempt to delete setting of Website B under Website A
        $this->actingAs($this->user)
            ->delete(route('websites.settings.destroy', [$this->websiteA, $settingB]))
            ->assertStatus(404);

        // Attempt to delete metadata of Website B under Website A
        $this->actingAs($this->user)
            ->delete(route('websites.seo.destroy', [$this->websiteA, $metaB]))
            ->assertStatus(404);

        $this->assertDatabaseHas('settings', ['id' => $settingB->id, 'setting_value' => 'supersecret123']);
        $this->assertDatabaseHas('metadata', ['id' => $metaB->id, 'meta_title' => 'Secret Beta Portal']);
    }

    /**
     * File upload validation rejects dangerous and oversize files.
     */
    public function test_file_upload_validation_security(): void
    {
        Storage::fake('public');

        // Oversized file (>10MB)
        $oversizeFile = UploadedFile::fake()->create('massive.zip', 15000); // 15MB
        $response = $this->actingAs($this->user)->post(route('websites.media.store', $this->websiteA), [
            'file' => $oversizeFile,
        ]);
        $response->assertSessionHasErrors('file');

        // Dangerous PHP executable file
        $phpFile = UploadedFile::fake()->create('malicious.php', 10, 'application/x-php');
        $phpResponse = $this->actingAs($this->user)->post(route('websites.media.store', $this->websiteA), [
            'file' => $phpFile,
        ]);
        $phpResponse->assertSessionHasErrors('file');
    }

    /**
     * Alt text and section headings strip raw script tags to prevent XSS.
     */
    public function test_xss_content_sanitization(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('banner.jpg');
        $this->actingAs($this->user)->post(route('websites.media.store', $this->websiteA), [
            'file' => $file,
            'alt_text' => '<script>alert("xss")</script>Clean Logo',
        ]);

        $media = $this->websiteA->media()->firstOrFail();
        $this->assertStringNotContainsString('<script>', $media->alt_text);
        $this->assertStringNotContainsString('</script>', $media->alt_text);

        // Page section heading sanitization
        $page = Page::factory()->create(['website_id' => $this->websiteA->id]);
        $this->actingAs($this->user)->post(route('websites.pages.sections.store', [$this->websiteA, $page]), [
            'section_name' => 'Test Section',
            'section_type' => 'hero',
            'heading' => '<b>Bold Title</b>',
        ]);

        $section = $page->sections()->firstOrFail();
        $this->assertStringNotContainsString('<b>', $section->content['heading']);
        $this->assertEquals('Bold Title', $section->content['heading']);
    }
}
