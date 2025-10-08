<?php

namespace App\Models\NawalaChecker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortlinkTarget extends Model
{
    use HasFactory;

    protected $table = 'nc_shortlink_targets';

    protected $fillable = [
        'shortlink_id',
        'url',
        'priority',
        'weight',
        'is_active',
        'current_status',
        'last_checked_at',
        'metadata',
    ];

    protected $casts = [
        'priority' => 'integer',
        'weight' => 'integer',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the shortlink this target belongs to.
     */
    public function shortlink(): BelongsTo
    {
        return $this->belongsTo(Shortlink::class, 'shortlink_id');
    }

    /**
     * Scope a query to only include active targets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority')->orderBy('weight', 'desc');
    }
}

