<?php

namespace App\Services\NawalaChecker;

use App\Models\NawalaChecker\Shortlink;
use App\Models\NawalaChecker\ShortlinkTarget;
use App\Models\NawalaChecker\RotationHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShortlinkRotationService
{
    /**
     * Rotate shortlink to next available target.
     */
    public function rotate(Shortlink $shortlink, ?int $userId = null, string $reason = 'auto_rotation'): bool
    {
        return DB::transaction(function () use ($shortlink, $userId, $reason) {
            // Check cooldown
            if ($this->isInCooldown($shortlink)) {
                Log::info('Shortlink rotation skipped due to cooldown', ['shortlink_id' => $shortlink->id]);
                return false;
            }

            // Get next available target
            $nextTarget = $this->getNextAvailableTarget($shortlink);

            if (!$nextTarget) {
                Log::warning('No available target for rotation', ['shortlink_id' => $shortlink->id]);
                return false;
            }

            $currentTargetId = $shortlink->current_target_id;

            // Update shortlink
            $shortlink->update([
                'current_target_id' => $nextTarget->id,
                'last_rotated_at' => now(),
                'rotation_count' => $shortlink->rotation_count + 1,
            ]);

            // Record rotation history
            RotationHistory::create([
                'shortlink_id' => $shortlink->id,
                'from_target_id' => $currentTargetId,
                'to_target_id' => $nextTarget->id,
                'reason' => $reason,
                'triggered_by' => $userId,
                'rotated_at' => now(),
            ]);

            Log::info('Shortlink rotated successfully', [
                'shortlink_id' => $shortlink->id,
                'from' => $currentTargetId,
                'to' => $nextTarget->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Rollback shortlink to original target.
     */
    public function rollback(Shortlink $shortlink, ?int $userId = null): bool
    {
        if (!$shortlink->original_target_id) {
            return false;
        }

        return DB::transaction(function () use ($shortlink, $userId) {
            $currentTargetId = $shortlink->current_target_id;

            $shortlink->update([
                'current_target_id' => $shortlink->original_target_id,
                'last_rotated_at' => now(),
            ]);

            RotationHistory::create([
                'shortlink_id' => $shortlink->id,
                'from_target_id' => $currentTargetId,
                'to_target_id' => $shortlink->original_target_id,
                'reason' => 'rollback',
                'triggered_by' => $userId,
                'rotated_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Check if shortlink should be rotated based on target status.
     */
    public function shouldRotate(Shortlink $shortlink): bool
    {
        if (!$shortlink->group || !$shortlink->currentTarget) {
            return false;
        }

        $group = $shortlink->group;
        $currentTarget = $shortlink->currentTarget;

        // Check if current target is failing
        if ($currentTarget->current_status === 'OK') {
            return false;
        }

        // Check if we're in cooldown
        if ($this->isInCooldown($shortlink)) {
            return false;
        }

        // Check if there are alternative targets
        $alternativeTargets = $this->getAvailableTargets($shortlink);
        if ($alternativeTargets->isEmpty()) {
            return false;
        }

        return true;
    }

    /**
     * Auto-rotate shortlinks that need rotation.
     */
    public function autoRotateAll(): int
    {
        $rotatedCount = 0;

        $shortlinks = Shortlink::active()
            ->with(['group', 'currentTarget'])
            ->get();

        foreach ($shortlinks as $shortlink) {
            if ($this->shouldRotate($shortlink)) {
                if ($this->rotate($shortlink, null, 'auto_rotation')) {
                    $rotatedCount++;
                }
            }
        }

        return $rotatedCount;
    }

    /**
     * Check if shortlink is in cooldown period.
     */
    protected function isInCooldown(Shortlink $shortlink): bool
    {
        if (!$shortlink->group || !$shortlink->last_rotated_at) {
            return false;
        }

        $cooldownSeconds = $shortlink->group->cooldown_seconds;
        $lastRotated = $shortlink->last_rotated_at;

        return $lastRotated->addSeconds($cooldownSeconds)->isFuture();
    }

    /**
     * Get next available target for rotation.
     */
    protected function getNextAvailableTarget(Shortlink $shortlink): ?ShortlinkTarget
    {
        $availableTargets = $this->getAvailableTargets($shortlink);

        if ($availableTargets->isEmpty()) {
            return null;
        }

        // Return highest priority target
        return $availableTargets->first();
    }

    /**
     * Get available targets for shortlink (excluding current).
     */
    protected function getAvailableTargets(Shortlink $shortlink)
    {
        return ShortlinkTarget::where('shortlink_id', $shortlink->id)
            ->where('id', '!=', $shortlink->current_target_id)
            ->active()
            ->where('current_status', 'OK')
            ->byPriority()
            ->get();
    }
}

