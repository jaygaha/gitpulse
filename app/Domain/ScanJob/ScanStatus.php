<?php

declare(strict_types=1);

namespace App\Domain\ScanJob;

enum ScanStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::RUNNING], true);
    }
}
