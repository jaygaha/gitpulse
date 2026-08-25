<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Setting\Repositories\SettingRepositoryInterface;
use App\Models\Setting;

final class EloquentSettingRepository implements SettingRepositoryInterface
{
    public function get(string $key, ?string $default = null): ?string
    {
        return Setting::where('key', $key)->value('value') ?? $default;
    }

    public function set(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : $default;
    }
}
