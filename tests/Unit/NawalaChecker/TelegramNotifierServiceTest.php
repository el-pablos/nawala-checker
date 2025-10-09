<?php

namespace Tests\Unit\NawalaChecker;

use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\NotificationChannel;
use App\Models\NawalaChecker\Target;
use App\Models\User;
use App\Services\NawalaChecker\TelegramNotifierService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotifierServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected TelegramNotifierService $service;
    protected User $user;
    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TelegramNotifierService();
        $this->user = User::factory()->create();
        $this->group = Group::factory()->create();
    }

    /** @test */
    public function it_sends_notification_on_status_change_to_blocked()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
        ]);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'is_active' => true,
            'notify_on_block' => true,
        ]);

        $this->service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org');
        });
    }

    /** @test */
    public function it_sends_notification_on_status_change_to_recovered()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
        ]);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'is_active' => true,
            'notify_on_recover' => true,
        ]);

        $this->service->notifyStatusChange($target, 'DNS_FILTERED', 'OK');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org');
        });
    }

    /** @test */
    public function it_does_not_send_notification_when_channel_is_inactive()
    {
        Http::fake();

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
        ]);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'is_active' => false,
        ]);

        $this->service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        Http::assertNothingSent();
    }

    /** @test */
    public function it_does_not_send_notification_when_notify_on_block_is_disabled()
    {
        Http::fake();

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
        ]);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'is_active' => true,
            'notify_on_block' => false,
        ]);

        $this->service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        Http::assertNothingSent();
    }

    /** @test */
    public function it_does_not_send_notification_when_notify_on_recover_is_disabled()
    {
        Http::fake();

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
        ]);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'is_active' => true,
            'notify_on_recover' => false,
        ]);

        $this->service->notifyStatusChange($target, 'DNS_FILTERED', 'OK');

        Http::assertNothingSent();
    }

    /** @test */
    public function it_builds_correct_status_change_message()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'domain_or_url' => 'example.com',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildStatusChangeMessage');
        $method->setAccessible(true);

        $message = $method->invoke($this->service, $target, 'OK', 'DNS_FILTERED');

        $this->assertStringContainsString('example.com', $message);
        $this->assertStringContainsString('Status Change Alert', $message);
        $this->assertStringContainsString('OK', $message);
        $this->assertStringContainsString('DNS Filtered', $message);
    }

    /** @test */
    public function it_includes_timestamp_in_message()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildStatusChangeMessage');
        $method->setAccessible(true);

        $message = $method->invoke($this->service, $target, 'OK', 'DNS_FILTERED');

        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $message);
    }

    /** @test */
    public function it_includes_notes_in_message_when_available()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'notes' => 'Important website',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildStatusChangeMessage');
        $method->setAccessible(true);

        $message = $method->invoke($this->service, $target, 'OK', 'DNS_FILTERED');

        $this->assertStringContainsString('Important website', $message);
    }

    /** @test */
    public function it_returns_correct_emoji_for_status()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getStatusEmoji');
        $method->setAccessible(true);

        $this->assertEquals('✅', $method->invoke($this->service, 'OK'));
        $this->assertEquals('🚫', $method->invoke($this->service, 'DNS_FILTERED'));
        $this->assertEquals('⛔', $method->invoke($this->service, 'HTTP_BLOCKPAGE'));
        $this->assertEquals('🔒', $method->invoke($this->service, 'HTTPS_SNI_BLOCK'));
        $this->assertEquals('⏱️', $method->invoke($this->service, 'TIMEOUT'));
        $this->assertEquals('❌', $method->invoke($this->service, 'RST'));
        $this->assertEquals('❓', $method->invoke($this->service, 'UNKNOWN'));
    }

    /** @test */
    public function it_returns_correct_status_text()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getStatusText');
        $method->setAccessible(true);

        $this->assertStringContainsString('OK', $method->invoke($this->service, 'OK'));
        $this->assertStringContainsString('DNS Filtered', $method->invoke($this->service, 'DNS_FILTERED'));
        $this->assertStringContainsString('HTTP Block Page', $method->invoke($this->service, 'HTTP_BLOCKPAGE'));
        $this->assertStringContainsString('HTTPS SNI Block', $method->invoke($this->service, 'HTTPS_SNI_BLOCK'));
        $this->assertStringContainsString('Timeout', $method->invoke($this->service, 'TIMEOUT'));
        $this->assertStringContainsString('Connection Reset', $method->invoke($this->service, 'RST'));
    }

    /** @test */
    public function it_can_test_notification()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $result = $this->service->testNotification($channel->id, 'Test message');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_fails_test_notification_for_inactive_channel()
    {
        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $result = $this->service->testNotification($channel->id, 'Test message');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_telegram_api_errors_gracefully()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
        ]);

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
        ]);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'is_active' => true,
            'notify_on_block' => true,
        ]);

        // Should not throw exception
        $this->service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    /** @test */
    public function it_handles_network_timeout_gracefully()
    {
        Http::fake([
            'api.telegram.org/*' => function () {
                throw new \Exception('Connection timeout');
            },
        ]);

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
        ]);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'is_active' => true,
            'notify_on_block' => true,
        ]);

        // Should not throw exception
        $this->service->notifyStatusChange($target, 'OK', 'DNS_FILTERED');

        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    /** @test */
    public function it_determines_should_notify_correctly()
    {
        $channel = NotificationChannel::factory()->create([
            'notify_on_block' => true,
            'notify_on_recover' => true,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('shouldNotify');
        $method->setAccessible(true);

        // Should notify on block
        $this->assertTrue($method->invoke($this->service, $channel, 'OK', 'DNS_FILTERED'));

        // Should notify on recover
        $this->assertTrue($method->invoke($this->service, $channel, 'DNS_FILTERED', 'OK'));

        // Should not notify when status doesn't change
        $this->assertFalse($method->invoke($this->service, $channel, 'OK', 'OK'));
        $this->assertFalse($method->invoke($this->service, $channel, 'DNS_FILTERED', 'HTTP_BLOCKPAGE'));
    }
}

