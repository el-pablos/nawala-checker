<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\NawalaChecker\CheckResult;
use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusLogsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Group $group;
    protected Target $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->group = Group::factory()->create();
        $this->target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_stores_check_result_with_timestamp()
    {
        $checkResult = CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'blocked',
            'checked_at' => now(),
        ]);

        $this->assertDatabaseHas('nc_check_results', [
            'id' => $checkResult->id,
            'target_id' => $this->target->id,
            'status' => 'blocked',
        ]);

        $this->assertNotNull($checkResult->checked_at);
    }

    /** @test */
    public function it_tracks_status_transitions()
    {
        // Initial status: accessible
        CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'accessible',
            'checked_at' => now()->subHours(2),
        ]);

        // Status changed to blocked
        CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'blocked',
            'checked_at' => now()->subHour(),
        ]);

        // Status changed back to accessible
        CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'accessible',
            'checked_at' => now(),
        ]);

        $results = CheckResult::where('target_id', $this->target->id)
            ->orderBy('checked_at', 'asc')
            ->get();

        $this->assertEquals('accessible', $results[0]->status);
        $this->assertEquals('blocked', $results[1]->status);
        $this->assertEquals('accessible', $results[2]->status);
    }

    /** @test */
    public function it_retrieves_check_history_for_target()
    {
        CheckResult::factory()->count(10)->create([
            'target_id' => $this->target->id,
        ]);

        $history = $this->target->checkResults()->get();

        $this->assertCount(10, $history);
    }

    /** @test */
    public function it_orders_history_by_timestamp_descending()
    {
        $old = CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'checked_at' => now()->subDays(2),
        ]);

        $recent = CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'checked_at' => now(),
        ]);

        $history = $this->target->checkResults()
            ->orderBy('checked_at', 'desc')
            ->get();

        $this->assertEquals($recent->id, $history->first()->id);
        $this->assertEquals($old->id, $history->last()->id);
    }

    /** @test */
    public function it_stores_resolver_results()
    {
        $resolverResults = [
            [
                'resolver' => 'Google DNS',
                'type' => 'dns',
                'verdict' => 'blocked',
                'ip_addresses' => ['103.10.66.1'],
            ],
            [
                'resolver' => 'Cloudflare DNS',
                'type' => 'dns',
                'verdict' => 'accessible',
                'ip_addresses' => ['93.184.216.34'],
            ],
        ];

        $checkResult = CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'resolver_results' => $resolverResults,
        ]);

        $this->assertDatabaseHas('nc_check_results', [
            'id' => $checkResult->id,
        ]);

        $this->assertEquals($resolverResults, $checkResult->resolver_results);
    }

    /** @test */
    public function it_stores_confidence_score()
    {
        $checkResult = CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'confidence' => 0.85,
        ]);

        $this->assertDatabaseHas('nc_check_results', [
            'id' => $checkResult->id,
            'confidence' => 0.85,
        ]);
    }

    /** @test */
    public function it_can_filter_history_by_status()
    {
        CheckResult::factory()->count(3)->create([
            'target_id' => $this->target->id,
            'status' => 'blocked',
        ]);

        CheckResult::factory()->count(2)->create([
            'target_id' => $this->target->id,
            'status' => 'accessible',
        ]);

        $blockedHistory = $this->target->checkResults()
            ->where('status', 'blocked')
            ->get();

        $this->assertCount(3, $blockedHistory);
    }

    /** @test */
    public function it_can_filter_history_by_date_range()
    {
        CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'checked_at' => now()->subDays(10),
        ]);

        CheckResult::factory()->count(3)->create([
            'target_id' => $this->target->id,
            'checked_at' => now()->subDays(3),
        ]);

        CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'checked_at' => now(),
        ]);

        $recentHistory = $this->target->checkResults()
            ->where('checked_at', '>=', now()->subDays(7))
            ->get();

        $this->assertCount(4, $recentHistory);
    }

    /** @test */
    public function it_updates_target_last_status()
    {
        $checkResult = CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'blocked',
        ]);

        $this->target->update([
            'last_status' => $checkResult->status,
            'last_checked_at' => $checkResult->checked_at,
        ]);

        $this->assertDatabaseHas('nc_targets', [
            'id' => $this->target->id,
            'last_status' => 'blocked',
        ]);
    }

    /** @test */
    public function it_updates_target_last_checked_at()
    {
        $checkTime = now();

        $checkResult = CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'checked_at' => $checkTime,
        ]);

        $this->target->update([
            'last_checked_at' => $checkResult->checked_at,
        ]);

        $this->target->refresh();
        
        $this->assertEquals(
            $checkTime->format('Y-m-d H:i:s'),
            $this->target->last_checked_at->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function it_detects_status_change()
    {
        // First check: accessible
        CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'accessible',
            'checked_at' => now()->subHour(),
        ]);

        $this->target->update(['last_status' => 'accessible']);

        // Second check: blocked (status changed)
        $newResult = CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'blocked',
            'checked_at' => now(),
        ]);

        $statusChanged = $this->target->last_status !== $newResult->status;

        $this->assertTrue($statusChanged);
    }

    /** @test */
    public function it_stores_error_messages()
    {
        $checkResult = CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'error',
            'error_message' => 'DNS resolution failed',
        ]);

        $this->assertDatabaseHas('nc_check_results', [
            'id' => $checkResult->id,
            'error_message' => 'DNS resolution failed',
        ]);
    }

    /** @test */
    public function it_can_get_latest_check_result()
    {
        CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'checked_at' => now()->subHours(2),
        ]);

        $latest = CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'checked_at' => now(),
        ]);

        $latestResult = $this->target->checkResults()
            ->latest('checked_at')
            ->first();

        $this->assertEquals($latest->id, $latestResult->id);
    }

    /** @test */
    public function it_can_count_status_changes()
    {
        CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'accessible',
            'checked_at' => now()->subHours(4),
        ]);

        CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'blocked',
            'checked_at' => now()->subHours(3),
        ]);

        CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'accessible',
            'checked_at' => now()->subHours(2),
        ]);

        CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'status' => 'blocked',
            'checked_at' => now()->subHour(),
        ]);

        $results = $this->target->checkResults()
            ->orderBy('checked_at', 'asc')
            ->get();

        $changes = 0;
        for ($i = 1; $i < $results->count(); $i++) {
            if ($results[$i]->status !== $results[$i - 1]->status) {
                $changes++;
            }
        }

        $this->assertEquals(3, $changes);
    }

    /** @test */
    public function it_can_paginate_history()
    {
        CheckResult::factory()->count(50)->create([
            'target_id' => $this->target->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/nawala-checker/targets/{$this->target->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('check_results')
        );
    }

    /** @test */
    public function it_stores_check_duration()
    {
        $checkResult = CheckResult::factory()->create([
            'target_id' => $this->target->id,
            'check_duration' => 1.25, // seconds
        ]);

        $this->assertDatabaseHas('nc_check_results', [
            'id' => $checkResult->id,
            'check_duration' => 1.25,
        ]);
    }
}

