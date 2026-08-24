<?php

namespace App\Domain\Issue;

final readonly class IssueNumber
{
    public function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException("IssueNumber must be positive, got {$value}.");
        }
    }
}
