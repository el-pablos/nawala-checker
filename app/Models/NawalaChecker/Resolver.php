<?php

namespace App\Models\NawalaChecker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resolver extends Model
{
    use HasFactory;

    protected $table = 'nc_resolvers';

    protected $fillable = [
        'name',
        'type',
        'address',
        'port',
        'is_active',
        'priority',
        'weight',
        'metadata',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'weight' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the check results using this resolver.
     */
    public function checkResults(): HasMany
    {
        return $this->hasMany(CheckResult::class, 'resolver_id');
    }

    /**
     * Scope a query to only include active resolvers.
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

