<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'input_value' => $this->input_value,
            'file_hash' => $this->file_hash,
            'malicious_count' => (int) $this->malicious_count,
            'total_engines' => (int) $this->total_engines,
            'ai_explanation' => $this->ai_explanation,
            'status' => $this->status?->value,
            'verdict' => $this->verdict?->value,
            'result_json' => $this->result_json,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
