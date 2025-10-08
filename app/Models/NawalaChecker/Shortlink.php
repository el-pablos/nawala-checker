<?php

namespace App\Models\NawalaChecker;

use App\Models\Traits\LogsActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shortlink extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'nc_shortlinks';

    protected $fillable = [
        'slug',
        'group_id',
        'current_target_id',
        'original_target_id',
        'is_active',
        'last_rotated_at',
        'rotation_count',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rotation_count' => 'integer',
        'last_rotated_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the group this shortlink belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ShortlinkGroup::class, 'group_id');
    }

    /**
     * Get the current target.
     */
    public function currentTarget(): BelongsTo
    {
        return $this->belongsTo(ShortlinkTarget::class, 'current_target_id');
    }

    /**
     * Get the original target.
     */
    public function originalTarget(): BelongsTo
    {
        return $this->belongsTo(ShortlinkTarget::class, 'original_target_id');
    }

    /**
     * Get the user who created this shortlink.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all targets for this shortlink.
     */
    public function targets(): HasMany
    {
        return $this->hasMany(ShortlinkTarget::class, 'shortlink_id');
    }

    /**
     * Get the rotation history for this shortlink.
     */
    public function rotationHistory(): HasMany
    {
        return $this->hasMany(RotationHistory::class, 'shortlink_id');
    }

    /**
     * Scope a query to only include active shortlinks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

