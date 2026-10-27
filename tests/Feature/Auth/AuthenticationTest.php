<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the login screen renders successfully with all required elements.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('CodeBaseHub');
        $response->assertSee('Administrator Login');
        $response->assertSee('Sign In');
        $response->assertSee('Email Address');
        $response->assertSee('Password');
        $response->assertSee('Protected area. Public registration is restricted.');
    }

    /**
     * Test that an administrator can authenticate with valid credentials.
     */
    public function test_administrator_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@codebasehub.local',
            'password' => Hash::make('securePassword123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@codebasehub.local',
            'password' => 'securePassword123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * Test that the session is regenerated upon successful login.
     */
    public function test_session_is_regenerated_upon_login(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@codebasehub.local',
            'password' => Hash::make('securePassword123'),
        ]);

        $this->get(route('login'));
        $initialSessionId = session()->getId();

        $this->post(route('login'), [
            'email' => 'admin@codebasehub.local',
            'password' => 'securePassword123',
        ]);

        $this->assertNotEquals($initialSessionId, session()->getId());
    }

    /**
     * Test that an administrator cannot authenticate with an invalid password.
     */
    public function test_administrator_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@codebasehub.local',
            'password' => Hash::make('correctPassword123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@codebasehub.local',
            'password' => 'wrongPassword999',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test that an administrator cannot authenticate with a nonexistent email.
     */
    public function test_administrator_cannot_authenticate_with_nonexistent_email(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'nonexistent@codebasehub.local',
            'password' => 'anyPassword123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test that validation errors occur when required fields are missing.
     */
    public function test_validation_errors_for_missing_fields(): void
    {
        $response = $this->post(route('login'), [
            'email' => '',
            'password' => '',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email', 'password']);
    }

    /**
     * Test that validation error occurs for an invalid email format.
     */
    public function test_validation_error_for_invalid_email_format(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'not-an-email-address',
            'password' => 'somePassword123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test that rapid invalid login attempts trigger rate limiting.
     */
    public function test_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'email' => 'ratelimit@codebasehub.local',
            'password' => Hash::make('password123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => 'ratelimit@codebasehub.local',
                'password' => 'invalid-password',
            ]);
        }

        $response = $this->post(route('login'), [
            'email' => 'ratelimit@codebasehub.local',
            'password' => 'invalid-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('Too many login attempts', $errors[0]);
    }

    /**
     * Test that an authenticated administrator can logout and session is invalidated.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that unauthenticated guests cannot access the dashboard route.
     */
    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that unauthenticated guests cannot access the websites management route.
     */
    public function test_unauthenticated_user_cannot_access_websites(): void
    {
        $response = $this->get(route('websites'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that unauthenticated guests cannot access root route.
     */
    public function test_unauthenticated_user_cannot_access_root(): void
    {
        $response = $this->get('/');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that authenticated users visiting login are redirected to dashboard.
     */
    public function test_authenticated_user_visiting_login_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('dashboard'));
    }

    /**
     * Test that make:admin console command creates an administrator with hashed password.
     */
    public function test_make_admin_command_creates_administrator_with_hashed_password(): void
    {
        $this->artisan('make:admin', [
            '--name' => 'CLI Admin',
            '--email' => 'cliadmin@codebasehub.local',
            '--password' => 'SuperSecretPass1!',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'cliadmin@codebasehub.local',
            'name' => 'CLI Admin',
        ]);

        $created = User::where('email', 'cliadmin@codebasehub.local')->first();
        $this->assertNotNull($created);
        $this->assertTrue(Hash::check('SuperSecretPass1!', $created->password));
    }

    /**
     * Test that make:admin console command updates an existing administrator account.
     */
    public function test_make_admin_command_updates_existing_administrator(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@codebasehub.local',
            'name' => 'Old Name',
            'password' => Hash::make('oldPassword123'),
        ]);

        $this->artisan('make:admin', [
            '--name' => 'Updated Name',
            '--email' => 'existing@codebasehub.local',
            '--password' => 'newPassword456',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertTrue(Hash::check('newPassword456', $user->password));
    }

    /**
     * Test that make:admin command rejects passwords shorter than 8 characters.
     */
    public function test_make_admin_command_rejects_short_password(): void
    {
        $this->artisan('make:admin', [
            '--name' => 'Short Pass Admin',
            '--email' => 'shortpass@codebasehub.local',
            '--password' => 'short',
            '--no-interaction' => true,
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'email' => 'shortpass@codebasehub.local',
        ]);
    }

    /**
     * Test that make:admin command rejects invalid email format.
     */
    public function test_make_admin_command_rejects_invalid_email(): void
    {
        $this->artisan('make:admin', [
            '--name' => 'Invalid Email Admin',
            '--email' => 'invalid-email-format',
            '--password' => 'validPassword123',
            '--no-interaction' => true,
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'name' => 'Invalid Email Admin',
        ]);
    }
}
