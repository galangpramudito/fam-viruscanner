<?php

namespace App\Exceptions;

use RuntimeException;

class VirusTotalTimeoutException extends RuntimeException
{
    public static function exceeded(int $attempts): self
    {
        return new self("VirusTotal analysis did not complete after {$attempts} attempts.");
    }
}
