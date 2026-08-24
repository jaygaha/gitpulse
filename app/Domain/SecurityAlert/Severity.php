<?php

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

    /** Maps GitHub severity vocab (error/warning/note/none/moderate), case-insensitive. Throws on unknown values. */
    public static function fromString(string $value): self
    {
        return match (strtolower(trim($value))) {
            'critical' => self::CRITICAL,
            'high', 'error' => self::HIGH,
            'medium', 'moderate', 'warning' => self::MEDIUM,
            'low', 'note', 'none' => self::LOW,
            default => throw new \InvalidArgumentException("Unknown severity: {$value}."),
        };
    }
}
