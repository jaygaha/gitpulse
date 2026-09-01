<?php

declare(strict_types=1);

namespace App\Domain\Setting;

final class Setting
{
    public function __construct(
        public readonly string $key,
        public readonly string $value,
    ) {}

    public function intValue(int $default = 0): int
    {
        $trimmed = trim($this->value);

        return preg_match('/^\d+$/', $trimmed) ? (int) $trimmed : $default;
    }
}
