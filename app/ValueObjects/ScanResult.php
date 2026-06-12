<?php

namespace App\ValueObjects;

use App\Enums\Verdict;

final class ScanResult
{
    public function __construct(
        public readonly VtAnalysisResult $stats,
        public readonly string $aiExplanation,
        public readonly Verdict $verdict,
    ) {
    }
}
