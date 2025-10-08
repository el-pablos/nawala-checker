<?php

namespace App\Services\NawalaChecker;

use App\Models\NawalaChecker\Target;
use App\Models\NawalaChecker\NotificationChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifierService
{
    protected string $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN', ''));
    }

    /**
     * Notify about status change.
     */
    public function notifyStatusChange(Target $target, string $oldStatus, string $newStatus): void
    {
        // Get notification channels for this target
        $channels = $this->getNotificationChannels($target);

        foreach ($channels as $channel) {
            // Check if we should notify based on status change
            if ($this->shouldNotify($channel, $oldStatus, $newStatus)) {
                $message = $this->buildStatusChangeMessage($target, $oldStatus, $newStatus);
                $this->sendTelegramMessage($channel->chat_id, $message);
            }
        }
    }

    /**
     * Test notification to a channel.
     */
    public function testNotification(int $channelId, string $message): bool
    {
        $channel = NotificationChannel::find($channelId);

        if (!$channel || !$channel->is_active) {
            return false;
        }

        $testMessage = "🔔 *Test Notification*\n\n" . $message;
        
        return $this->sendTelegramMessage($channel->chat_id, $testMessage);
    }

    /**
     * Send Telegram message.
     */
    protected function sendTelegramMessage(string $chatId, string $message): bool
    {
        if (empty($this->botToken)) {
            Log::warning('Telegram bot token not configured');
            return false;
        }

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$this->botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => true,
                ]
            );

            if ($response->successful()) {
                Log::info('Telegram notification sent', ['chat_id' => $chatId]);
                return true;
            }

            Log::error('Telegram notification failed', [
                'chat_id' => $chatId,
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Telegram notification exception', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get notification channels for a target.
     */
    protected function getNotificationChannels(Target $target)
    {
        $channels = collect();

        // User-specific channels
        if ($target->owner_id) {
            $userChannels = NotificationChannel::active()
                ->where('user_id', $target->owner_id)
                ->get();
            $channels = $channels->merge($userChannels);
        }

        // Group-specific channels
        if ($target->group_id) {
            $groupChannels = NotificationChannel::active()
                ->where('group_id', $target->group_id)
                ->get();
            $channels = $channels->merge($groupChannels);
        }

        return $channels->unique('id');
    }

    /**
     * Check if we should notify based on status change.
     */
    protected function shouldNotify(NotificationChannel $channel, string $oldStatus, string $newStatus): bool
    {
        $blockedStatuses = ['DNS_FILTERED', 'HTTP_BLOCKPAGE', 'HTTPS_SNI_BLOCK', 'RST'];

        $wasBlocked = in_array($oldStatus, $blockedStatuses);
        $isBlocked = in_array($newStatus, $blockedStatuses);

        // Notify on block
        if (!$wasBlocked && $isBlocked && $channel->notify_on_block) {
            return true;
        }

        // Notify on recover
        if ($wasBlocked && !$isBlocked && $channel->notify_on_recover) {
            return true;
        }

        return false;
    }

    /**
     * Build status change message.
     */
    protected function buildStatusChangeMessage(Target $target, string $oldStatus, string $newStatus): string
    {
        $emoji = $this->getStatusEmoji($newStatus);
        $statusText = $this->getStatusText($newStatus);

        $message = "{$emoji} *Status Change Alert*\n\n";
        $message .= "*Target:* `{$target->domain_or_url}`\n";
        $message .= "*Old Status:* {$this->getStatusText($oldStatus)}\n";
        $message .= "*New Status:* {$statusText}\n";
        $message .= "*Time:* " . now()->format('Y-m-d H:i:s') . "\n";

        if ($target->notes) {
            $message .= "\n*Notes:* {$target->notes}";
        }

        return $message;
    }

    /**
     * Get emoji for status.
     */
    protected function getStatusEmoji(string $status): string
    {
        return match ($status) {
            'OK' => '✅',
            'DNS_FILTERED' => '🚫',
            'HTTP_BLOCKPAGE' => '⛔',
            'HTTPS_SNI_BLOCK' => '🔒',
            'TIMEOUT' => '⏱️',
            'RST' => '❌',
            default => '❓',
        };
    }

    /**
     * Get human-readable status text.
     */
    protected function getStatusText(string $status): string
    {
        return match ($status) {
            'OK' => '✅ OK',
            'DNS_FILTERED' => '🚫 DNS Filtered',
            'HTTP_BLOCKPAGE' => '⛔ HTTP Block Page',
            'HTTPS_SNI_BLOCK' => '🔒 HTTPS SNI Block',
            'TIMEOUT' => '⏱️ Timeout',
            'RST' => '❌ Connection Reset',
            'INCONCLUSIVE' => '❓ Inconclusive',
            default => '❓ Unknown',
        };
    }
}

