<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that unauthenticated users cannot access any website CRUD routes.
     */
    public function test_unauthenticated_users_cannot_access_website_routes(): void
    {
        $website = Website::factory()->create();

        $this->get(route('websites.index'))->assertRedirect(route('login'));
        $this->get(route('websites.create'))->assertRedirect(route('login'));
        $this->post(route('websites.store'), [])->assertRedirect(route('login'));
        $this->get(route('websites.show', $website))->assertRedirect(route('login'));
        $this->get(route('websites.edit', $website))->assertRedirect(route('login'));
        $this->put(route('websites.update', $website), [])->assertRedirect(route('login'));
        $this->delete(route('websites.destroy', $website))->assertRedirect(route('login'));
    }

    /**
     * Test that authenticated administrators can view the website directory.
     */
    public function test_authenticated_admin_can_view_website_directory(): void
    {
        $user = User::factory()->create();
        $website1 = Website::factory()->create(['website_name' => 'Alpha Showcase', 'domain' => 'alpha.dev']);
        $website2 = Website::factory()->create(['website_name' => 'Beta Portal', 'domain' => 'beta.org']);

        $response = $this->actingAs($user)->get(route('websites.index'));

        $response->assertStatus(200);
        $response->assertSee('Alpha Showcase');
        $response->assertSee('alpha.dev');
        $response->assertSee('Beta Portal');
        $response->assertSee('beta.org');
    }

    /**
     * Test that authenticated administrators can access the website creation page.
     */
    public function test_authenticated_admin_can_view_create_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('websites.create'));

        $response->assertStatus(200);
        $response->assertSee('Add Website');
        $response->assertSee('Website Name');
        $response->assertSee('Primary Domain');
        $response->assertSee('Create Website');
    }

    /**
     * Test that a website can be successfully created with all fields.
     */
    public function test_authenticated_admin_can_create_website(): void
    {
        $user = User::factory()->create();

        $payload = [
            'website_name' => 'CloudSync Platform',
            'domain' => 'cloudsync.io',
            'description' => 'A premier cloud synchronization engine.',
            'notes' => 'Primary production cluster in Frankfurt.',
            'provider' => 'aws',
        ];

        $response = $this->actingAs($user)->post(route('websites.store'), $payload);

        $response->assertRedirect(route('websites.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('websites', [
            'website_name' => 'CloudSync Platform',
            'domain' => 'cloudsync.io',
            'provider' => 'aws',
        ]);
    }

    /**
     * Test that creating a website validates required fields.
     */
    public function test_website_creation_requires_mandatory_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('websites.store'), [
            'website_name' => '',
            'domain' => '',
            'provider' => '',
        ]);

        $response->assertSessionHasErrors(['website_name', 'domain', 'provider']);
        $this->assertEquals(0, Website::count());
    }

    /**
     * Test that optional fields can be omitted.
     */
    public function test_website_creation_succeeds_without_optional_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('websites.store'), [
            'website_name' => 'Minimal Site',
            'domain' => 'minimal.dev',
            'provider' => 'cloud',
            'description' => null,
            'notes' => null,
        ]);

        $response->assertRedirect(route('websites.index'));
        $this->assertDatabaseHas('websites', [
            'website_name' => 'Minimal Site',
            'domain' => 'minimal.dev',
        ]);
    }

    /**
     * Test that duplicate domains are rejected.
     */
    public function test_website_creation_rejects_duplicate_domain(): void
    {
        $user = User::factory()->create();
        Website::factory()->create(['domain' => 'taken.io']);

        $response = $this->actingAs($user)->post(route('websites.store'), [
            'website_name' => 'New Site',
            'domain' => 'taken.io',
            'provider' => 'cloud',
        ]);

        $response->assertSessionHasErrors(['domain']);
        $this->assertEquals(1, Website::count());
    }

    /**
     * Test that viewing a website detail page shows all information.
     */
    public function test_authenticated_admin_can_view_website_details(): void
    {
        $user = User::factory()->create();
        $website = Website::factory()->create([
            'website_name' => 'Venture Deck Showcase',
            'domain' => 'venturedeck.co',
            'description' => 'Detailed venture showcase documentation.',
            'notes' => 'Domain renewed until 2029.',
            'provider' => 'cloud',
        ]);

        $response = $this->actingAs($user)->get(route('websites.show', $website));

        $response->assertStatus(200);
        $response->assertSee('Venture Deck Showcase');
        $response->assertSee('venturedeck.co');
        $response->assertSee('Detailed venture showcase documentation.');
        $response->assertSee('Domain renewed until 2029.');
    }

    /**
     * Test that viewing a non-existent website returns a 404 response.
     */
    public function test_viewing_nonexistent_website_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/websites/99999');

        $response->assertStatus(404);
    }

    /**
     * Test that the edit form displays current website values.
     */
    public function test_authenticated_admin_can_view_edit_form(): void
    {
        $user = User::factory()->create();
        $website = Website::factory()->create([
            'website_name' => 'Original Name',
            'domain' => 'original.com',
        ]);

        $response = $this->actingAs($user)->get(route('websites.edit', $website));

        $response->assertStatus(200);
        $response->assertSee('Original Name');
        $response->assertSee('original.com');
        $response->assertSee('Save Changes');
    }

    /**
     * Test that an administrator can update an existing website.
     */
    public function test_authenticated_admin_can_update_website(): void
    {
        $user = User::factory()->create();
        $website = Website::factory()->create([
            'website_name' => 'Old Branding',
            'domain' => 'olddomain.com',
        ]);

        $updatedData = [
            'website_name' => 'New Branding Pro',
            'domain' => 'newdomain.com',
            'description' => 'Updated store description.',
            'notes' => 'Migrated server to us-east.',
            'provider' => 'aws',
        ];

        $response = $this->actingAs($user)->put(route('websites.update', $website), $updatedData);

        $response->assertRedirect(route('websites.show', $website));
        $response->assertSessionHas('success');

        $website->refresh();
        $this->assertEquals('New Branding Pro', $website->website_name);
        $this->assertEquals('newdomain.com', $website->domain);
        $this->assertEquals('Updated store description.', $website->description);
    }

    /**
     * Test that updating a website enforces validation rules.
     */
    public function test_updating_website_requires_valid_data(): void
    {
        $user = User::factory()->create();
        $website = Website::factory()->create([
            'website_name' => 'Valid Name',
        ]);

        $response = $this->actingAs($user)->put(route('websites.update', $website), [
            'website_name' => '',
            'domain' => '',
            'provider' => '',
        ]);

        $response->assertSessionHasErrors(['website_name', 'domain', 'provider']);
        $website->refresh();
        $this->assertEquals('Valid Name', $website->website_name);
    }

    /**
     * Test that an administrator can delete a website.
     */
    public function test_authenticated_admin_can_delete_website(): void
    {
        $user = User::factory()->create();
        $website = Website::factory()->create([
            'website_name' => 'Obsolete Site',
            'domain' => 'obsolete.dev',
        ]);

        $this->assertDatabaseHas('websites', ['id' => $website->id]);

        $response = $this->actingAs($user)->delete(route('websites.destroy', $website));

        $response->assertRedirect(route('websites.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('websites', ['id' => $website->id]);
    }

    /**
     * Test that attempting to update or delete non-existent website returns 404.
     */
    public function test_updating_or_deleting_nonexistent_website_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/websites/99999', [
            'website_name' => 'Ghost',
            'domain' => 'ghost.io',
            'provider' => 'cloud',
        ])->assertStatus(404);

        $this->actingAs($user)->delete('/websites/99999')->assertStatus(404);
    }

    /**
     * Test that unauthenticated JSON requests receive 401 unauthorized.
     */
    public function test_unauthenticated_json_requests_receive_unauthorized(): void
    {
        $website = Website::factory()->create();

        $this->postJson(route('websites.store'), [])->assertStatus(401);
        $this->putJson(route('websites.update', $website), [])->assertStatus(401);
        $this->deleteJson(route('websites.destroy', $website))->assertStatus(401);
    }

    /**
     * Test that user input containing HTML or script tags is safely escaped in rendered views.
     */
    public function test_user_content_is_escaped_when_rendered(): void
    {
        $user = User::factory()->create();
        $website = Website::factory()->create([
            'website_name' => '<script>alert("xss")</script>Secure Site',
            'description' => '<b>Bold Text</b> & "Quotes"',
            'notes' => '<img src=x onerror=alert(1)>',
        ]);

        $response = $this->actingAs($user)->get(route('websites.show', $website));

        $response->assertStatus(200);
        $response->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Secure Site', false);
        $response->assertDontSee('<script>alert("xss")</script>', false);
    }
}
