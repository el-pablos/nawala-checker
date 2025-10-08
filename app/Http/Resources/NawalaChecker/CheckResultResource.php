<?php

namespace App\Http\Resources\NawalaChecker;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'response_time_ms' => $this->response_time_ms,
            'resolved_ip' => $this->resolved_ip,
            'http_status_code' => $this->http_status_code,
            'error_message' => $this->error_message,
            'confidence' => $this->confidence,
            'checked_at' => $this->checked_at->toISOString(),
            
            'resolver' => $this->whenLoaded('resolver', fn() => [
                'id' => $this->resolver->id,
                'name' => $this->resolver->name,
                'type' => $this->resolver->type,
            ]),
        ];
    }
}

