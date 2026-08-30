<?php

declare(strict_types=1);

namespace App\Domain\PullRequest;

final readonly class PRNumber
{
    public function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException("PRNumber must be positive, got {$value}.");
        }
    }
}
