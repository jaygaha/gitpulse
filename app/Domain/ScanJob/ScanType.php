<?php

declare(strict_types=1);

namespace App\Domain\ScanJob;

enum ScanType: string
{
    case SCHEDULED = 'scheduled';
    case MANUAL = 'manual';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower($value))
            ?? throw new \InvalidArgumentException("Unknown scan type: {$value}.");
    }
}
