<?php

namespace App\Models\NawalaChecker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $table = 'nc_groups';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'check_interval',
        'default_check_interval',
        'jitter_percent',
        'notifications_enabled',
        'created_by',
    ];

    protected $casts = [
        'check_interval' => 'integer',
        'jitter_percent' => 'integer',
        'notifications_enabled' => 'boolean',
    ];

    /**
     * Get the user who created this group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the targets in this group.
     */
    public function targets(): HasMany
    {
        return $this->hasMany(Target::class, 'group_id');
    }

    /**
     * Get the notification channels for this group.
     */
    public function notificationChannels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class, 'group_id');
    }
}

