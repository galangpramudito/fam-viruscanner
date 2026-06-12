<?php

namespace App\Models;

use App\Enums\ScanStatus;
use App\Enums\Verdict;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'input_value',
        'file_hash',
        'malicious_count',
        'total_engines',
        'ai_explanation',
        'status',
        'verdict',
        'result_json',
        'expires_at',
    ];

    protected $casts = [
        'status' => ScanStatus::class,
        'verdict' => Verdict::class,
        'result_json' => 'array',
        'expires_at' => 'datetime',
        'malicious_count' => 'integer',
        'total_engines' => 'integer',
    ];

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ScanStatus::Pending->value);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ScanStatus::Completed->value);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', ScanStatus::Failed->value);
    }

    public function scopeForUrl(Builder $query, string $url): Builder
    {
        return $query->where('type', 'url')->where('input_value', $url);
    }

    public function scopeForFileHash(Builder $query, string $hash): Builder
    {
        return $query->where('type', 'file')->where('file_hash', $hash);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
