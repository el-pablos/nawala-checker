<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\NotificationChannel;
use App\Models\NawalaChecker\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->group = Group::factory()->create();
    }

    /** @test */
    public function it_can_update_check_interval_for_target()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'check_interval' => 300,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/nawala-checker/targets/{$target->id}", [
                'domain_or_url' => $target->domain_or_url,
                'type' => $target->type,
                'enabled' => $target->enabled,
                'check_interval' => 600,
            ]);

        $response->assertRedirect();
        
        $target->refresh();
        $this->assertEquals(600, $target->check_interval);
    }

    /** @test */
    public function it_validates_minimum_check_interval()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/nawala-checker/targets/{$target->id}", [
                'domain_or_url' => $target->domain_or_url,
                'type' => $target->type,
                'enabled' => $target->enabled,
                'check_interval' => 30, // Less than 60 seconds
            ]);

        $response->assertSessionHasErrors('check_interval');
    }

    /** @test */
    public function it_validates_maximum_check_interval()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/nawala-checker/targets/{$target->id}", [
                'domain_or_url' => $target->domain_or_url,
                'type' => $target->type,
                'enabled' => $target->enabled,
                'check_interval' => 4000, // More than 3600 seconds (1 hour)
            ]);

        $response->assertSessionHasErrors('check_interval');
    }

    /** @test */
    public function it_accepts_valid_check_intervals()
    {
        $validIntervals = [60, 120, 300, 600, 900, 1800, 3600];

        foreach ($validIntervals as $interval) {
            $target = Target::factory()->create([
                'group_id' => $this->group->id,
                'owner_id' => $this->user->id,
            ]);

            $response = $this->actingAs($this->user)
                ->put("/nawala-checker/targets/{$target->id}", [
                    'domain_or_url' => $target->domain_or_url,
                    'type' => $target->type,
                    'enabled' => $target->enabled,
                    'check_interval' => $interval,
                ]);

            $response->assertRedirect();
            
            $target->refresh();
            $this->assertEquals($interval, $target->check_interval);
        }
    }

    /** @test */
    public function it_can_update_group_default_check_interval()
    {
        $group = Group::factory()->create([
            'check_interval' => 300,
        ]);

        $group->update(['check_interval' => 600]);

        $this->assertDatabaseHas('nc_groups', [
            'id' => $group->id,
            'check_interval' => 600,
        ]);
    }

    /** @test */
    public function it_can_enable_notifications_for_target()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'is_active' => false,
        ]);

        $channel->update(['is_active' => true]);

        $this->assertDatabaseHas('nc_notification_channels', [
            'id' => $channel->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_disable_notifications_for_target()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'is_active' => true,
        ]);

        $channel->update(['is_active' => false]);

        $this->assertDatabaseHas('nc_notification_channels', [
            'id' => $channel->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_can_enable_notifications_globally_for_group()
    {
        $group = Group::factory()->create([
            'notifications_enabled' => false,
        ]);

        $group->update(['notifications_enabled' => true]);

        $this->assertDatabaseHas('nc_groups', [
            'id' => $group->id,
            'notifications_enabled' => true,
        ]);
    }

    /** @test */
    public function it_can_disable_notifications_globally_for_group()
    {
        $group = Group::factory()->create([
            'notifications_enabled' => true,
        ]);

        $group->update(['notifications_enabled' => false]);

        $this->assertDatabaseHas('nc_groups', [
            'id' => $group->id,
            'notifications_enabled' => false,
        ]);
    }

    /** @test */
    public function it_can_configure_telegram_chat_id()
    {
        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'telegram',
            'chat_id' => '123456789',
        ]);

        $this->assertDatabaseHas('nc_notification_channels', [
            'id' => $channel->id,
            'chat_id' => '123456789',
        ]);
    }

    /** @test */
    public function it_can_update_telegram_chat_id()
    {
        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'telegram',
            'chat_id' => '123456789',
        ]);

        $channel->update(['chat_id' => '987654321']);

        $this->assertDatabaseHas('nc_notification_channels', [
            'id' => $channel->id,
            'chat_id' => '987654321',
        ]);
    }

    /** @test */
    public function it_validates_telegram_chat_id_format()
    {
        $validChatIds = [
            '123456789',
            '-123456789',
            '@username',
        ];

        foreach ($validChatIds as $chatId) {
            $channel = NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'telegram',
                'chat_id' => $chatId,
            ]);

            $this->assertEquals($chatId, $channel->chat_id);
        }
    }

    /** @test */
    public function it_can_configure_notification_preferences()
    {
        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'notify_on_block' => true,
            'notify_on_recover' => true,
            'notify_on_rotation' => false,
        ]);

        $this->assertTrue($channel->notify_on_block);
        $this->assertTrue($channel->notify_on_recover);
        $this->assertFalse($channel->notify_on_rotation);
    }

    /** @test */
    public function it_can_update_notification_preferences()
    {
        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'notify_on_block' => true,
            'notify_on_recover' => false,
        ]);

        $channel->update([
            'notify_on_block' => false,
            'notify_on_recover' => true,
        ]);

        $this->assertFalse($channel->notify_on_block);
        $this->assertTrue($channel->notify_on_recover);
    }

    /** @test */
    public function it_can_test_telegram_connection()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['id' => 123]], 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'telegram',
            'is_active' => true,
        ]);

        // Simulate testing connection
        $response = Http::get("https://api.telegram.org/bot{$channel->chat_id}/getMe");

        $this->assertTrue($response->successful());
    }

    /** @test */
    public function it_stores_multiple_notification_channels_per_user()
    {
        $channels = NotificationChannel::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $userChannels = NotificationChannel::where('user_id', $this->user->id)->get();

        $this->assertCount(3, $userChannels);
    }

    /** @test */
    public function it_can_set_different_intervals_for_different_targets()
    {
        $target1 = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'check_interval' => 60,
        ]);

        $target2 = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'check_interval' => 3600,
        ]);

        $this->assertEquals(60, $target1->check_interval);
        $this->assertEquals(3600, $target2->check_interval);
    }

    /** @test */
    public function it_uses_group_interval_when_target_interval_is_null()
    {
        $group = Group::factory()->create([
            'check_interval' => 600,
        ]);

        $target = Target::factory()->create([
            'group_id' => $group->id,
            'owner_id' => $this->user->id,
            'check_interval' => null,
        ]);

        $this->assertEquals(600, $target->getEffectiveCheckInterval());
    }

    /** @test */
    public function it_can_configure_jitter_percent_for_group()
    {
        $group = Group::factory()->create([
            'jitter_percent' => 15,
        ]);

        $this->assertDatabaseHas('nc_groups', [
            'id' => $group->id,
            'jitter_percent' => 15,
        ]);
    }
}

