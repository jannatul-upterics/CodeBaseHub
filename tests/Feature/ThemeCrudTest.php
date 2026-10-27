<?php

namespace Tests\Feature;

use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteThemeSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unauthenticated users cannot access theme routes.
     */
    public function test_unauthenticated_users_cannot_access_theme_routes(): void
    {
        $theme = Theme::factory()->create();

        $this->get(route('themes.index'))->assertRedirect(route('login'));
        $this->get(route('themes.create'))->assertRedirect(route('login'));
        $this->post(route('themes.store'), [])->assertRedirect(route('login'));
        $this->get(route('themes.show', $theme))->assertRedirect(route('login'));
        $this->get(route('themes.edit', $theme))->assertRedirect(route('login'));
        $this->put(route('themes.update', $theme), [])->assertRedirect(route('login'));
        $this->delete(route('themes.destroy', $theme))->assertRedirect(route('login'));
    }

    /**
     * Theme listing renders themes with preview images and supports search.
     */
    public function test_theme_listing_with_search_and_status_filter(): void
    {
        $user = User::factory()->create();
        $t1 = Theme::factory()->create([
            'theme_name' => 'Minimalist Slate',
            'slug' => 'minimalist-slate',
            'preview_image' => 'https://example.com/slate.png',
            'status' => 1,
        ]);
        $t2 = Theme::factory()->create([
            'theme_name' => 'Vibrant Neon',
            'slug' => 'vibrant-neon',
            'preview_image' => null,
            'status' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('themes.index'));
        $response->assertStatus(200);
        $response->assertSee('Minimalist Slate');
        $response->assertSee('https://example.com/slate.png');
        $response->assertSee('Vibrant Neon');

        // Search
        $searchResponse = $this->actingAs($user)->get(route('themes.index', ['search' => 'Slate']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Minimalist Slate');
        $searchResponse->assertDontSee('Vibrant Neon');

        // Status filter
        $activeResponse = $this->actingAs($user)->get(route('themes.index', ['status' => '1']));
        $activeResponse->assertStatus(200);
        $activeResponse->assertSee('Minimalist Slate');
        $activeResponse->assertDontSee('Vibrant Neon');
    }

    /**
     * Create theme form renders.
     */
    public function test_create_theme_form_renders(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('themes.create'));
        $response->assertStatus(200);
        $response->assertSee('Create New Theme');
        $response->assertSee('Theme Name');
        $response->assertSee('Unique Slug');
    }

    /**
     * Can create theme with real database fields.
     */
    public function test_can_create_theme(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('themes.store'), [
            'theme_name' => 'Corporate Azure',
            'slug' => 'corporate-azure',
            'description' => 'Crisp professional theme for financial and enterprise firms.',
            'preview_image' => 'https://images.unsplash.com/photo-azure.jpg',
            'version' => '2.1.0',
            'status' => 1,
        ]);

        $response->assertRedirect(route('themes.index'));
        $this->assertDatabaseHas('themes', [
            'theme_name' => 'Corporate Azure',
            'slug' => 'corporate-azure',
            'version' => '2.1.0',
            'status' => 1,
        ]);
    }

    /**
     * Theme slug must be unique.
     */
    public function test_theme_slug_uniqueness_validation(): void
    {
        $user = User::factory()->create();
        Theme::factory()->create(['slug' => 'existing-slug']);

        $response = $this->actingAs($user)
            ->from(route('themes.create'))
            ->post(route('themes.store'), [
                'theme_name' => 'Duplicate Slug Theme',
                'slug' => 'existing-slug',
            ]);

        $response->assertRedirect(route('themes.create'));
        $response->assertSessionHasErrors(['slug']);
    }

    /**
     * Theme detail page displays theme information and assigned websites.
     */
    public function test_theme_detail_page_displays_assigned_websites(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->create(['theme_name' => 'Nordic Frost', 'slug' => 'nordic-frost']);
        $website = Website::factory()->create(['website_name' => 'Nordic Store', 'domain' => 'nordic.no']);

        // Attach theme via website_theme_settings
        WebsiteThemeSetting::create([
            'website_id' => $website->id,
            'theme_id' => $theme->id,
            'setting_key' => 'active_theme',
            'setting_value' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('themes.show', $theme));
        $response->assertStatus(200);
        $response->assertSee('Nordic Frost');
        $response->assertSee('nordic-frost');
        $response->assertSee('Nordic Store');
        $response->assertSee('nordic.no');
    }

    /**
     * Can update theme.
     */
    public function test_can_update_theme(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->create([
            'theme_name' => 'Old Theme Name',
            'slug' => 'old-theme-slug',
            'version' => '1.0.0',
        ]);

        $response = $this->actingAs($user)->put(route('themes.update', $theme), [
            'theme_name' => 'Updated Theme Name',
            'slug' => 'old-theme-slug', // same slug
            'version' => '1.1.0',
            'status' => 1,
        ]);

        $response->assertRedirect(route('themes.show', $theme));
        $this->assertDatabaseHas('themes', [
            'id' => $theme->id,
            'theme_name' => 'Updated Theme Name',
            'version' => '1.1.0',
        ]);
    }

    /**
     * Theme assignment to website creates/updates website_theme_settings.
     */
    public function test_theme_assignment_to_website(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->create(['theme_name' => 'Emerald Coast']);
        $website = Website::factory()->create();

        $response = $this->actingAs($user)->post(route('websites.theme', $website), [
            'theme_id' => $theme->id,
            'setting_key' => 'active_theme',
            'setting_value' => 'active',
        ]);

        $response->assertRedirect(route('websites.show', $website));
        $this->assertDatabaseHas('website_theme_settings', [
            'website_id' => $website->id,
            'theme_id' => $theme->id,
            'setting_key' => 'active_theme',
            'setting_value' => 'active',
        ]);
    }

    /**
     * Deleting a theme assigned to websites is blocked to protect foreign-key integrity.
     */
    public function test_deleting_theme_assigned_to_websites_is_blocked(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->create(['theme_name' => 'Protected Theme']);
        $website = Website::factory()->create();

        WebsiteThemeSetting::create([
            'website_id' => $website->id,
            'theme_id' => $theme->id,
            'setting_key' => 'active_theme',
            'setting_value' => 'active',
        ]);

        $response = $this->actingAs($user)->delete(route('themes.destroy', $theme));
        $response->assertRedirect(route('themes.index'));
        $response->assertSessionHas('error');

        // Theme record must NOT be deleted
        $this->assertDatabaseHas('themes', ['id' => $theme->id]);
    }

    /**
     * Deleting an unassigned theme succeeds.
     */
    public function test_deleting_unassigned_theme_succeeds(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->create(['theme_name' => 'Unused Theme']);

        $response = $this->actingAs($user)->delete(route('themes.destroy', $theme));
        $response->assertRedirect(route('themes.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('themes', ['id' => $theme->id]);
    }
}
