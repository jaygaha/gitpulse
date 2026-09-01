<?php

use App\Domain\Setting\Repositories\SettingRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds and updates the global staleness threshold', function () {
    $settings = app(SettingRepositoryInterface::class);

    expect($settings->getInt('stale_threshold_days'))->toBe(30);

    $settings->set('stale_threshold_days', '45');

    expect($settings->getInt('stale_threshold_days'))->toBe(45);
});
