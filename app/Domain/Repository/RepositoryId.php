<?php

declare(strict_types=1);

namespace App\Domain\Repository;

/**
 * GitHub's global repository identifier (64-bit snowflake).
 * Distinct from per-repository sequential numbers.
 */
final readonly class RepositoryId
{
    public function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException("RepositoryId must be a positive GitHub id, got {$value}.");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
