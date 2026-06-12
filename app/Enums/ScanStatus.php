<?php

namespace App\Enums;

enum ScanStatus: string
{
    case Pending = 'pending';
    case Scanning = 'scanning';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Scanning => 'Memindai',
            self::Completed => 'Selesai',
            self::Failed => 'Gagal',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed], true);
    }
}
