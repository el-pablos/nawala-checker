<?php

namespace App\Http\Resources\NawalaChecker;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortlinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'rotation_count' => $this->rotation_count,
            'last_rotated_at' => $this->last_rotated_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'group' => $this->whenLoaded('group', fn() => [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'slug' => $this->group->slug,
                'rotation_threshold' => $this->group->rotation_threshold,
                'cooldown_seconds' => $this->group->cooldown_seconds,
            ]),
            'current_target' => $this->whenLoaded('currentTarget', fn() =>
                $this->currentTarget ? new ShortlinkTargetResource($this->currentTarget) : null
            ),
            'targets' => $this->whenLoaded('targets', fn() =>
                ShortlinkTargetResource::collection($this->targets)
            ),
            'rotation_history' => $this->whenLoaded('rotationHistory', function() {
                if (!$this->rotationHistory) {
                    return [];
                }
                return $this->rotationHistory->map(function($history) {
                    return [
                        'id' => $history->id,
                        'from_target_id' => $history->from_target_id,
                        'to_target_id' => $history->to_target_id,
                        'reason' => $history->reason,
                        'rotated_at' => $history->rotated_at?->toISOString(),
                        'from_target' => $history->fromTarget ? [
                            'url' => $history->fromTarget->url,
                        ] : null,
                        'to_target' => $history->toTarget ? [
                            'url' => $history->toTarget->url,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}

