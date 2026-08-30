<?php

declare(strict_types=1);

namespace App\Domain\Setting\Repositories;

interface SettingRepositoryInterface
{
    public function get(string $key, ?string $default = null): ?string;

    public function set(string $key, string $value): void;

    public function getInt(string $key, int $default = 0): int;
}
