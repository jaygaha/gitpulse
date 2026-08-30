<?php

declare(strict_types=1);

namespace App\Domain\Issue;

/**
 * Per-repository sequential issue number (visible as #123).
 */
final readonly class IssueNumber
{
    public function __construct(public int $value)
    {
        if ($value <= 0 || $value > 1_000_000) {
            throw new \InvalidArgumentException("IssueNumber must be between 1 and 1,000,000, got {$value}.");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return '#'.$this->value;
    }
}
