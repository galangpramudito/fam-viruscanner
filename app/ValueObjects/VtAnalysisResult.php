<?php

namespace App\ValueObjects;

final class VtAnalysisResult
{
    public function __construct(
        public readonly int $malicious,
        public readonly int $suspicious,
        public readonly int $harmless,
        public readonly int $undetected,
        public readonly int $timeout,
        public readonly int $total,
    ) {
    }

    public static function fromStatsArray(array $stats): self
    {
        $malicious = (int) ($stats['malicious'] ?? 0);
        $suspicious = (int) ($stats['suspicious'] ?? 0);
        $harmless = (int) ($stats['harmless'] ?? 0);
        $undetected = (int) ($stats['undetected'] ?? 0);
        $timeout = (int) ($stats['timeout'] ?? 0);

        $total = $malicious + $suspicious + $harmless + $undetected + $timeout;

        return new self(
            malicious: $malicious,
            suspicious: $suspicious,
            harmless: $harmless,
            undetected: $undetected,
            timeout: $timeout,
            total: $total,
        );
    }

    public function isEmpty(): bool
    {
        return $this->total === 0;
    }
}
