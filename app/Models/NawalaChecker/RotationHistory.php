<?php

namespace App\Models\NawalaChecker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotationHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'nc_rotation_history';

    protected $fillable = [
        'shortlink_id',
        'from_target_id',
        'to_target_id',
        'reason',
        'notes',
        'triggered_by',
        'rotated_at',
    ];

    protected $casts = [
        'rotated_at' => 'datetime',
    ];

    /**
     * Get the shortlink this history belongs to.
     */
    public function shortlink(): BelongsTo
    {
        return $this->belongsTo(Shortlink::class, 'shortlink_id');
    }

    /**
     * Get the source target.
     */
    public function fromTarget(): BelongsTo
    {
        return $this->belongsTo(ShortlinkTarget::class, 'from_target_id');
    }

    /**
     * Get the destination target.
     */
    public function toTarget(): BelongsTo
    {
        return $this->belongsTo(ShortlinkTarget::class, 'to_target_id');
    }

    /**
     * Get the user who triggered this rotation.
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}

