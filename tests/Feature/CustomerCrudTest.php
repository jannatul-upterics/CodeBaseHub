<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unauthenticated users cannot access customer routes.
     */
    public function test_unauthenticated_users_cannot_access_customer_routes(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('customers.index'))->assertRedirect(route('login'));
        $this->get(route('customers.create'))->assertRedirect(route('login'));
        $this->post(route('customers.store'), [])->assertRedirect(route('login'));
        $this->get(route('customers.show', $customer))->assertRedirect(route('login'));
        $this->get(route('customers.edit', $customer))->assertRedirect(route('login'));
        $this->put(route('customers.update', $customer), [])->assertRedirect(route('login'));
        $this->delete(route('customers.destroy', $customer))->assertRedirect(route('login'));
    }

    /**
     * Customer listing displays customers and supports search.
     */
    public function test_customer_listing_with_search_and_portfolio_filter(): void
    {
        $user = User::factory()->create();
        $c1 = Customer::factory()->create(['name' => 'Acme Corporation', 'email' => 'contact@acme.com']);
        $c2 = Customer::factory()->create(['name' => 'Globex Industries', 'email' => 'info@globex.org']);

        // Attach a website to c1
        Website::factory()->create(['customer_id' => $c1->id, 'website_name' => 'Acme Store', 'domain' => 'acmestore.com']);

        // Index
        $response = $this->actingAs($user)->get(route('customers.index'));
        $response->assertStatus(200);
        $response->assertSee('Acme Corporation');
        $response->assertSee('Globex Industries');

        // Search by name
        $searchResponse = $this->actingAs($user)->get(route('customers.index', ['search' => 'Acme']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Acme Corporation');
        $searchResponse->assertDontSee('Globex Industries');

        // Search by email
        $emailResponse = $this->actingAs($user)->get(route('customers.index', ['search' => 'globex.org']));
        $emailResponse->assertStatus(200);
        $emailResponse->assertSee('Globex Industries');
        $emailResponse->assertDontSee('Acme Corporation');

        // Filter by has_sites
        $hasSitesResponse = $this->actingAs($user)->get(route('customers.index', ['portfolio' => 'has_sites']));
        $hasSitesResponse->assertStatus(200);
        $hasSitesResponse->assertSee('Acme Corporation');
        $hasSitesResponse->assertDontSee('Globex Industries');

        // Filter by no_sites
        $noSitesResponse = $this->actingAs($user)->get(route('customers.index', ['portfolio' => 'no_sites']));
        $noSitesResponse->assertStatus(200);
        $noSitesResponse->assertSee('Globex Industries');
        $noSitesResponse->assertDontSee('Acme Corporation');
    }

    /**
     * Create customer form renders.
     */
    public function test_create_customer_form_renders(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('customers.create'));
        $response->assertStatus(200);
        $response->assertSee('Add New Customer');
        $response->assertSee('Customer or Company Name');
        $response->assertSee('Contact Email Address');
    }

    /**
     * Customer can be created with valid data.
     */
    public function test_can_create_customer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Initech LLC',
            'email' => 'hello@initech.com',
            'password' => 'secret12345',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Initech LLC',
            'email' => 'hello@initech.com',
        ]);
    }

    /**
     * Customer email uniqueness is strictly validated.
     */
    public function test_customer_email_uniqueness_validation(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['email' => 'existing@initech.com']);

        $response = $this->actingAs($user)
            ->from(route('customers.create'))
            ->post(route('customers.store'), [
                'name' => 'Duplicate Customer',
                'email' => 'existing@initech.com',
                'password' => 'secret12345',
            ]);

        $response->assertRedirect(route('customers.create'));
        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Customer detail page displays customer information and related websites.
     */
    public function test_customer_detail_page_displays_websites(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Umbrella Corp', 'email' => 'ops@umbrella.corp']);
        $website = Website::factory()->create([
            'customer_id' => $customer->id,
            'website_name' => 'Umbrella Portal',
            'domain' => 'umbrella.corp',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));
        $response->assertStatus(200);
        $response->assertSee('Umbrella Corp');
        $response->assertSee('ops@umbrella.corp');
        $response->assertSee('Umbrella Portal');
        $response->assertSee('umbrella.corp');
    }

    /**
     * Edit customer form renders.
     */
    public function test_edit_customer_form_renders(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Soylent Corp']);

        $response = $this->actingAs($user)->get(route('customers.edit', $customer));
        $response->assertStatus(200);
        $response->assertSee('Edit Customer Profile');
        $response->assertSee('Soylent Corp');
    }

    /**
     * Customer can be updated and respects email unique ignore on self.
     */
    public function test_can_update_customer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create([
            'name' => 'Old Name',
            'email' => 'same@email.com',
        ]);

        $response = $this->actingAs($user)->put(route('customers.update', $customer), [
            'name' => 'New Name',
            'email' => 'same@email.com', // Keeps same email, shouldn't fail uniqueness
        ]);

        $response->assertRedirect(route('customers.show', $customer));
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'New Name',
            'email' => 'same@email.com',
        ]);
    }

    /**
     * Safe customer deletion sets customer_id to null on related websites without deleting them.
     */
    public function test_safe_customer_deletion_preserves_websites(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Hooli Corp']);
        $website = Website::factory()->create([
            'customer_id' => $customer->id,
            'website_name' => 'Hooli Search',
            'domain' => 'hoolisearch.xyz',
        ]);

        $response = $this->actingAs($user)->delete(route('customers.destroy', $customer));
        $response->assertRedirect(route('customers.index'));

        // Customer record is deleted
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);

        // Website is preserved, but customer_id is set to null
        $this->assertDatabaseHas('websites', [
            'id' => $website->id,
            'website_name' => 'Hooli Search',
            'customer_id' => null,
        ]);
    }
}
