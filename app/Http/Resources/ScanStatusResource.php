<?php

namespace App\Http\Resources;

use App\Enums\ScanStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScanStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payload = [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status?->value,
            'verdict' => $this->verdict?->value,
            'malicious_count' => (int) $this->malicious_count,
            'total_engines' => (int) $this->total_engines,
            'progress' => $this->progressPercent(),
        ];

        if ($this->status === ScanStatus::Completed) {
            $payload['ai_explanation'] = $this->ai_explanation;
            $payload['result_json'] = $this->result_json;
        }

        if ($this->status === ScanStatus::Failed) {
            $payload['error'] = $this->result_json['error'] ?? null;
        }

        return $payload;
    }

    private function progressPercent(): int
    {
        return match ($this->status?->value) {
            'pending' => 10,
            'scanning' => 50,
            'completed' => 100,
            'failed' => 100,
            default => 0,
        };
    }
}
