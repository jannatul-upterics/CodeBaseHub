<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unauthenticated users cannot access template routes.
     */
    public function test_unauthenticated_users_cannot_access_template_routes(): void
    {
        $template = Template::factory()->create();

        $this->get(route('templates.index'))->assertRedirect(route('login'));
        $this->get(route('templates.create'))->assertRedirect(route('login'));
        $this->post(route('templates.store'), [])->assertRedirect(route('login'));
        $this->get(route('templates.show', $template))->assertRedirect(route('login'));
        $this->get(route('templates.edit', $template))->assertRedirect(route('login'));
        $this->put(route('templates.update', $template), [])->assertRedirect(route('login'));
        $this->delete(route('templates.destroy', $template))->assertRedirect(route('login'));
    }

    /**
     * Template catalog listing with search and status filter.
     */
    public function test_template_catalog_with_search_and_status_filter(): void
    {
        $user = User::factory()->create();
        $t1 = Template::factory()->create([
            'template_name' => 'SaaS Landing High Conversion',
            'slug' => 'saas-landing-hc',
            'status' => 1,
        ]);
        $t2 = Template::factory()->create([
            'template_name' => 'Portfolio Grid Minimal',
            'slug' => 'portfolio-grid-min',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('templates.index'));
        $response->assertStatus(200);
        $response->assertSee('SaaS Landing High Conversion');
        $response->assertSee('Portfolio Grid Minimal');

        // Search
        $searchResponse = $this->actingAs($user)->get(route('templates.index', ['search' => 'Conversion']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('SaaS Landing High Conversion');
        $searchResponse->assertDontSee('Portfolio Grid Minimal');

        // Status filter
        $activeResponse = $this->actingAs($user)->get(route('templates.index', ['status' => '1']));
        $activeResponse->assertStatus(200);
        $activeResponse->assertSee('SaaS Landing High Conversion');
        $activeResponse->assertDontSee('Portfolio Grid Minimal');
    }

    /**
     * Create template form renders.
     */
    public function test_create_template_form_renders(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('templates.create'));
        $response->assertStatus(200);
        $response->assertSee('Create Template Blueprint');
        $response->assertSee('Template Name');
        $response->assertSee('Unique Slug');
    }

    /**
     * Can create template with real database fields.
     */
    public function test_can_create_template(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('templates.store'), [
            'template_name' => 'E-Commerce Marketplace Wireframe',
            'slug' => 'ecommerce-marketplace-wireframe',
            'description' => 'Multi-vendor product showcase blueprint with facet navigation.',
            'preview_image' => 'https://images.unsplash.com/wireframe.png',
            'version' => '1.2.0',
            'status' => 1,
        ]);

        $response->assertRedirect(route('templates.index'));
        $this->assertDatabaseHas('templates', [
            'template_name' => 'E-Commerce Marketplace Wireframe',
            'slug' => 'ecommerce-marketplace-wireframe',
            'version' => '1.2.0',
            'status' => 1,
        ]);
    }

    /**
     * Template slug uniqueness is enforced.
     */
    public function test_template_slug_uniqueness_validation(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['slug' => 'existing-template-slug']);

        $response = $this->actingAs($user)
            ->from(route('templates.create'))
            ->post(route('templates.store'), [
                'template_name' => 'Duplicate Slug Template',
                'slug' => 'existing-template-slug',
            ]);

        $response->assertRedirect(route('templates.create'));
        $response->assertSessionHasErrors(['slug']);
    }

    /**
     * Template detail view renders blueprint details.
     */
    public function test_template_detail_view_renders_blueprint(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create([
            'template_name' => 'Healthcare Clinic Portal',
            'slug' => 'healthcare-clinic-portal',
            'description' => 'Specialized appointment scheduling wireframe blueprint.',
            'version' => '3.0.0',
        ]);

        $response = $this->actingAs($user)->get(route('templates.show', $template));
        $response->assertStatus(200);
        $response->assertSee('Healthcare Clinic Portal');
        $response->assertSee('healthcare-clinic-portal');
        $response->assertSee('v3.0.0');
        $response->assertSee('appointment scheduling wireframe');
    }

    /**
     * Can update template.
     */
    public function test_can_update_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create([
            'template_name' => 'Old Template Name',
            'slug' => 'original-template-slug',
        ]);

        $response = $this->actingAs($user)->put(route('templates.update', $template), [
            'template_name' => 'Refined Template Name',
            'slug' => 'original-template-slug', // Same slug
            'version' => '2.0.0',
            'status' => 1,
        ]);

        $response->assertRedirect(route('templates.show', $template));
        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'template_name' => 'Refined Template Name',
            'version' => '2.0.0',
        ]);
    }

    /**
     * Can delete template from catalog.
     */
    public function test_can_delete_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['template_name' => 'Deprecated Template']);

        $response = $this->actingAs($user)->delete(route('templates.destroy', $template));
        $response->assertRedirect(route('templates.index'));
        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    }
}
