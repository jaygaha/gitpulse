<?php

declare(strict_types=1);

namespace App\Domain\SecurityAlert;

enum Severity: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public function isHigherThan(self $other): bool
    {
        return $this->rank() > $other->rank();
    }

    private function rank(): int
    {
        return match ($this) {
            self::CRITICAL => 4,
            self::HIGH => 3,
            self::MEDIUM => 2,
            self::LOW => 1,
        };
    }

    public static function fromString(string $value): self
    {
        return match (strtolower(trim($value))) {
            'critical' => self::CRITICAL,
            'high' => self::HIGH,
            'medium' => self::MEDIUM,
            'low' => self::LOW,
            default => throw new \InvalidArgumentException("Unknown severity: {$value}."),
        };
    }
}
