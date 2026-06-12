<?php

namespace App\Enums;

enum Verdict: string
{
    case Safe = 'safe';
    case Suspicious = 'suspicious';
    case Malicious = 'malicious';

    public const SAFE_MAX_MALICIOUS = 0;
    public const SUSPICIOUS_MAX_MALICIOUS = 3;
    public const SUSPICIOUS_MAX_RATIO = 0.02;

    public static function fromStats(int $malicious, int $total): self
    {
        if ($malicious <= self::SAFE_MAX_MALICIOUS) {
            return self::Safe;
        }

        if ($total > 0) {
            $ratio = $malicious / $total;
            if ($malicious <= self::SUSPICIOUS_MAX_MALICIOUS || $ratio <= self::SUSPICIOUS_MAX_RATIO) {
                return self::Suspicious;
            }
        }

        return self::Malicious;
    }

    public function label(): string
    {
        return match ($this) {
            self::Safe => 'Aman',
            self::Suspicious => 'Waspada',
            self::Malicious => 'Bahaya',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Safe => 'green',
            self::Suspicious => 'yellow',
            self::Malicious => 'red',
        };
    }
}
