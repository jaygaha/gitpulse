<?php

use App\Domain\Repository\StalenessThreshold;

it('accepts a positive day count', function () {
    expect((new StalenessThreshold(14))->days)->toBe(14);
});

it('rejects zero and negative day counts', function (int $days) {
    new StalenessThreshold($days);
})->throws(InvalidArgumentException::class)->with([0, -1, -30]);
