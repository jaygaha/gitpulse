<?php

use App\Domain\Services\StalenessCalculator;
use Carbon\Carbon;

it('uses the repo override when present', function () {
    $lastActivity = now()->subDays(10);

    expect(StalenessCalculator::isStale($lastActivity, 7, 30))->toBeTrue()
        ->and(StalenessCalculator::isStale($lastActivity, 14, 30))->toBeFalse();
});

it('falls back to the global default when the repo has no override', function () {
    expect(StalenessCalculator::isStale(now()->subDays(31), null, 30))->toBeTrue()
        ->and(StalenessCalculator::isStale(now()->subDays(29), null, 30))->toBeFalse();
});

it('treats activity exactly on the threshold as not stale', function () {
    expect(StalenessCalculator::isStale(now()->subDays(30), null, 30))->toBeFalse();
});

it('treats a missing last-activity timestamp as not stale', function () {
    expect(StalenessCalculator::isStale(null, null, 30))->toBeFalse();
});

it('compares calendar days deterministically against an injected clock', function () {
    $now = Carbon::parse('2026-01-10 12:00:00');

    expect(StalenessCalculator::isStale(Carbon::parse('2025-12-10 08:00'), null, 30, $now))->toBeTrue()
        ->and(StalenessCalculator::isStale(Carbon::parse('2025-12-11 08:00'), null, 30, $now))->toBeFalse()
        ->and(StalenessCalculator::isStale(Carbon::parse('2025-12-12 08:00'), null, 30, $now))->toBeFalse();
});
