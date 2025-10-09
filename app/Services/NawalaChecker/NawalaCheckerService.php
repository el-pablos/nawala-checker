<?php

namespace App\Services\NawalaChecker;

use App\Models\NawalaChecker\Target;
use App\Models\NawalaChecker\CheckResult;
use App\Models\NawalaChecker\Shortlink;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NawalaCheckerService
{
    public function __construct(
        protected CheckRunnerService $checkRunner,
        protected ShortlinkRotationService $rotationService,
        protected TelegramNotifierService $notifierService
    ) {}

    /**
     * Get paginated targets with filters.
     */
    public function getTargets(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Target::with(['owner', 'group', 'tags', 'latestCheckResult'])
            ->select([
                'id', 'domain_or_url', 'type', 'group_id', 'owner_id',
                'enabled', 'current_status', 'last_checked_at',
                'last_status_change_at', 'consecutive_failures', 'created_at'
            ]);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where('domain_or_url', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (isset($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        if (isset($filters['status'])) {
            $query->where('current_status', $filters['status']);
        }

        if (isset($filters['enabled'])) {
            $query->where('enabled', $filters['enabled']);
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Create a new target.
     */
    public function createTarget(array $data): Target
    {
        return DB::transaction(function () use ($data) {
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $target = Target::create($data);

            if (!empty($tags)) {
                $target->tags()->sync($tags);
            }

            return $target->load(['owner', 'group', 'tags']);
        });
    }

    /**
     * Update a target.
     */
    public function updateTarget(Target $target, array $data): Target
    {
        return DB::transaction(function () use ($target, $data) {
            $tags = $data['tags'] ?? null;
            unset($data['tags']);

            $target->update($data);

            if ($tags !== null) {
                $target->tags()->sync($tags);
            }

            return $target->load(['owner', 'group', 'tags']);
        });
    }

    /**
     * Delete a target.
     */
    public function deleteTarget(Target $target): bool
    {
        return $target->delete();
    }

    /**
     * Run check now for a target.
     */
    public function runCheckNow(Target $target): array
    {
        return $this->checkRunner->checkTarget($target);
    }

    /**
     * Get check results for a target.
     */
    public function getCheckResults(Target $target, int $hours = 24): Collection
    {
        return CheckResult::where('target_id', $target->id)
            ->where('checked_at', '>=', now()->subHours($hours))
            ->orderBy('checked_at', 'desc')
            ->get();
    }

    /**
     * Get statistics for a target.
     */
    public function getTargetStatistics(Target $target, int $days = 7): array
    {
        $results = CheckResult::where('target_id', $target->id)
            ->where('checked_at', '>=', now()->subDays($days))
            ->get();

        $statusCounts = $results->groupBy('status')->map->count();
        $avgResponseTime = $results->avg('response_time_ms');
        $accessibleCount = $results->where('status', 'OK')->count();
        $blockedStatuses = ['DNS_FILTERED', 'HTTP_BLOCKPAGE', 'HTTPS_SNI_BLOCK'];
        $blockedCount = $results->whereIn('status', $blockedStatuses)->count();
        $uptime = $accessibleCount / max($results->count(), 1) * 100;

        return [
            'total_checks' => $results->count(),
            'status_counts' => $statusCounts,
            'accessible_count' => $accessibleCount,
            'blocked_count' => $blockedCount,
            'avg_response_time' => round($avgResponseTime, 2),
            'uptime_percentage' => round($uptime, 2),
        ];
    }

    /**
     * Get paginated shortlinks.
     */
    public function getShortlinks(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Shortlink::with(['group', 'currentTarget', 'creator'])
            ->select([
                'id', 'slug', 'group_id', 'current_target_id',
                'is_active', 'last_rotated_at', 'rotation_count', 'created_at'
            ]);

        if (!empty($filters['search'])) {
            $query->where('slug', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Rotate shortlink to next available target.
     */
    public function rotateShortlink(Shortlink $shortlink, ?int $userId = null, string $reason = 'manual'): bool
    {
        return $this->rotationService->rotate($shortlink, $userId, $reason);
    }

    /**
     * Rollback shortlink to original target.
     */
    public function rollbackShortlink(Shortlink $shortlink, ?int $userId = null): bool
    {
        return $this->rotationService->rollback($shortlink, $userId);
    }

    /**
     * Test telegram notification.
     */
    public function testTelegramNotification(int $channelId, string $message): bool
    {
        return $this->notifierService->testNotification($channelId, $message);
    }

    /**
     * Send notification for status change.
     */
    public function notifyStatusChange(Target $target, string $oldStatus, string $newStatus): void
    {
        $this->notifierService->notifyStatusChange($target, $oldStatus, $newStatus);
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(?int $userId = null): array
    {
        $query = Target::query();
        
        if ($userId) {
            $query->where('owner_id', $userId);
        }

        $totalTargets = $query->count();
        $enabledTargets = (clone $query)->where('enabled', true)->count();
        $blockedTargets = (clone $query)->whereIn('current_status', [
            'DNS_FILTERED', 'HTTP_BLOCKPAGE', 'HTTPS_SNI_BLOCK'
        ])->count();
        $okTargets = (clone $query)->where('current_status', 'OK')->count();

        // Calculate percentages
        $accessiblePercentage = $totalTargets > 0 ? round(($okTargets / $totalTargets) * 100) : 0;
        $blockedPercentage = $totalTargets > 0 ? round(($blockedTargets / $totalTargets) * 100) : 0;

        return [
            'total_targets' => $totalTargets,
            'enabled_targets' => $enabledTargets,
            'blocked_targets' => $blockedTargets,
            'blocked_count' => $blockedTargets, // Alias for tests
            'ok_targets' => $okTargets,
            'accessible_count' => $okTargets, // Alias for tests
            'unknown_targets' => $totalTargets - $blockedTargets - $okTargets,
            'accessible_percentage' => $accessiblePercentage,
            'blocked_percentage' => $blockedPercentage,
        ];
    }
}

