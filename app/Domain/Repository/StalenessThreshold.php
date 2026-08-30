<?php

declare(strict_types=1);

namespace App\Domain\Repository;

final readonly class StalenessThreshold
{
    public function __construct(public int $days)
    {
        if ($days <= 0) {
            throw new \InvalidArgumentException("Staleness threshold must be positive, got {$days}.");
        }
    }
}
