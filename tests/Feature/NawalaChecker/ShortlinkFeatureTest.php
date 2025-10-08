<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\NawalaChecker\Shortlink;
use App\Models\NawalaChecker\ShortlinkGroup;
use App\Models\NawalaChecker\ShortlinkTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortlinkFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ShortlinkGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->group = ShortlinkGroup::factory()->create([
            'rotation_threshold' => 50,
            'rotation_cooldown' => 300,
        ]);
    }

    /** @test */
    public function it_can_list_shortlinks()
    {
        Shortlink::factory()->count(3)->create(['group_id' => $this->group->id]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker/shortlinks');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('tools/nawala-checker/shortlinks/index')
            ->has('shortlinks.data', 3)
        );
    }

    /** @test */
    public function it_can_create_shortlink_with_targets()
    {
        $data = [
            'slug' => 'test-shortlink',
            'group_id' => $this->group->id,
            'is_active' => true,
            'targets' => [
                [
                    'url' => 'https://example1.com',
                    'priority' => 1,
                    'weight' => 100,
                    'is_active' => true,
                ],
                [
                    'url' => 'https://example2.com',
                    'priority' => 2,
                    'weight' => 100,
                    'is_active' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/shortlinks', $data);

        $response->assertRedirect('/nawala-checker/shortlinks');

        $this->assertDatabaseHas('nc_shortlinks', [
            'slug' => 'test-shortlink',
            'group_id' => $this->group->id,
            'is_active' => true,
        ]);

        $shortlink = Shortlink::where('slug', 'test-shortlink')->first();
        $this->assertCount(2, $shortlink->targets);
        $this->assertEquals('https://example1.com', $shortlink->targets[0]->url);
    }

    /** @test */
    public function it_validates_slug_uniqueness()
    {
        Shortlink::factory()->create([
            'slug' => 'existing-slug',
            'group_id' => $this->group->id,
        ]);

        $data = [
            'slug' => 'existing-slug',
            'group_id' => $this->group->id,
            'is_active' => true,
            'targets' => [
                ['url' => 'https://example1.com', 'priority' => 1, 'weight' => 100, 'is_active' => true],
                ['url' => 'https://example2.com', 'priority' => 2, 'weight' => 100, 'is_active' => true],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/shortlinks', $data);

        $response->assertSessionHasErrors('slug');
    }

    /** @test */
    public function it_requires_minimum_two_targets()
    {
        $data = [
            'slug' => 'test-shortlink',
            'group_id' => $this->group->id,
            'is_active' => true,
            'targets' => [
                ['url' => 'https://example1.com', 'priority' => 1, 'weight' => 100, 'is_active' => true],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/shortlinks', $data);

        $response->assertSessionHasErrors('targets');
    }

    /** @test */
    public function it_can_show_shortlink_details()
    {
        $shortlink = Shortlink::factory()->create(['group_id' => $this->group->id]);
        ShortlinkTarget::factory()->count(2)->create(['shortlink_id' => $shortlink->id]);

        $response = $this->actingAs($this->user)
            ->get("/nawala-checker/shortlinks/{$shortlink->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('tools/nawala-checker/shortlinks/show')
            ->has('shortlink')
            ->where('shortlink.id', $shortlink->id)
        );
    }

    /** @test */
    public function it_can_force_rotate_shortlink()
    {
        $shortlink = Shortlink::factory()->create(['group_id' => $this->group->id]);
        
        $target1 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 1,
            'is_active' => true,
        ]);
        
        $target2 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 2,
            'is_active' => true,
        ]);

        $shortlink->update(['current_target_id' => $target1->id]);

        $response = $this->actingAs($this->user)
            ->post("/nawala-checker/shortlinks/{$shortlink->id}/rotate");

        $response->assertRedirect();
        
        $shortlink->refresh();
        $this->assertNotEquals($target1->id, $shortlink->current_target_id);
        $this->assertDatabaseHas('nc_rotation_history', [
            'shortlink_id' => $shortlink->id,
            'from_target_id' => $target1->id,
        ]);
    }

    /** @test */
    public function it_can_rollback_shortlink()
    {
        $shortlink = Shortlink::factory()->create(['group_id' => $this->group->id]);
        
        $target1 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 1,
            'is_active' => true,
        ]);
        
        $target2 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 2,
            'is_active' => true,
        ]);

        $shortlink->update([
            'current_target_id' => $target2->id,
            'original_target_id' => $target1->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post("/nawala-checker/shortlinks/{$shortlink->id}/rollback");

        $response->assertRedirect();
        
        $shortlink->refresh();
        $this->assertEquals($target1->id, $shortlink->current_target_id);
        $this->assertDatabaseHas('nc_rotation_history', [
            'shortlink_id' => $shortlink->id,
            'to_target_id' => $target1->id,
            'reason' => 'rollback',
        ]);
    }

    /** @test */
    public function it_can_delete_shortlink()
    {
        $shortlink = Shortlink::factory()->create(['group_id' => $this->group->id]);
        ShortlinkTarget::factory()->count(2)->create(['shortlink_id' => $shortlink->id]);

        $response = $this->actingAs($this->user)
            ->delete("/nawala-checker/shortlinks/{$shortlink->id}");

        $response->assertRedirect('/nawala-checker/shortlinks');
        $this->assertDatabaseMissing('nc_shortlinks', ['id' => $shortlink->id]);
        $this->assertDatabaseMissing('nc_shortlink_targets', ['shortlink_id' => $shortlink->id]);
    }

    /** @test */
    public function it_sanitizes_input_on_create()
    {
        $data = [
            'slug' => '<script>alert("xss")</script>test-slug',
            'group_id' => $this->group->id,
            'is_active' => true,
            'targets' => [
                [
                    'url' => 'https://example1.com',
                    'priority' => 1,
                    'weight' => 100,
                    'is_active' => true,
                ],
                [
                    'url' => 'https://example2.com',
                    'priority' => 2,
                    'weight' => 100,
                    'is_active' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/shortlinks', $data);

        $this->assertDatabaseMissing('nc_shortlinks', [
            'slug' => '<script>alert("xss")</script>test-slug',
        ]);
    }

    /** @test */
    public function it_prevents_rotation_during_cooldown()
    {
        $shortlink = Shortlink::factory()->create([
            'group_id' => $this->group->id,
            'last_rotated_at' => now()->subSeconds(100), // Within cooldown period
        ]);
        
        $target1 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 1,
            'is_active' => true,
        ]);
        
        $target2 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 2,
            'is_active' => true,
        ]);

        $shortlink->update(['current_target_id' => $target1->id]);

        $response = $this->actingAs($this->user)
            ->post("/nawala-checker/shortlinks/{$shortlink->id}/rotate");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function it_requires_authentication()
    {
        $shortlink = Shortlink::factory()->create(['group_id' => $this->group->id]);

        $this->get('/nawala-checker/shortlinks')
            ->assertRedirect('/login');

        $this->post('/nawala-checker/shortlinks', [])
            ->assertRedirect('/login');

        $this->get("/nawala-checker/shortlinks/{$shortlink->id}")
            ->assertRedirect('/login');

        $this->delete("/nawala-checker/shortlinks/{$shortlink->id}")
            ->assertRedirect('/login');
    }
}

