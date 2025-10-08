<?php

namespace App\Models\NawalaChecker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortlinkGroup extends Model
{
    use HasFactory;

    protected $table = 'nc_shortlink_groups';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'rotation_threshold',
        'cooldown_seconds',
        'min_confidence',
        'auto_rollback',
        'created_by',
    ];

    protected $casts = [
        'rotation_threshold' => 'integer',
        'cooldown_seconds' => 'integer',
        'min_confidence' => 'integer',
        'auto_rollback' => 'boolean',
    ];

    /**
     * Get the user who created this group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the shortlinks in this group.
     */
    public function shortlinks(): HasMany
    {
        return $this->hasMany(Shortlink::class, 'group_id');
    }
}

