<?php

namespace App\Domain\Repository;

final class StalenessThreshold
{
    public readonly int $days;

    public function __construct(int $days)
    {
        if ($days <= 0) {
            throw new \InvalidArgumentException("Staleness threshold must be positive, got {$days}.");
        }

        $this->days = $days;
    }
}
