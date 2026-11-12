<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteThemeSetting;
use Database\Seeders\VocabotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VocabotThemeManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test VocabotSeeder links Vocabot website, domain, template, and theme settings without duplicates.
     */
    public function test_vocabot_seeder_creates_and_links_website_without_duplicates(): void
    {
        // First run
        $this->seed(VocabotSeeder::class);

        $this->assertDatabaseHas('websites', [
            'website_name' => 'Vocabot',
            'domain' => 'vocabots.com',
        ]);

        $website = Website::where('website_name', 'Vocabot')->first();
        $this->assertNotNull($website);
        $this->assertNotNull($website->template_id);
        $this->assertEquals('Vocabot AI Voice Agent', $website->template->template_name);
        $this->assertEquals('vocabots.com', $website->primaryDomain->domain_name);

        // Verify all 7 required theme settings are stored in database
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'primary_color',
            'setting_value' => '#7c3aed',
        ]);
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'accent_color',
            'setting_value' => '#a855f7',
        ]);
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'background_color',
            'setting_value' => '#070313',
        ]);
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'text_color',
            'setting_value' => '#ffffff',
        ]);
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'font_family',
            'setting_value' => 'Plus Jakarta Sans',
        ]);
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'logo',
            'setting_value' => 'images/logo2.webp',
        ]);
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'button_style',
            'setting_value' => 'pill',
        ]);

        // Second run to verify idempotency (no duplicate records created)
        $this->seed(VocabotSeeder::class);

        $this->assertEquals(1, Website::where('website_name', 'Vocabot')->count());
        $this->assertEquals(1, Template::where('slug', 'vocabot')->count());
        $this->assertEquals(1, Theme::where('slug', 'vocabot-dark')->count());
    }

    /**
     * Test website and template relationships.
     */
    public function test_website_and_template_relationships(): void
    {
        $template = Template::factory()->create([
            'template_name' => 'Custom SaaS Template',
            'slug' => 'custom-saas',
        ]);

        $website = Website::factory()->create([
            'template_id' => $template->id,
            'website_name' => 'Vocabot Preview',
        ]);

        $this->assertInstanceOf(Template::class, $website->template);
        $this->assertEquals($template->id, $website->template->id);
        $this->assertTrue($template->websites->contains($website));
        $this->assertEquals('Custom SaaS Template', $website->template_name);
    }

    /**
     * Test website theme settings helper methods.
     */
    public function test_website_theme_settings_helpers(): void
    {
        $website = Website::factory()->create();
        $theme = Theme::factory()->create(['theme_name' => 'Cyber Glow']);

        WebsiteThemeSetting::create([
            'website_id' => $website->id,
            'theme_id' => $theme->id,
            'setting_key' => 'active_theme',
            'setting_value' => 'active',
        ]);

        WebsiteThemeSetting::create([
            'website_id' => $website->id,
            'theme_id' => $theme->id,
            'setting_key' => 'primary_color',
            'setting_value' => '#6366f1',
        ]);

        $website->load('themeSettings.theme');

        $this->assertEquals('#6366f1', $website->getThemeSetting('primary_color'));
        $this->assertEquals('default_val', $website->getThemeSetting('non_existent', 'default_val'));
        $this->assertEquals($theme->id, $website->activeTheme()->id);

        $map = $website->getThemeSettingsMap();
        $this->assertArrayHasKey('primary_color', $map);
        $this->assertEquals('#6366f1', $map['primary_color']);
    }

    /**
     * Test updating theme settings through CodeBaseHub controller supports all Vocabot settings.
     */
    public function test_theme_settings_update_supports_all_vocabot_settings(): void
    {
        $user = User::factory()->create();
        $website = Website::factory()->create();
        $theme = Theme::factory()->create(['theme_name' => 'Dark Tech']);

        $payload = [
            'theme_id' => $theme->id,
            'primary_color' => '#8b5cf6',
            'accent_color' => '#ec4899',
            'background_color' => '#0f172a',
            'text_color' => '#f8fafc',
            'font_family' => 'Inter',
            'logo' => 'uploads/logos/custom-vocabot.png',
            'button_style' => 'rounded',
            'layout_mode' => 'fluid',
            'navbar_style' => 'sticky',
            'custom_css' => '.vocabot-hero { opacity: 0.95; }',
        ];

        $response = $this->actingAs($user)
            ->post(route('websites.theme.settings.update', $website), $payload);

        $response->assertRedirect(route('websites.theme.index', $website));
        $response->assertSessionHas('success');

        foreach ($payload as $key => $value) {
            if ($key === 'theme_id') {
                continue;
            }

            $this->assertDatabaseHas('website_theme_settings', [
                'website_id' => $website->id,
                'theme_id' => $theme->id,
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        }
    }

    /**
     * Test validation rules reject invalid colors or invalid button styling.
     */
    public function test_theme_settings_validation_rejects_invalid_inputs(): void
    {
        $user = User::factory()->create();
        $website = Website::factory()->create();
        $theme = Theme::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('websites.theme.settings.update', $website), [
                'theme_id' => $theme->id,
                'primary_color' => 'not-a-hex-color',
                'button_style' => 'invalid-style',
            ]);

        $response->assertSessionHasErrors(['primary_color', 'button_style']);
    }

    /**
     * Test Vocabot theme management workspace renders with loaded settings.
     */
    public function test_vocabot_theme_management_workspace_renders_with_loaded_settings(): void
    {
        $user = User::factory()->create();
        $this->seed(VocabotSeeder::class);

        $website = Website::where('website_name', 'Vocabot')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('websites.theme.index', $website));

        $response->assertOk();
        $response->assertSee('Vocabot Theme Management');
        $response->assertSee('#7c3aed');
        $response->assertSee('#a855f7');
        $response->assertSee('#070313');
        $response->assertSee('#ffffff');
        $response->assertSee('Plus Jakarta Sans');
        $response->assertSee('pill');
        $response->assertSee('Save Changes');
        $response->assertSee('Reset to Default');
    }

    /**
     * Test resetting Vocabot theme settings to default restores original configuration.
     */
    public function test_reset_to_default_restores_default_theme_settings(): void
    {
        $user = User::factory()->create();
        $this->seed(VocabotSeeder::class);

        $website = Website::where('website_name', 'Vocabot')->firstOrFail();
        $theme = $website->activeTheme();

        // Alter settings to custom values
        $this->actingAs($user)
            ->post(route('websites.theme.settings.update', $website), [
                'theme_id' => $theme->id,
                'primary_color' => '#10b981',
                'accent_color' => '#f59e0b',
                'background_color' => '#ffffff',
                'text_color' => '#111827',
                'font_family' => 'Roboto',
                'button_style' => 'square',
            ]);

        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'primary_color',
            'setting_value' => '#10b981',
        ]);

        // Submit Reset to default
        $resetResponse = $this->actingAs($user)
            ->post(route('websites.theme.settings.reset', $website), [
                'theme_id' => $theme->id,
            ]);

        $resetResponse->assertRedirect(route('websites.theme.index', $website));
        $resetResponse->assertSessionHas('success');

        // Assert restored to Vocabot defaults
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'primary_color',
            'setting_value' => '#7c3aed',
        ]);
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'accent_color',
            'setting_value' => '#a855f7',
        ]);
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'background_color',
            'setting_value' => '#070313',
        ]);
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'text_color',
            'setting_value' => '#ffffff',
        ]);
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'font_family',
            'setting_value' => 'Plus Jakarta Sans',
        ]);
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'button_style',
            'setting_value' => 'pill',
        ]);
    }

    /**
     * Test updating theme settings on Vocabot does not affect other websites.
     */
    public function test_theme_settings_changes_do_not_affect_other_websites(): void
    {
        $user = User::factory()->create();
        $websiteA = Website::factory()->create(['website_name' => 'Vocabot AI']);
        $websiteB = Website::factory()->create(['website_name' => 'Other Tenant Site']);
        $theme = Theme::factory()->create();

        // Set initial setting on Website B
        WebsiteThemeSetting::create([
            'website_id' => $websiteB->id,
            'theme_id' => $theme->id,
            'setting_key' => 'primary_color',
            'setting_value' => '#000000',
        ]);

        // Update settings on Website A
        $this->actingAs($user)
            ->post(route('websites.theme.settings.update', $websiteA), [
                'theme_id' => $theme->id,
                'primary_color' => '#7c3aed',
                'accent_color' => '#a855f7',
            ]);

        // Website A updated
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $websiteA->id,
            'setting_key' => 'primary_color',
            'setting_value' => '#7c3aed',
        ]);

        // Website B unchanged
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $websiteB->id,
            'setting_key' => 'primary_color',
            'setting_value' => '#000000',
        ]);
        $this->assertDatabaseMissing('website_theme_settings', [
            'website_id' => $websiteB->id,
            'setting_key' => 'accent_color',
        ]);
    }

    /**
     * Test website management show page displays clear Manage Theme option.
     */
    public function test_website_management_show_page_displays_manage_theme_option(): void
    {
        $user = User::factory()->create();
        $this->seed(VocabotSeeder::class);

        $website = Website::where('website_name', 'Vocabot')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('websites.show', $website));

        $response->assertOk();
        $response->assertSee('Manage Theme');
        $response->assertSee(route('websites.theme.index', $website));
    }

    /**
     * Test logo file upload updates logo setting and creates media record.
     */
    public function test_logo_file_upload_updates_logo_setting_and_creates_media(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $website = Website::factory()->create(['website_name' => 'Vocabot']);
        $theme = Theme::factory()->create();

        $uploadedFile = UploadedFile::fake()->image('custom_vocabot_logo.png', 200, 60);

        $response = $this->actingAs($user)
            ->post(route('websites.theme.settings.update', $website), [
                'theme_id' => $theme->id,
                'logo_file' => $uploadedFile,
            ]);

        $response->assertRedirect(route('websites.theme.index', $website));
        $response->assertSessionHas('success');

        $media = $website->media()->first();
        $this->assertNotNull($media);
        $this->assertStringContainsString('custom-vocabot-logo.png', $media->file_name);
        Storage::disk('public')->assertExists($media->file_path);

        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'setting_key' => 'logo',
            'setting_value' => 'storage/'.$media->file_path,
        ]);
    }
}
