<?php

namespace Tests\Unit\NawalaChecker;

use App\Models\NawalaChecker\Shortlink;
use App\Models\NawalaChecker\ShortlinkGroup;
use App\Models\NawalaChecker\ShortlinkTarget;
use App\Models\User;
use App\Services\NawalaChecker\ShortlinkRotationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ShortlinkRotationServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected ShortlinkRotationService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShortlinkRotationService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_rotate_shortlink_to_next_target()
    {
        $group = ShortlinkGroup::factory()->create([
            'cooldown_seconds' => 0, // No cooldown for testing
        ]);

        $shortlink = Shortlink::factory()->create([
            'group_id' => $group->id,
        ]);

        $target1 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 1,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $target2 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 2,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $shortlink->update([
            'current_target_id' => $target1->id,
            'original_target_id' => $target1->id,
        ]);

        $result = $this->service->rotate($shortlink, $this->user->id, 'manual');

        $this->assertTrue($result);
        $shortlink->refresh();
        $this->assertEquals($target2->id, $shortlink->current_target_id);
        $this->assertEquals(1, $shortlink->rotation_count);
    }

    /** @test */
    public function it_respects_cooldown_period()
    {
        $group = ShortlinkGroup::factory()->create([
            'cooldown_seconds' => 3600, // 1 hour cooldown
        ]);

        $shortlink = Shortlink::factory()->create([
            'group_id' => $group->id,
            'last_rotated_at' => now()->subMinutes(30), // Rotated 30 minutes ago
        ]);

        $target1 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $target2 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $shortlink->update(['current_target_id' => $target1->id]);

        $result = $this->service->rotate($shortlink, $this->user->id, 'manual');

        $this->assertFalse($result); // Should fail due to cooldown
    }

    /** @test */
    public function it_can_rollback_to_original_target()
    {
        $shortlink = Shortlink::factory()->create();

        $originalTarget = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
        ]);

        $currentTarget = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
        ]);

        $shortlink->update([
            'original_target_id' => $originalTarget->id,
            'current_target_id' => $currentTarget->id,
        ]);

        $result = $this->service->rollback($shortlink, $this->user->id);

        $this->assertTrue($result);
        $shortlink->refresh();
        $this->assertEquals($originalTarget->id, $shortlink->current_target_id);
    }

    /** @test */
    public function it_records_rotation_history()
    {
        $group = ShortlinkGroup::factory()->create(['cooldown_seconds' => 0]);
        $shortlink = Shortlink::factory()->create(['group_id' => $group->id]);

        $target1 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $target2 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $shortlink->update(['current_target_id' => $target1->id]);

        $this->service->rotate($shortlink, $this->user->id, 'manual');

        $this->assertDatabaseHas('nc_rotation_history', [
            'shortlink_id' => $shortlink->id,
            'from_target_id' => $target1->id,
            'to_target_id' => $target2->id,
            'reason' => 'manual',
            'triggered_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_selects_highest_priority_target()
    {
        $group = ShortlinkGroup::factory()->create(['cooldown_seconds' => 0]);
        $shortlink = Shortlink::factory()->create(['group_id' => $group->id]);

        $target1 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 10,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $target2 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 5, // Higher priority (lower number)
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $target3 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 15,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $shortlink->update(['current_target_id' => $target1->id]);

        $this->service->rotate($shortlink, $this->user->id, 'manual');

        $shortlink->refresh();
        $this->assertEquals($target2->id, $shortlink->current_target_id);
    }

    /** @test */
    public function it_skips_inactive_targets()
    {
        $group = ShortlinkGroup::factory()->create(['cooldown_seconds' => 0]);
        $shortlink = Shortlink::factory()->create(['group_id' => $group->id]);

        $target1 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 1,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $target2 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 2,
            'is_active' => false, // Inactive
            'current_status' => 'OK',
        ]);

        $target3 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'priority' => 3,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $shortlink->update(['current_target_id' => $target1->id]);

        $this->service->rotate($shortlink, $this->user->id, 'manual');

        $shortlink->refresh();
        $this->assertEquals($target3->id, $shortlink->current_target_id); // Should skip target2
    }

    /** @test */
    public function it_fails_rotation_when_no_alternative_targets_available()
    {
        $group = ShortlinkGroup::factory()->create(['cooldown_seconds' => 0]);
        $shortlink = Shortlink::factory()->create(['group_id' => $group->id]);

        $target1 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $shortlink->update(['current_target_id' => $target1->id]);

        $result = $this->service->rotate($shortlink, $this->user->id, 'manual');

        $this->assertFalse($result); // No alternative targets
    }

    /** @test */
    public function it_can_determine_if_shortlink_should_rotate()
    {
        $group = ShortlinkGroup::factory()->create([
            'cooldown_seconds' => 0,
            'rotation_threshold' => 3,
        ]);

        $shortlink = Shortlink::factory()->create(['group_id' => $group->id]);

        $target1 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'is_active' => true,
            'current_status' => 'DNS_FILTERED', // Failing
        ]);

        $target2 = ShortlinkTarget::factory()->create([
            'shortlink_id' => $shortlink->id,
            'is_active' => true,
            'current_status' => 'OK',
        ]);

        $shortlink->update(['current_target_id' => $target1->id]);

        $shouldRotate = $this->service->shouldRotate($shortlink);

        $this->assertTrue($shouldRotate);
    }
}

