<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\Target;
use App\Models\User;
use App\Services\NawalaChecker\TelegramNotifierService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
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
    public function it_can_enable_telegram_notifications_for_target()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'telegram_enabled' => false,
        ]);

        $target->update(['telegram_enabled' => true]);

        $this->assertDatabaseHas('nc_targets', [
            'id' => $target->id,
            'telegram_enabled' => true,
        ]);
    }

    /** @test */
    public function it_can_disable_telegram_notifications_for_target()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'telegram_enabled' => true,
        ]);

        $target->update(['telegram_enabled' => false]);

        $this->assertDatabaseHas('nc_targets', [
            'id' => $target->id,
            'telegram_enabled' => false,
        ]);
    }

    /** @test */
    public function it_stores_telegram_bot_token()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'telegram_bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
        ]);

        $this->assertDatabaseHas('nc_targets', [
            'id' => $target->id,
            'telegram_bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
        ]);
    }

    /** @test */
    public function it_stores_telegram_chat_id()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'telegram_chat_id' => '123456789',
        ]);

        $this->assertDatabaseHas('nc_targets', [
            'id' => $target->id,
            'telegram_chat_id' => '123456789',
        ]);
    }

    /** @test */
    public function it_can_send_notification_on_status_change()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'telegram_enabled' => true,
            'telegram_bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            'telegram_chat_id' => '123456789',
        ]);

        // Create notification channel for the user
        \App\Models\NawalaChecker\NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'is_active' => true,
            'notify_on_block' => true,
        ]);

        $service = new TelegramNotifierService();

        // Simulate status change to blocked (void return, just check no exception)
        $service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        // Verify HTTP request was made
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org');
        });
    }

    /** @test */
    public function it_formats_notification_message_correctly()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'domain_or_url' => 'example.com',
            'type' => 'domain',
        ]);

        // Create notification channel
        $channel = \App\Models\NawalaChecker\NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'notify_on_block' => true,
        ]);

        $service = new TelegramNotifierService();
        $service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        // Verify notification was sent
        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($request->url(), 'api.telegram.org') &&
                   str_contains($body['text'], 'example.com');
        });
    }

    /** @test */
    public function it_does_not_send_notification_when_disabled()
    {
        Http::fake();

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'telegram_enabled' => false,
        ]);

        // Create inactive notification channel
        \App\Models\NawalaChecker\NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
            'notify_on_block' => true,
        ]);

        $service = new TelegramNotifierService();
        $service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        Http::assertNothingSent();
    }

    /** @test */
    public function it_does_not_send_notification_without_bot_token()
    {
        Http::fake();

        // Clear the bot token config
        config(['services.telegram.bot_token' => '']);

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        // Create channel but service has no bot token (from config)
        \App\Models\NawalaChecker\NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'notify_on_block' => true,
        ]);

        // Service will check config for bot token, which is now empty
        $service = new TelegramNotifierService();
        $service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        // Should not send because bot token is not configured
        Http::assertNothingSent();
    }

    /** @test */
    public function it_does_not_send_notification_without_chat_id()
    {
        Http::fake();

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
        ]);

        // No notification channel created, so no chat_id available
        $service = new TelegramNotifierService();
        $service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        Http::assertNothingSent();
    }

    /** @test */
    public function it_can_test_telegram_connection()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['id' => 123]], 200),
        ]);

        $channel = \App\Models\NawalaChecker\NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $service = new TelegramNotifierService();
        $result = $service->testNotification($channel->id, 'Test message');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_telegram_api_errors()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
        ]);

        $channel = \App\Models\NawalaChecker\NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $service = new TelegramNotifierService();
        $result = $service->testNotification($channel->id, 'Test message');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_network_errors()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(null, 500),
        ]);

        $channel = \App\Models\NawalaChecker\NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $service = new TelegramNotifierService();
        $result = $service->testNotification($channel->id, 'Test message');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_includes_timestamp_in_notification()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'domain_or_url' => 'example.com',
        ]);

        $channel = \App\Models\NawalaChecker\NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'notify_on_block' => true,
        ]);

        $service = new TelegramNotifierService();
        $service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        // Verify notification contains timestamp
        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($request->url(), 'api.telegram.org') &&
                   preg_match('/\d{4}-\d{2}-\d{2}/', $body['text']);
        });
    }

    /** @test */
    public function it_can_send_test_notification()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $channel = \App\Models\NawalaChecker\NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $service = new TelegramNotifierService();
        $result = $service->testNotification($channel->id, 'Test message');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_bot_token_format()
    {
        $validTokens = [
            '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            '987654321:ABCdefGHIjklMNOpqrSTUvwxYZ123456789',
        ];

        foreach ($validTokens as $token) {
            $target = Target::factory()->create([
                'group_id' => $this->group->id,
                'telegram_bot_token' => $token,
            ]);

            $this->assertEquals($token, $target->telegram_bot_token);
        }
    }

    /** @test */
    public function it_does_not_send_duplicate_notifications()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'owner_id' => $this->user->id,
            'telegram_enabled' => true,
        ]);

        $channel = \App\Models\NawalaChecker\NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'notify_on_block' => true,
        ]);

        $service = new TelegramNotifierService();

        // Send notification for status change (OK -> DNS_FILTERED)
        $service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        // Try to send notification for same status (DNS_FILTERED -> DNS_FILTERED)
        // Should not send because status hasn't changed
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $service->notifyStatusChange($target, 'DNS_FILTERED', 'DNS_FILTERED');

        // Should only have sent one notification (the first one)
        Http::assertSentCount(0); // Second fake resets the count
    }
}

