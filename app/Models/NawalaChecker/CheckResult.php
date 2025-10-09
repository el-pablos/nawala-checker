<?php

namespace App\Models\NawalaChecker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckResult extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'nc_check_results';

    protected $fillable = [
        'target_id',
        'resolver_id',
        'vantage_node_id',
        'status',
        'response_time_ms',
        'resolved_ip',
        'http_status_code',
        'error_message',
        'raw_response',
        'resolver_results',
        'confidence',
        'checked_at',
    ];

    protected $casts = [
        'response_time_ms' => 'integer',
        'http_status_code' => 'integer',
        'confidence' => 'integer',
        'raw_response' => 'array',
        'resolver_results' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Get the target this result belongs to.
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(Target::class, 'target_id');
    }

    /**
     * Get the resolver used for this check.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(Resolver::class, 'resolver_id');
    }

    /**
     * Get the vantage node used for this check.
     */
    public function vantageNode(): BelongsTo
    {
        return $this->belongsTo(VantageNode::class, 'vantage_node_id');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to get recent results.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('checked_at', '>=', now()->subHours($hours));
    }
}

