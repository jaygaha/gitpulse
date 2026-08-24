<?php

namespace App\Domain\Services;

use Carbon\CarbonInterface;

/**
 * An item is stale when its last activity day is strictly before the effective threshold's cutoff day (repo override, else global default).
 */
final class StalenessCalculator
{
    public static function isStale(?CarbonInterface $lastActivity, ?int $repoThreshold, int $globalDefault, ?CarbonInterface $now = null): bool
    {
        $threshold = $repoThreshold ?? $globalDefault;

        if ($threshold <= 0) {
            throw new \InvalidArgumentException("Staleness threshold must be positive, got {$threshold}.");
        }

        if ($lastActivity === null) {
            return false;
        }

        $timezone = date_default_timezone_get();
        $cutoff = ($now ?? now())
            ->timezone($timezone)
            ->copy()
            ->subDays($threshold)
            ->startOfDay();

        return $lastActivity->copy()->timezone($timezone)->startOfDay()->lt($cutoff);
    }
}
