<?php

namespace App\Http\Resources\NawalaChecker;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResolverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            'port' => $this->port,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'weight' => $this->weight,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

