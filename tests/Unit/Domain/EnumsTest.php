<?php

use App\Domain\ScanJob\ScanStatus;
use App\Domain\ScanJob\ScanType;
use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\Severity;

it('exposes expected alert types', function () {
    expect(AlertType::fromString('dependabot'))->toBe(AlertType::DEPENDABOT)
        ->and(AlertType::fromString('code_scanning'))->toBe(AlertType::CODE_SCANNING)
        ->and(AlertType::fromString('DEPENDABOT'))->toBe(AlertType::DEPENDABOT)
        ->and(fn () => AlertType::fromString('nope'))->toThrow(InvalidArgumentException::class);
});

it('orders severities by criticality', function () {
    expect(Severity::CRITICAL->isHigherThan(Severity::HIGH))->toBeTrue()
        ->and(Severity::LOW->isHigherThan(Severity::MEDIUM))->toBeFalse()
        ->and(Severity::MEDIUM->isHigherThan(Severity::MEDIUM))->toBeFalse();
});

it('orders severities symmetrically across all pairs', function (Severity $higher, Severity $lower) {
    expect($higher->isHigherThan($lower))->toBeTrue()
        ->and($lower->isHigherThan($higher))->toBeFalse();
})->with([
    [Severity::CRITICAL, Severity::HIGH],
    [Severity::HIGH, Severity::MEDIUM],
    [Severity::MEDIUM, Severity::LOW],
    [Severity::CRITICAL, Severity::LOW],
]);

it('maps severity strings from the APIs ignoring case', function (string $input, Severity $expected) {
    expect(Severity::fromString($input))->toBe($expected);
})->with([
    ['critical', Severity::CRITICAL],
    ['CRITICAL', Severity::CRITICAL],
    ['high', Severity::HIGH],
    ['medium', Severity::MEDIUM],
    ['low', Severity::LOW],
]);

it('rejects unknown severity values instead of failing open', function (string $input) {
    Severity::fromString($input);
})->throws(InvalidArgumentException::class)->with(['unknown-thing', '', 'sev:9', 'error', 'warning', 'note']);

it('models the scan lifecycle', function () {
    expect(ScanType::fromString('manual'))->toBe(ScanType::MANUAL)
        ->and(ScanType::SCHEDULED->value)->toBe('scheduled')
        ->and(ScanStatus::PENDING->isActive())->toBeTrue()
        ->and(ScanStatus::RUNNING->isActive())->toBeTrue()
        ->and(ScanStatus::COMPLETED->isActive())->toBeFalse()
        ->and(ScanStatus::FAILED->isActive())->toBeFalse();
});
