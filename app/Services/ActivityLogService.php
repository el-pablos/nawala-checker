<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Log an activity
     */
    public function log(string $action, string $model, int $modelId, array $data = []): void
    {
        $user = Auth::user();
        
        $logData = [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('activity')->info($action, $logData);
    }

    /**
     * Log target creation
     */
    public function logTargetCreated(int $targetId, array $data): void
    {
        $this->log('target.created', 'Target', $targetId, $data);
    }

    /**
     * Log target update
     */
    public function logTargetUpdated(int $targetId, array $changes): void
    {
        $this->log('target.updated', 'Target', $targetId, $changes);
    }

    /**
     * Log target deletion
     */
    public function logTargetDeleted(int $targetId): void
    {
        $this->log('target.deleted', 'Target', $targetId);
    }

    /**
     * Log check execution
     */
    public function logCheckExecuted(int $targetId, array $result): void
    {
        $this->log('check.executed', 'Target', $targetId, [
            'status' => $result['status'] ?? 'unknown',
            'confidence' => $result['confidence'] ?? 0,
        ]);
    }

    /**
     * Log shortlink creation
     */
    public function logShortlinkCreated(int $shortlinkId, array $data): void
    {
        $this->log('shortlink.created', 'Shortlink', $shortlinkId, $data);
    }

    /**
     * Log shortlink rotation
     */
    public function logShortlinkRotated(int $shortlinkId, array $data): void
    {
        $this->log('shortlink.rotated', 'Shortlink', $shortlinkId, $data);
    }

    /**
     * Log shortlink rollback
     */
    public function logShortlinkRollback(int $shortlinkId, array $data): void
    {
        $this->log('shortlink.rollback', 'Shortlink', $shortlinkId, $data);
    }

    /**
     * Log shortlink deletion
     */
    public function logShortlinkDeleted(int $shortlinkId): void
    {
        $this->log('shortlink.deleted', 'Shortlink', $shortlinkId);
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $event, array $data = []): void
    {
        $user = Auth::user();
        
        $logData = [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'event' => $event,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('security')->warning($event, $logData);
    }

    /**
     * Log rate limit exceeded
     */
    public function logRateLimitExceeded(string $action): void
    {
        $this->logSecurityEvent('rate_limit.exceeded', [
            'action' => $action,
        ]);
    }

    /**
     * Log unauthorized access attempt
     */
    public function logUnauthorizedAccess(string $resource): void
    {
        $this->logSecurityEvent('unauthorized.access', [
            'resource' => $resource,
        ]);
    }

    /**
     * Log suspicious input detected
     */
    public function logSuspiciousInput(string $field, string $value): void
    {
        $this->logSecurityEvent('suspicious.input', [
            'field' => $field,
            'value' => substr($value, 0, 100), // Limit length
        ]);
    }
}

