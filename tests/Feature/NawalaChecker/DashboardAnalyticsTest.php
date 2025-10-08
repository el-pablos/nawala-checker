<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\NawalaChecker\CheckResult;
use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->group = Group::factory()->create();
    }

    /** @test */
    public function it_displays_dashboard_page()
    {
        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('tools/nawala-checker/dashboard')
        );
    }

    /** @test */
    public function it_calculates_total_targets_count()
    {
        Target::factory()->count(10)->create(['group_id' => $this->group->id]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('stats')
                ->where('stats.total_targets', 10)
        );
    }

    /** @test */
    public function it_calculates_blocked_targets_count()
    {
        Target::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'last_status' => 'blocked',
        ]);

        Target::factory()->count(7)->create([
            'group_id' => $this->group->id,
            'last_status' => 'accessible',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('stats.blocked_count', 3)
        );
    }

    /** @test */
    public function it_calculates_accessible_targets_count()
    {
        Target::factory()->count(4)->create([
            'group_id' => $this->group->id,
            'last_status' => 'accessible',
        ]);

        Target::factory()->count(6)->create([
            'group_id' => $this->group->id,
            'last_status' => 'blocked',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('stats.accessible_count', 4)
        );
    }

    /** @test */
    public function it_calculates_unknown_status_count()
    {
        Target::factory()->count(2)->create([
            'group_id' => $this->group->id,
            'last_status' => null,
        ]);

        Target::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'last_status' => 'accessible',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('stats.unknown_count', 2)
        );
    }

    /** @test */
    public function it_calculates_active_monitoring_count()
    {
        Target::factory()->count(8)->create([
            'group_id' => $this->group->id,
            'enabled' => true,
        ]);

        Target::factory()->count(2)->create([
            'group_id' => $this->group->id,
            'enabled' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('stats.active_count', 8)
        );
    }

    /** @test */
    public function it_shows_recent_checks()
    {
        $target = Target::factory()->create(['group_id' => $this->group->id]);

        CheckResult::factory()->count(5)->create([
            'target_id' => $target->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('recent_checks')
        );
    }

    /** @test */
    public function it_shows_groups_with_target_counts()
    {
        $group1 = Group::factory()->create(['name' => 'Group 1']);
        $group2 = Group::factory()->create(['name' => 'Group 2']);

        Target::factory()->count(5)->create(['group_id' => $group1->id]);
        Target::factory()->count(3)->create(['group_id' => $group2->id]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('groups')
        );
    }

    /** @test */
    public function it_calculates_percentage_blocked()
    {
        Target::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'last_status' => 'blocked',
        ]);

        Target::factory()->count(7)->create([
            'group_id' => $this->group->id,
            'last_status' => 'accessible',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        
        // 3 blocked out of 10 total = 30%
        $response->assertInertia(fn ($page) => 
            $page->where('stats.blocked_percentage', 30)
        );
    }

    /** @test */
    public function it_calculates_percentage_accessible()
    {
        Target::factory()->count(7)->create([
            'group_id' => $this->group->id,
            'last_status' => 'accessible',
        ]);

        Target::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'last_status' => 'blocked',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        
        // 7 accessible out of 10 total = 70%
        $response->assertInertia(fn ($page) => 
            $page->where('stats.accessible_percentage', 70)
        );
    }

    /** @test */
    public function it_handles_zero_targets_gracefully()
    {
        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('stats.total_targets', 0)
                ->where('stats.blocked_count', 0)
                ->where('stats.accessible_count', 0)
        );
    }

    /** @test */
    public function it_shows_last_check_time()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'last_checked_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('stats')
        );
    }

    /** @test */
    public function it_counts_checks_in_last_24_hours()
    {
        $target = Target::factory()->create(['group_id' => $this->group->id]);

        // Recent checks (last 24 hours)
        CheckResult::factory()->count(10)->create([
            'target_id' => $target->id,
            'checked_at' => now()->subHours(12),
        ]);

        // Old checks (more than 24 hours ago)
        CheckResult::factory()->count(5)->create([
            'target_id' => $target->id,
            'checked_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('stats.checks_last_24h', 10)
        );
    }

    /** @test */
    public function it_shows_status_distribution()
    {
        Target::factory()->count(5)->create([
            'group_id' => $this->group->id,
            'last_status' => 'blocked',
        ]);

        Target::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'last_status' => 'accessible',
        ]);

        Target::factory()->count(2)->create([
            'group_id' => $this->group->id,
            'last_status' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('stats')
                ->where('stats.total_targets', 10)
                ->where('stats.blocked_count', 5)
                ->where('stats.accessible_count', 3)
                ->where('stats.unknown_count', 2)
        );
    }

    /** @test */
    public function it_updates_stats_in_real_time()
    {
        // Initial state
        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertInertia(fn ($page) => 
            $page->where('stats.total_targets', 0)
        );

        // Add targets
        Target::factory()->count(5)->create(['group_id' => $this->group->id]);

        // Check updated stats
        $response = $this->actingAs($this->user)
            ->get('/nawala-checker');

        $response->assertInertia(fn ($page) => 
            $page->where('stats.total_targets', 5)
        );
    }
}

