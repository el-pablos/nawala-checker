<?php

namespace Tests\Unit\NawalaChecker;

use App\Models\NawalaChecker\CheckResult;
use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\Resolver;
use App\Models\NawalaChecker\Shortlink;
use App\Models\NawalaChecker\ShortlinkGroup;
use App\Models\NawalaChecker\ShortlinkTarget;
use App\Models\NawalaChecker\Tag;
use App\Models\NawalaChecker\Target;
use App\Models\User;
use App\Services\NawalaChecker\NawalaCheckerService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NawalaCheckerServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected NawalaCheckerService $service;
    protected User $user;
    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NawalaCheckerService::class);
        $this->user = User::factory()->create();
        $this->group = Group::factory()->create();
    }

    /** @test */
    public function it_can_get_targets_list()
    {
        Target::factory()->count(5)->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        $targets = $this->service->getTargets([], 10);

        $this->assertCount(5, $targets);
    }

    /** @test */
    public function it_can_filter_targets_by_group()
    {
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        Target::factory()->count(3)->create(['group_id' => $group1->id]);
        Target::factory()->count(2)->create(['group_id' => $group2->id]);

        $targets = $this->service->getTargets(['group_id' => $group1->id], 10);

        $this->assertCount(3, $targets);
    }

    /** @test */
    public function it_can_filter_targets_by_status()
    {
        Target::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'current_status' => 'OK',
        ]);
        
        Target::factory()->count(2)->create([
            'group_id' => $this->group->id,
            'current_status' => 'DNS_FILTERED',
        ]);

        $targets = $this->service->getTargets(['status' => 'OK'], 10);

        $this->assertCount(3, $targets);
    }

    /** @test */
    public function it_can_search_targets()
    {
        Target::factory()->create([
            'group_id' => $this->group->id,
            'domain_or_url' => 'searchable-domain.com',
        ]);
        
        Target::factory()->create([
            'group_id' => $this->group->id,
            'domain_or_url' => 'other-domain.com',
        ]);

        $targets = $this->service->getTargets(['search' => 'searchable'], 10);

        $this->assertCount(1, $targets);
    }

    /** @test */
    public function it_can_create_target()
    {
        $data = [
            'domain_or_url' => 'example.com',
            'type' => 'domain',
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'enabled' => true,
            'check_interval' => 300,
        ];

        $target = $this->service->createTarget($data);

        $this->assertInstanceOf(Target::class, $target);
        $this->assertEquals('example.com', $target->domain_or_url);
        $this->assertDatabaseHas('nc_targets', [
            'domain_or_url' => 'example.com',
        ]);
    }

    /** @test */
    public function it_can_create_target_with_tags()
    {
        // Create tags first
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $tag3 = Tag::factory()->create();

        $data = [
            'domain_or_url' => 'example.com',
            'type' => 'domain',
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'enabled' => true,
            'tags' => [$tag1->id, $tag2->id, $tag3->id],
        ];

        $target = $this->service->createTarget($data);

        $this->assertCount(3, $target->tags);
    }

    /** @test */
    public function it_can_update_target()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        $data = [
            'domain_or_url' => 'updated-domain.com',
            'type' => 'domain',
            'enabled' => false,
        ];

        $updated = $this->service->updateTarget($target, $data);

        $this->assertEquals('updated-domain.com', $updated->domain_or_url);
        $this->assertFalse($updated->enabled);
    }

    /** @test */
    public function it_can_delete_target()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        $this->service->deleteTarget($target);

        $this->assertSoftDeleted('nc_targets', ['id' => $target->id]);
    }

    /** @test */
    public function it_can_run_check_now()
    {
        // Mock HTTP requests to prevent actual network calls
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        Resolver::factory()->create(['is_active' => true]);

        $result = $this->service->runCheckNow($target);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('confidence', $result);
    }

    /** @test */
    public function it_can_get_dashboard_stats()
    {
        Target::factory()->count(5)->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'current_status' => 'OK',
        ]);
        
        Target::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'current_status' => 'DNS_FILTERED',
        ]);

        $stats = $this->service->getDashboardStats($this->user->id);

        $this->assertEquals(8, $stats['total_targets']);
        $this->assertEquals(5, $stats['accessible_count']);
        $this->assertEquals(3, $stats['blocked_count']);
    }

    /** @test */
    public function it_can_get_check_results()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        // Create check results within the last 24 hours
        CheckResult::factory()->count(10)->create([
            'target_id' => $target->id,
            'checked_at' => now()->subHours(rand(1, 23)),
        ]);

        $results = $this->service->getCheckResults($target, 24);

        $this->assertCount(10, $results);
    }

    /** @test */
    public function it_can_get_target_statistics()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        CheckResult::factory()->count(5)->create([
            'target_id' => $target->id,
            'status' => 'OK',
        ]);
        
        CheckResult::factory()->count(3)->create([
            'target_id' => $target->id,
            'status' => 'DNS_FILTERED',
        ]);

        $stats = $this->service->getTargetStatistics($target, 7);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_checks', $stats);
        $this->assertArrayHasKey('blocked_count', $stats);
        $this->assertArrayHasKey('accessible_count', $stats);
    }

    /** @test */
    public function it_can_get_shortlinks_list()
    {
        $group = ShortlinkGroup::factory()->create();
        
        Shortlink::factory()->count(5)->create([
            'group_id' => $group->id,
        ]);

        $shortlinks = $this->service->getShortlinks([], 10);

        $this->assertCount(5, $shortlinks);
    }

    /** @test */
    public function it_can_rotate_shortlink()
    {
        $group = ShortlinkGroup::factory()->create(['cooldown_seconds' => 0]);
        $shortlink = Shortlink::factory()->create(['group_id' => $group->id]);
        
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

        $result = $this->service->rotateShortlink($shortlink, $this->user->id, 'manual');

        $this->assertTrue($result);
        $shortlink->refresh();
        $this->assertEquals($target2->id, $shortlink->current_target_id);
    }

    /** @test */
    public function it_can_rollback_shortlink()
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

        $result = $this->service->rollbackShortlink($shortlink, $this->user->id);

        $this->assertTrue($result);
        $shortlink->refresh();
        $this->assertEquals($originalTarget->id, $shortlink->current_target_id);
    }

    /** @test */
    public function it_calculates_dashboard_percentages_correctly()
    {
        Target::factory()->count(7)->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'current_status' => 'OK',
        ]);
        
        Target::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'current_status' => 'DNS_FILTERED',
        ]);

        $stats = $this->service->getDashboardStats($this->user->id);

        $this->assertEquals(70, $stats['accessible_percentage']);
        $this->assertEquals(30, $stats['blocked_percentage']);
    }
}

