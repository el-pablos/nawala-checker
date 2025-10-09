<?php

namespace App\Models\NawalaChecker;

use App\Models\Traits\LogsActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Target extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'nc_targets';

    protected $fillable = [
        'domain_or_url',
        'type',
        'group_id',
        'owner_id',
        'enabled',
        'check_interval',
        'current_status',
        'last_checked_at',
        'last_status_change_at',
        'consecutive_failures',
        'notes',
        'metadata',
        'telegram_enabled',
        'telegram_bot_token',
        'telegram_chat_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'check_interval' => 'integer',
        'consecutive_failures' => 'integer',
        'last_checked_at' => 'datetime',
        'last_status_change_at' => 'datetime',
        'metadata' => 'array',
        'telegram_enabled' => 'boolean',
    ];

    /**
     * Get the owner of this target.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the group this target belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * Get the tags for this target.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'nc_target_tag', 'target_id', 'tag_id');
    }

    /**
     * Get the check results for this target.
     */
    public function checkResults(): HasMany
    {
        return $this->hasMany(CheckResult::class, 'target_id');
    }

    /**
     * Get the latest check result.
     */
    public function latestCheckResult()
    {
        return $this->hasOne(CheckResult::class, 'target_id')->latestOfMany('checked_at');
    }

    /**
     * Scope a query to only include enabled targets.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('current_status', $status);
    }

    /**
     * Scope a query to filter by owner.
     */
    public function scopeByOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    /**
     * Get the effective check interval (target-specific or group default).
     */
    public function getEffectiveCheckInterval(): int
    {
        if ($this->check_interval) {
            return $this->check_interval;
        }

        if ($this->group && $this->group->default_check_interval) {
            return $this->group->default_check_interval;
        }

        return 300; // default 5 minutes
    }
}

