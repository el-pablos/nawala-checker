<?php

namespace App\Models\NawalaChecker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VantageNode extends Model
{
    use HasFactory;

    protected $table = 'nc_vantage_nodes';

    protected $fillable = [
        'name',
        'location',
        'endpoint_url',
        'api_key',
        'is_active',
        'weight',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'weight' => 'integer',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'api_key',
    ];

    /**
     * Get the check results from this vantage node.
     */
    public function checkResults(): HasMany
    {
        return $this->hasMany(CheckResult::class, 'vantage_node_id');
    }

    /**
     * Scope a query to only include active nodes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

