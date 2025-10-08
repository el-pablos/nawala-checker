<?php

namespace App\Http\Resources\NawalaChecker;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortlinkTargetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'priority' => $this->priority,
            'weight' => $this->weight,
            'is_active' => $this->is_active,
            'current_status' => $this->current_status,
            'last_checked_at' => $this->last_checked_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

