<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GroupManagementTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_create_group_with_color()
    {
        $colors = ['blue', 'green', 'red', 'yellow', 'purple', 'pink', 'indigo', 'gray'];

        foreach ($colors as $color) {
            $group = Group::factory()->create([
                'name' => "Group {$color}",
                'color' => $color,
            ]);

            $this->assertDatabaseHas('nc_groups', [
                'name' => "Group {$color}",
                'color' => $color,
            ]);
        }
    }

    /** @test */
    public function it_validates_color_options()
    {
        $validColors = ['blue', 'green', 'red', 'yellow', 'purple', 'pink', 'indigo', 'gray'];

        foreach ($validColors as $color) {
            $group = Group::factory()->create(['color' => $color]);
            $this->assertEquals($color, $group->color);
        }
    }

    /** @test */
    public function it_can_move_target_between_groups()
    {
        $group1 = Group::factory()->create(['name' => 'Group 1']);
        $group2 = Group::factory()->create(['name' => 'Group 2']);

        $target = Target::factory()->create([
            'group_id' => $group1->id,
            'owner_id' => $this->user->id,
        ]);

        $this->assertEquals($group1->id, $target->group_id);

        // Move to group 2
        $response = $this->actingAs($this->user)
            ->put("/nawala-checker/targets/{$target->id}", [
                'domain_or_url' => $target->domain_or_url,
                'type' => $target->type,
                'enabled' => $target->enabled,
                'group_id' => $group2->id,
            ]);

        $response->assertRedirect();

        $target->refresh();
        $this->assertEquals($group2->id, $target->group_id);
    }

    /** @test */
    public function it_can_delete_group_with_cascade()
    {
        $group = Group::factory()->create();
        $targets = Target::factory()->count(3)->create([
            'group_id' => $group->id,
            'owner_id' => $this->user->id,
        ]);

        $this->assertDatabaseCount('nc_targets', 3);

        // Delete group
        $group->delete();

        // Verify targets still exist but group_id is set to null (nullOnDelete)
        $this->assertDatabaseCount('nc_targets', 3);
        foreach ($targets as $target) {
            $this->assertDatabaseHas('nc_targets', [
                'id' => $target->id,
                'group_id' => null,
            ]);
        }
    }

    /** @test */
    public function it_has_default_color_if_not_specified()
    {
        // Create group without specifying color
        $group = Group::factory()->create();

        // Refresh to get database default
        $group->refresh();

        // Should have the default color from database
        $this->assertEquals('#3B82F6', $group->color);
    }

    /** @test */
    public function it_can_list_targets_by_group()
    {
        $group1 = Group::factory()->create(['name' => 'Group 1']);
        $group2 = Group::factory()->create(['name' => 'Group 2']);

        Target::factory()->count(3)->create([
            'group_id' => $group1->id,
            'owner_id' => $this->user->id,
        ]);
        Target::factory()->count(2)->create([
            'group_id' => $group2->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/nawala-checker/targets?group_id={$group1->id}");

        $response->assertStatus(200);

        // Should return only group1 targets
        $response->assertInertia(fn ($page) =>
            $page->component('tools/nawala-checker/targets/index')
                ->has('targets.data', 3)
        );
    }

    /** @test */
    public function it_stores_group_description()
    {
        $group = Group::factory()->create([
            'name' => 'Test Group',
            'description' => 'This is a test group',
        ]);

        $this->assertDatabaseHas('nc_groups', [
            'name' => 'Test Group',
            'description' => 'This is a test group',
        ]);
    }

    /** @test */
    public function it_stores_default_check_interval_for_group()
    {
        $group = Group::factory()->create([
            'default_check_interval' => 600,
        ]);

        $this->assertDatabaseHas('nc_groups', [
            'id' => $group->id,
            'default_check_interval' => 600,
        ]);
    }

    /** @test */
    public function it_can_count_targets_in_group()
    {
        $group = Group::factory()->create();
        Target::factory()->count(5)->create([
            'group_id' => $group->id,
            'owner_id' => $this->user->id,
        ]);

        $group->refresh();
        $this->assertEquals(5, $group->targets()->count());
    }

    /** @test */
    public function it_can_get_group_statistics()
    {
        $group = Group::factory()->create();

        // Create targets with different statuses
        Target::factory()->create([
            'group_id' => $group->id,
            'owner_id' => $this->user->id,
            'current_status' => 'DNS_FILTERED',
        ]);

        Target::factory()->create([
            'group_id' => $group->id,
            'owner_id' => $this->user->id,
            'current_status' => 'OK',
        ]);

        Target::factory()->create([
            'group_id' => $group->id,
            'owner_id' => $this->user->id,
            'current_status' => 'OK',
        ]);

        $group->refresh();

        $blockedCount = $group->targets()->where('current_status', 'DNS_FILTERED')->count();
        $accessibleCount = $group->targets()->where('current_status', 'OK')->count();

        $this->assertEquals(1, $blockedCount);
        $this->assertEquals(2, $accessibleCount);
    }

    /** @test */
    public function it_requires_unique_group_slug()
    {
        Group::factory()->create([
            'name' => 'Unique Group',
            'slug' => 'unique-group',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Group::factory()->create([
            'name' => 'Another Name',
            'slug' => 'unique-group', // Same slug should fail
        ]);
    }

    /** @test */
    public function it_can_update_group_color()
    {
        $group = Group::factory()->create(['color' => 'blue']);

        $group->update(['color' => 'red']);

        $this->assertDatabaseHas('nc_groups', [
            'id' => $group->id,
            'color' => 'red',
        ]);
    }

    /** @test */
    public function it_can_disable_all_targets_in_group()
    {
        $group = Group::factory()->create();
        $targets = Target::factory()->count(3)->create([
            'group_id' => $group->id,
            'owner_id' => $this->user->id,
            'enabled' => true,
        ]);

        // Disable all targets in group
        $group->targets()->update(['enabled' => false]);

        foreach ($targets as $target) {
            $target->refresh();
            $this->assertFalse($target->enabled);
        }
    }

    /** @test */
    public function it_can_get_active_targets_count()
    {
        $group = Group::factory()->create();

        Target::factory()->count(3)->create([
            'group_id' => $group->id,
            'owner_id' => $this->user->id,
            'enabled' => true,
        ]);

        Target::factory()->count(2)->create([
            'group_id' => $group->id,
            'owner_id' => $this->user->id,
            'enabled' => false,
        ]);

        $activeCount = $group->targets()->where('enabled', true)->count();
        $this->assertEquals(3, $activeCount);
    }
}

