<?php

declare(strict_types=1);

namespace App\Domain\PullRequest;

/**
 * Per-repository sequential pull request number (visible as PR #123).
 */
final readonly class PRNumber
{
    public function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException("Pull request number must be positive, got {$value}.");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return 'PR #'.$this->value;
    }
}
