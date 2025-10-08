<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteMonitoringTest extends TestCase
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
    public function it_can_add_valid_domain_for_monitoring()
    {
        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/targets', [
                'domain_or_url' => 'example.com',
                'type' => 'domain',
                'enabled' => true,
                'group_id' => $this->group->id,
                'check_interval' => 300,
            ]);

        $response->assertRedirect('/nawala-checker/targets');
        
        $this->assertDatabaseHas('nc_targets', [
            'domain_or_url' => 'example.com',
            'type' => 'domain',
            'enabled' => true,
            'check_interval' => 300,
        ]);
    }

    /** @test */
    public function it_can_add_valid_url_for_monitoring()
    {
        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/targets', [
                'domain_or_url' => 'https://example.com/path',
                'type' => 'url',
                'enabled' => true,
                'group_id' => $this->group->id,
            ]);

        $response->assertRedirect('/nawala-checker/targets');
        
        $this->assertDatabaseHas('nc_targets', [
            'domain_or_url' => 'https://example.com/path',
            'type' => 'url',
        ]);
    }

    /** @test */
    public function it_validates_domain_format()
    {
        $invalidDomains = [
            '',
            'not a domain',
            'http://example.com', // Should use type=url
            '192.168.1.1', // IP address
        ];

        foreach ($invalidDomains as $domain) {
            $response = $this->actingAs($this->user)
                ->post('/nawala-checker/targets', [
                    'domain_or_url' => $domain,
                    'type' => 'domain',
                    'enabled' => true,
                    'group_id' => $this->group->id,
                ]);

            $response->assertSessionHasErrors('domain_or_url');
        }
    }

    /** @test */
    public function it_validates_url_format()
    {
        $invalidUrls = [
            '',
            'not-a-url',
            'example.com', // Missing protocol
            'ftp://example.com', // Wrong protocol
        ];

        foreach ($invalidUrls as $url) {
            $response = $this->actingAs($this->user)
                ->post('/nawala-checker/targets', [
                    'domain_or_url' => $url,
                    'type' => 'url',
                    'enabled' => true,
                    'group_id' => $this->group->id,
                ]);

            $response->assertSessionHasErrors('domain_or_url');
        }
    }

    /** @test */
    public function it_validates_check_interval_minimum()
    {
        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/targets', [
                'domain_or_url' => 'example.com',
                'type' => 'domain',
                'enabled' => true,
                'group_id' => $this->group->id,
                'check_interval' => 30, // Less than 60 seconds
            ]);

        $response->assertSessionHasErrors('check_interval');
    }

    /** @test */
    public function it_validates_check_interval_maximum()
    {
        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/targets', [
                'domain_or_url' => 'example.com',
                'type' => 'domain',
                'enabled' => true,
                'group_id' => $this->group->id,
                'check_interval' => 100000, // More than 86400 seconds (24 hours)
            ]);

        $response->assertSessionHasErrors('check_interval');
    }

    /** @test */
    public function it_accepts_valid_check_intervals()
    {
        $validIntervals = [60, 300, 600, 1800, 3600, 86400];

        foreach ($validIntervals as $interval) {
            $response = $this->actingAs($this->user)
                ->post('/nawala-checker/targets', [
                    'domain_or_url' => "example{$interval}.com",
                    'type' => 'domain',
                    'enabled' => true,
                    'group_id' => $this->group->id,
                    'check_interval' => $interval,
                ]);

            $response->assertRedirect();
            
            $this->assertDatabaseHas('nc_targets', [
                'domain_or_url' => "example{$interval}.com",
                'check_interval' => $interval,
            ]);
        }
    }

    /** @test */
    public function it_can_execute_manual_check()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'enabled' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post("/nawala-checker/targets/{$target->id}/run-check");

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function it_can_toggle_monitoring_status()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'enabled' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post("/nawala-checker/targets/{$target->id}/toggle");

        $response->assertRedirect();
        
        $target->refresh();
        $this->assertFalse($target->enabled);

        // Toggle back
        $response = $this->actingAs($this->user)
            ->post("/nawala-checker/targets/{$target->id}/toggle");

        $target->refresh();
        $this->assertTrue($target->enabled);
    }

    /** @test */
    public function it_uses_group_default_interval_when_not_specified()
    {
        $group = Group::factory()->create([
            'default_check_interval' => 600,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/targets', [
                'domain_or_url' => 'example.com',
                'type' => 'domain',
                'enabled' => true,
                'group_id' => $group->id,
                // No check_interval specified
            ]);

        $response->assertRedirect();
        
        $target = Target::where('domain_or_url', 'example.com')->first();
        $this->assertNull($target->check_interval);
        $this->assertEquals(600, $target->getEffectiveCheckInterval());
    }

    /** @test */
    public function it_requires_group_id()
    {
        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/targets', [
                'domain_or_url' => 'example.com',
                'type' => 'domain',
                'enabled' => true,
                // No group_id
            ]);

        $response->assertSessionHasErrors('group_id');
    }

    /** @test */
    public function it_validates_group_exists()
    {
        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/targets', [
                'domain_or_url' => 'example.com',
                'type' => 'domain',
                'enabled' => true,
                'group_id' => 99999, // Non-existent group
            ]);

        $response->assertSessionHasErrors('group_id');
    }

    /** @test */
    public function it_can_disable_monitoring_on_creation()
    {
        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/targets', [
                'domain_or_url' => 'example.com',
                'type' => 'domain',
                'enabled' => false,
                'group_id' => $this->group->id,
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('nc_targets', [
            'domain_or_url' => 'example.com',
            'enabled' => false,
        ]);
    }

    /** @test */
    public function it_stores_owner_id_on_creation()
    {
        $response = $this->actingAs($this->user)
            ->post('/nawala-checker/targets', [
                'domain_or_url' => 'example.com',
                'type' => 'domain',
                'enabled' => true,
                'group_id' => $this->group->id,
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('nc_targets', [
            'domain_or_url' => 'example.com',
            'owner_id' => $this->user->id,
        ]);
    }
}

