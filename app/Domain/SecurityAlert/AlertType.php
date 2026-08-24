<?php

namespace App\Domain\SecurityAlert;

enum AlertType: string
{
    case DEPENDABOT = 'dependabot';
    case CODE_SCANNING = 'code_scanning';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower($value))
            ?? throw new \InvalidArgumentException("Unknown alert type: {$value}.");
    }
}
