<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\NawalaChecker\Target;
use App\Models\NawalaChecker\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class TargetFeatureTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_view_targets_list()
    {
        $this->actingAs($this->user);

        Target::factory()->count(3)->create(['owner_id' => $this->user->id]);

        $response = $this->get(route('nawala-checker.targets.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_create_target()
    {
        $this->actingAs($this->user);

        $data = [
            'domain_or_url' => 'example.com',
            'type' => 'domain',
            'enabled' => true,
        ];

        $response = $this->post(route('nawala-checker.targets.store'), $data);

        $response->assertRedirect(route('nawala-checker.targets.index'));
        $this->assertDatabaseHas('nc_targets', [
            'domain_or_url' => 'example.com',
            'owner_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_update_target()
    {
        $this->actingAs($this->user);

        $target = Target::factory()->create(['owner_id' => $this->user->id]);

        $data = [
            'domain_or_url' => 'updated-example.com',
            'enabled' => false,
        ];

        $response = $this->put(route('nawala-checker.targets.update', $target), $data);

        $response->assertRedirect(route('nawala-checker.targets.index'));
        $this->assertDatabaseHas('nc_targets', [
            'id' => $target->id,
            'domain_or_url' => 'updated-example.com',
            'enabled' => false,
        ]);
    }

    /** @test */
    public function user_can_delete_target()
    {
        $this->actingAs($this->user);

        $target = Target::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->delete(route('nawala-checker.targets.destroy', $target));

        $response->assertRedirect(route('nawala-checker.targets.index'));
        $this->assertSoftDeleted('nc_targets', ['id' => $target->id]);
    }

    /** @test */
    public function user_can_toggle_target_status()
    {
        $this->actingAs($this->user);

        $target = Target::factory()->create([
            'owner_id' => $this->user->id,
            'enabled' => true,
        ]);

        $response = $this->post(route('nawala-checker.targets.toggle', $target));

        $response->assertRedirect();
        $this->assertDatabaseHas('nc_targets', [
            'id' => $target->id,
            'enabled' => false,
        ]);
    }

    /** @test */
    public function user_can_run_check_on_target()
    {
        $this->actingAs($this->user);

        $target = Target::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->post(route('nawala-checker.targets.run-check', $target));

        $response->assertRedirect();
        $target->refresh();
        $this->assertNotNull($target->last_checked_at);
    }

    /** @test */
    public function domain_validation_rejects_invalid_domain()
    {
        $this->actingAs($this->user);

        $data = [
            'domain_or_url' => 'not-a-valid-domain',
            'type' => 'domain',
        ];

        $response = $this->post(route('nawala-checker.targets.store'), $data);

        $response->assertSessionHasErrors('domain_or_url');
    }

    /** @test */
    public function user_cannot_create_duplicate_target()
    {
        $this->actingAs($this->user);

        Target::factory()->create([
            'domain_or_url' => 'example.com',
            'owner_id' => $this->user->id,
        ]);

        $data = [
            'domain_or_url' => 'example.com',
            'type' => 'domain',
        ];

        $response = $this->post(route('nawala-checker.targets.store'), $data);

        $response->assertSessionHasErrors('domain_or_url');
    }

    /** @test */
    public function check_interval_must_be_within_valid_range()
    {
        $this->actingAs($this->user);

        // Too low
        $data = [
            'domain_or_url' => 'example.com',
            'type' => 'domain',
            'check_interval' => 30, // Less than 60
        ];

        $response = $this->post(route('nawala-checker.targets.store'), $data);
        $response->assertSessionHasErrors('check_interval');

        // Too high
        $data['check_interval'] = 90000; // More than 86400
        $response = $this->post(route('nawala-checker.targets.store'), $data);
        $response->assertSessionHasErrors('check_interval');
    }

    /** @test */
    public function user_can_filter_targets_by_status()
    {
        $this->actingAs($this->user);

        Target::factory()->create([
            'owner_id' => $this->user->id,
            'current_status' => 'OK',
        ]);

        Target::factory()->create([
            'owner_id' => $this->user->id,
            'current_status' => 'DNS_FILTERED',
        ]);

        $response = $this->get(route('nawala-checker.targets.index', ['status' => 'OK']));

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_search_targets()
    {
        $this->actingAs($this->user);

        Target::factory()->create([
            'owner_id' => $this->user->id,
            'domain_or_url' => 'searchable-domain.com',
        ]);

        $response = $this->get(route('nawala-checker.targets.index', ['search' => 'searchable']));

        $response->assertStatus(200);
    }
}

