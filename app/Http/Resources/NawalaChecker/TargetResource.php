<?php

namespace App\Http\Resources\NawalaChecker;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TargetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain_or_url' => $this->domain_or_url,
            'type' => $this->type,
            'enabled' => $this->enabled,
            'current_status' => $this->current_status,
            'consecutive_failures' => $this->consecutive_failures,
            'check_interval' => $this->check_interval,
            'effective_check_interval' => $this->getEffectiveCheckInterval(),
            'last_checked_at' => $this->last_checked_at?->toISOString(),
            'last_status_change_at' => $this->last_status_change_at?->toISOString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'owner' => $this->whenLoaded('owner', fn() => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ]),
            'group' => $this->whenLoaded('group', fn() => [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'slug' => $this->group->slug,
            ]),
            'tags' => $this->whenLoaded('tags', fn() => 
                $this->tags->map(fn($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'color' => $tag->color,
                ])
            ),
            'latest_check_result' => $this->whenLoaded('latestCheckResult', fn() => 
                $this->latestCheckResult ? new CheckResultResource($this->latestCheckResult) : null
            ),
        ];
    }
}

