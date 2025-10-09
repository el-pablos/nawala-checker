<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'is_admin' => false,
        ]);
    }

    /** @test */
    public function admin_can_access_admin_panel()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard');

        $response->assertOk();
    }

    /** @test */
    public function regular_user_cannot_access_admin_panel()
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_admin_panel()
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function admin_can_view_all_users()
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get('/admin/users');

        $response->assertOk();
        $response->assertViewHas('users');
    }

    /** @test */
    public function admin_can_create_new_user()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'domain_limit' => 10,
            'expires_at' => now()->addMonths(1)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->admin)
            ->post('/admin/users', $userData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'domain_limit' => 10,
        ]);
    }

    /** @test */
    public function admin_can_update_user_domain_limit()
    {
        $user = User::factory()->create([
            'domain_limit' => 5,
        ]);

        $response = $this->actingAs($this->admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'domain_limit' => 20,
            ]);

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertEquals(20, $user->domain_limit);
    }

    /** @test */
    public function admin_can_update_user_expiry_date()
    {
        $user = User::factory()->create([
            'expires_at' => now()->addDays(7),
        ]);

        $newExpiryDate = now()->addMonths(3);

        $response = $this->actingAs($this->admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'expires_at' => $newExpiryDate->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertEquals($newExpiryDate->format('Y-m-d'), $user->expires_at->format('Y-m-d'));
    }

    /** @test */
    public function admin_can_delete_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete("/admin/users/{$user->id}");

        $response->assertRedirect();
        
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /** @test */
    public function regular_user_cannot_create_users()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->regularUser)
            ->post('/admin/users', $userData);

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_cannot_update_users()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->put("/admin/users/{$user->id}", [
                'name' => 'Updated Name',
                'email' => $user->email,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_cannot_delete_users()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->delete("/admin/users/{$user->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_enforces_domain_limit_when_creating_target()
    {
        $user = User::factory()->create([
            'domain_limit' => 2,
        ]);

        $group = Group::factory()->create();

        // Create 2 targets (at limit)
        Target::factory()->count(2)->create([
            'owner_id' => $user->id,
            'group_id' => $group->id,
        ]);

        // Try to create 3rd target (should fail)
        $response = $this->actingAs($user)
            ->post('/nawala-checker/targets', [
                'domain_or_url' => 'example.com',
                'type' => 'domain',
                'group_id' => $group->id,
                'enabled' => true,
            ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function it_allows_creating_target_within_domain_limit()
    {
        $user = User::factory()->create([
            'domain_limit' => 5,
        ]);

        $group = Group::factory()->create();

        // Create 2 targets (under limit)
        Target::factory()->count(2)->create([
            'owner_id' => $user->id,
            'group_id' => $group->id,
        ]);

        // Create 3rd target (should succeed)
        $response = $this->actingAs($user)
            ->post('/nawala-checker/targets', [
                'domain_or_url' => 'example.com',
                'type' => 'domain',
                'group_id' => $group->id,
                'enabled' => true,
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('nc_targets', [
            'domain_or_url' => 'example.com',
            'owner_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_validates_expiry_date_is_in_future()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'expires_at' => now()->subDays(1)->format('Y-m-d'), // Past date
        ];

        $response = $this->actingAs($this->admin)
            ->post('/admin/users', $userData);

        $response->assertSessionHasErrors('expires_at');
    }

    /** @test */
    public function it_accepts_null_expiry_date()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'expires_at' => null,
        ];

        $response = $this->actingAs($this->admin)
            ->post('/admin/users', $userData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'expires_at' => null,
        ]);
    }

    /** @test */
    public function admin_can_view_system_statistics()
    {
        User::factory()->count(10)->create();
        
        $group = Group::factory()->create();
        Target::factory()->count(50)->create(['group_id' => $group->id]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats');
    }

    /** @test */
    public function admin_can_view_all_targets_across_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $group = Group::factory()->create();

        Target::factory()->count(3)->create([
            'owner_id' => $user1->id,
            'group_id' => $group->id,
        ]);
        
        Target::factory()->count(2)->create([
            'owner_id' => $user2->id,
            'group_id' => $group->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/targets');

        $response->assertOk();
        $response->assertViewHas('targets');
    }

    /** @test */
    public function admin_can_view_user_details()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/admin/users/{$user->id}");

        $response->assertOk();
        $response->assertViewHas('user');
    }

    /** @test */
    public function admin_can_suspend_user()
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => false,
            ]);

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertFalse($user->is_active);
    }

    /** @test */
    public function admin_can_reactivate_user()
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertTrue($user->is_active);
    }

    /** @test */
    public function it_validates_domain_limit_is_positive()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'domain_limit' => -5,
        ];

        $response = $this->actingAs($this->admin)
            ->post('/admin/users', $userData);

        $response->assertSessionHasErrors('domain_limit');
    }

    /** @test */
    public function admin_can_set_unlimited_domain_limit()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'domain_limit' => null, // Unlimited
        ];

        $response = $this->actingAs($this->admin)
            ->post('/admin/users', $userData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'domain_limit' => null,
        ]);
    }
}

