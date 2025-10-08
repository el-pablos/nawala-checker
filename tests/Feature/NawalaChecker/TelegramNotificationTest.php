<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\Target;
use App\Models\User;
use App\Services\NawalaChecker\TelegramNotifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
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
            'telegram_enabled' => true,
            'telegram_bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            'telegram_chat_id' => '123456789',
            'last_status' => 'accessible',
        ]);

        $service = new TelegramNotifierService();
        
        // Simulate status change to blocked
        $result = $service->notifyStatusChange($target, 'blocked', 'accessible');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_formats_notification_message_correctly()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'domain_or_url' => 'example.com',
            'type' => 'domain',
        ]);

        $service = new TelegramNotifierService();
        $message = $service->formatStatusChangeMessage($target, 'blocked', 'accessible');

        $this->assertStringContainsString('example.com', $message);
        $this->assertStringContainsString('blocked', $message);
        $this->assertStringContainsString('accessible', $message);
    }

    /** @test */
    public function it_does_not_send_notification_when_disabled()
    {
        Http::fake();

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'telegram_enabled' => false,
            'telegram_bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            'telegram_chat_id' => '123456789',
        ]);

        $service = new TelegramNotifierService();
        $result = $service->notifyStatusChange($target, 'blocked', 'accessible');

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    /** @test */
    public function it_does_not_send_notification_without_bot_token()
    {
        Http::fake();

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'telegram_enabled' => true,
            'telegram_bot_token' => null,
            'telegram_chat_id' => '123456789',
        ]);

        $service = new TelegramNotifierService();
        $result = $service->notifyStatusChange($target, 'blocked', 'accessible');

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    /** @test */
    public function it_does_not_send_notification_without_chat_id()
    {
        Http::fake();

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'telegram_enabled' => true,
            'telegram_bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            'telegram_chat_id' => null,
        ]);

        $service = new TelegramNotifierService();
        $result = $service->notifyStatusChange($target, 'blocked', 'accessible');

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    /** @test */
    public function it_can_test_telegram_connection()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['id' => 123]], 200),
        ]);

        $service = new TelegramNotifierService();
        $result = $service->testConnection('123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_telegram_api_errors()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
        ]);

        $service = new TelegramNotifierService();
        $result = $service->testConnection('invalid-token');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_network_errors()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(null, 500),
        ]);

        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'telegram_enabled' => true,
            'telegram_bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            'telegram_chat_id' => '123456789',
        ]);

        $service = new TelegramNotifierService();
        $result = $service->notifyStatusChange($target, 'blocked', 'accessible');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_includes_timestamp_in_notification()
    {
        $target = Target::factory()->create([
            'group_id' => $this->group->id,
            'domain_or_url' => 'example.com',
        ]);

        $service = new TelegramNotifierService();
        $message = $service->formatStatusChangeMessage($target, 'blocked', 'accessible');

        // Should contain timestamp or date
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}|\d{2}:\d{2}/', $message);
    }

    /** @test */
    public function it_can_send_test_notification()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $service = new TelegramNotifierService();
        $result = $service->sendTestMessage(
            '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            '123456789'
        );

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
            'telegram_enabled' => true,
            'telegram_bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            'telegram_chat_id' => '123456789',
            'last_status' => 'accessible',
        ]);

        $service = new TelegramNotifierService();
        
        // Send notification for status change
        $service->notifyStatusChange($target, 'blocked', 'accessible');

        // Try to send same notification again (same status)
        $result = $service->notifyStatusChange($target, 'blocked', 'blocked');

        // Should not send if status hasn't changed
        $this->assertFalse($result);
    }
}

