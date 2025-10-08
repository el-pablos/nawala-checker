<?php

namespace App\Models\NawalaChecker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'nc_tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
    ];

    /**
     * Get the targets that have this tag.
     */
    public function targets(): BelongsToMany
    {
        return $this->belongsToMany(Target::class, 'nc_target_tag', 'tag_id', 'target_id');
    }
}

