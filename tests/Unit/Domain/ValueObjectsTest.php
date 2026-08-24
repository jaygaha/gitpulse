<?php

use App\Domain\Issue\IssueNumber;
use App\Domain\PullRequest\PRNumber;
use App\Domain\Repository\RepositoryId;

it('wraps a positive integer id', function () {
    expect((new RepositoryId(98765))->value)->toBe(98765)
        ->and((new IssueNumber(42))->value)->toBe(42)
        ->and((new PRNumber(7))->value)->toBe(7);
});

it('rejects non-positive values', function (int $value) {
    new RepositoryId($value);
})->throws(InvalidArgumentException::class)->with([0, -1]);

it('rejects non-positive issue numbers', function (int $value) {
    new IssueNumber($value);
})->throws(InvalidArgumentException::class)->with([0, -1]);

it('rejects non-positive PR numbers', function (int $value) {
    new PRNumber($value);
})->throws(InvalidArgumentException::class)->with([0, -1]);
