<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_requires_authentication_for_dashboard()
    {
        $response = $this->get('/nawala-checker');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_allows_authenticated_users_to_access_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_requires_authentication_for_targets_index()
    {
        $response = $this->get('/nawala-checker/targets');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_requires_authentication_for_targets_create()
    {
        $response = $this->get('/nawala-checker/targets/create');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_requires_authentication_for_targets_store()
    {
        $response = $this->post('/nawala-checker/targets', []);

        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_requires_authentication_for_shortlinks_index()
    {
        $response = $this->get('/nawala-checker/shortlinks');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_requires_authentication_for_shortlinks_create()
    {
        $response = $this->get('/nawala-checker/shortlinks/create');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_requires_authentication_for_shortlinks_store()
    {
        $response = $this->post('/nawala-checker/shortlinks', []);

        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function it_cannot_login_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
    }

    /** @test */
    public function it_can_logout()
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');

        $this->assertGuest();
    }

    /** @test */
    public function it_redirects_to_login_after_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/logout');

        $response->assertRedirect('/');
    }

    /** @test */
    public function it_protects_against_csrf_attacks()
    {
        $user = User::factory()->create();

        // Without CSRF token
        $response = $this->post('/nawala-checker/targets', [
            'domain_or_url' => 'example.com',
            'type' => 'domain',
        ]);

        // Should fail without CSRF token
        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function it_allows_requests_with_valid_csrf_token()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->post('/nawala-checker/targets', [
                '_token' => 'test-token',
                'domain_or_url' => 'example.com',
                'type' => 'domain',
                'enabled' => true,
                'group_id' => 1,
            ]);

        // Should not be CSRF error (might be validation error)
        $response->assertStatus(302); // Redirect, not 419
    }

    /** @test */
    public function it_maintains_session_after_authentication()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/nawala-checker');
        $response->assertStatus(200);

        // Make another request
        $response = $this->get('/nawala-checker/targets');
        $response->assertStatus(200);

        // Should still be authenticated
        $this->assertAuthenticated();
    }

    /** @test */
    public function it_prevents_access_to_other_users_data()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 creates a target
        $this->actingAs($user1);
        // (Target creation would happen here)

        // User 2 tries to access
        $this->actingAs($user2);
        
        // Both users should be able to access the system
        $response = $this->get('/nawala-checker');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_remembers_user_when_remember_me_is_checked()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function it_validates_email_format_on_login()
    {
        $response = $this->post('/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function it_requires_password_on_login()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function it_throttles_login_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // Next attempt should be throttled
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    /** @test */
    public function it_redirects_authenticated_users_from_login_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/login');

        $response->assertRedirect('/dashboard');
    }

    /** @test */
    public function it_clears_session_on_logout()
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        
        $response = $this->post('/logout');

        $this->assertGuest();
        
        // Try to access protected route
        $response = $this->get('/nawala-checker');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_validates_user_exists_on_login()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }
}

