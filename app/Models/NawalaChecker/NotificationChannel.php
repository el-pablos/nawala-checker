<?php

namespace App\Models\NawalaChecker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationChannel extends Model
{
    use HasFactory;

    protected $table = 'nc_notification_channels';

    protected $fillable = [
        'name',
        'type',
        'chat_id',
        'user_id',
        'group_id',
        'is_active',
        'notify_on_block',
        'notify_on_recover',
        'notify_on_rotation',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'notify_on_block' => 'boolean',
        'notify_on_recover' => 'boolean',
        'notify_on_rotation' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the user this channel belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the group this channel belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * Scope a query to only include active channels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

