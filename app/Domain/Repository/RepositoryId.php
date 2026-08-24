<?php

namespace App\Domain\Repository;

final readonly class RepositoryId
{
    public function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException("RepositoryId must be positive, got {$value}.");
        }
    }
}
