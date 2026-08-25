<?php

use App\Domain\Issue\Issue;
use App\Domain\PullRequest\PullRequest;
use App\Domain\Repository\Repository;
use App\Domain\ScanJob\ScanJob;
use App\Domain\ScanJob\ScanStatus;
use App\Domain\ScanJob\ScanType;
use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\SecurityAlert;
use App\Domain\SecurityAlert\Severity;

function repositoryFixture(): Repository
{
    return new Repository(
        githubId: 501,
        name: 'gitpulse',
        fullName: 'jaygaha/gitpulse',
        owner: 'jaygaha',
        private: true,
        htmlUrl: 'https://github.com/jaygaha/gitpulse',
        staleThresholdDays: 21,
    );
}

function alertFixture(array $overrides = []): SecurityAlert
{
    return new SecurityAlert(
        githubId: 77,
        type: AlertType::DEPENDABOT,
        severity: Severity::CRITICAL,
        packageName: 'guzzlehttp/guzzle',
        summary: 'SSRF in redirect handling',
        advisoryUrl: 'https://github.com/advisories/GHSA-x',
        dismissedAt: $overrides['dismissedAt'] ?? null,
        fixedAt: $overrides['fixedAt'] ?? null,
        htmlUrl: 'https://github.com/jaygaha/gitpulse/security/dependabot/77',
    );
}

it('constructs the core entities', function () {
    $repo = repositoryFixture();

    $issue = new Issue(
        githubId: 9001,
        number: 12,
        title: 'Broken sync',
        state: 'open',
        labels: ['bug', 'api'],
        assignee: 'jay',
        lastActivityAt: now()->subDays(40),
        htmlUrl: 'https://github.com/jaygaha/gitpulse/issues/12',
    );

    $pr = new PullRequest(
        githubId: 9100,
        number: 3,
        title: 'Add GraphQL client',
        state: 'open',
        author: 'jay',
        baseRef: 'main',
        headRef: 'feature/graphql',
        lastActivityAt: now()->subHours(2),
        checksStatus: ['status' => 'success'],
        htmlUrl: 'https://github.com/jaygaha/gitpulse/pull/3',
    );

    $job = ScanJob::start(ScanType::MANUAL);

    expect($repo->isPrivate())->toBeTrue()
        ->and($issue->isOpen())->toBeTrue()
        ->and($pr->headRef)->toBe('feature/graphql')
        ->and($alert = alertFixture())->severity->toBe(Severity::CRITICAL)
        ->and($alert->isDismissed())->toBeFalse()
        ->and($job->type)->toBe(ScanType::MANUAL)
        ->and($job->status->isActive())->toBeTrue();
});

it('does not treat closed items as open and public repos as private', function () {
    $closedIssue = new Issue(
        githubId: 1,
        number: 1,
        title: 't',
        state: 'closed',
        labels: [],
        assignee: null,
        lastActivityAt: null,
        htmlUrl: 'https://example.test/i/1',
    );

    $publicRepo = new Repository(
        githubId: 2,
        name: 'r',
        fullName: 'o/r',
        owner: 'o',
        private: false,
        htmlUrl: 'https://example.test/o/r',
    );

    expect($closedIssue->isOpen())->toBeFalse()
        ->and($publicRepo->isPrivate())->toBeFalse();
});

it('flags dismissed and fixed alerts independently', function () {
    $now = now();

    expect(alertFixture(['dismissedAt' => $now])->isDismissed())->toBeTrue()
        ->and(alertFixture(['dismissedAt' => $now])->isFixed())->toBeFalse()
        ->and(alertFixture(['fixedAt' => $now])->isFixed())->toBeTrue()
        ->and(alertFixture(['fixedAt' => $now])->isDismissed())->toBeFalse();
});

it('completes a scan job with counters', function () {
    $job = ScanJob::start(ScanType::SCHEDULED);
    $finished = $job->finish(ScanStatus::COMPLETED, reposScanned: 4, itemsFetched: 120);

    expect($finished->status)->toBe(ScanStatus::COMPLETED)
        ->and($finished->reposScanned)->toBe(4)
        ->and($finished->itemsFetched)->toBe(120)
        ->and($finished->error)->toBeNull()
        ->and($finished->startedAt)->not->toBeNull()
        ->and($finished->finishedAt)->not->toBeNull();
});

it('records an error message on failed scans', function () {
    $job = ScanJob::start(ScanType::MANUAL);
    $failed = $job->finish(ScanStatus::FAILED, reposScanned: 0, itemsFetched: 0, error: 'rate limited');

    expect($failed->status)->toBe(ScanStatus::FAILED)
        ->and($failed->error)->toBe('rate limited');
});

it('leaves the original job untouched when finishing', function () {
    $job = ScanJob::start(ScanType::MANUAL);
    $at = now()->addMinutes(5);
    $job->finish(ScanStatus::COMPLETED, reposScanned: 1, itemsFetched: 1, finishedAt: $at);

    expect($job->status)->toBe(ScanStatus::RUNNING)
        ->and($job->finishedAt)->toBeNull();
});

it('refuses to finish a job that is not running', function (ScanStatus $current) {
    $job = new ScanJob(type: ScanType::MANUAL, status: $current);
    $job->finish(ScanStatus::COMPLETED, reposScanned: 0, itemsFetched: 0);
})->throws(InvalidArgumentException::class)->with([
    ScanStatus::PENDING,
    ScanStatus::COMPLETED,
    ScanStatus::FAILED,
]);

it('rejects an active status as the finish target', function () {
    $job = ScanJob::start(ScanType::MANUAL);
    $job->finish(ScanStatus::PENDING, reposScanned: 0, itemsFetched: 0);
})->throws(InvalidArgumentException::class);

it('requires an error message when finishing as failed', function () {
    $job = ScanJob::start(ScanType::MANUAL);
    $job->finish(ScanStatus::FAILED, reposScanned: 0, itemsFetched: 0);
})->throws(InvalidArgumentException::class);

it('rejects an error message on completed scans', function () {
    $job = ScanJob::start(ScanType::MANUAL);
    $job->finish(ScanStatus::COMPLETED, reposScanned: 1, itemsFetched: 1, error: 'ghost');
})->throws(InvalidArgumentException::class);

it('rejects negative counters', function () {
    $job = ScanJob::start(ScanType::MANUAL);
    $job->finish(ScanStatus::COMPLETED, reposScanned: -1, itemsFetched: 0);
})->throws(InvalidArgumentException::class);

it('rejects non-positive github ids on entities', function () {
    new Repository(githubId: 0, name: 'r', fullName: 'o/r', owner: 'o', private: false, htmlUrl: 'https://example.test/o/r');
})->throws(InvalidArgumentException::class);

it('rejects non-positive numbers on issues and pull requests', function () {
    new Issue(
        githubId: 1,
        number: 0,
        title: 't',
        state: 'open',
        labels: [],
        assignee: null,
        lastActivityAt: null,
        htmlUrl: 'https://example.test/i/0',
    );
})->throws(InvalidArgumentException::class);

it('rejects negative repo staleness overrides', function () {
    new Repository(
        githubId: 501,
        name: 'gitpulse',
        fullName: 'jaygaha/gitpulse',
        owner: 'jaygaha',
        private: true,
        htmlUrl: 'https://github.com/jaygaha/gitpulse',
        staleThresholdDays: -7,
    );
})->throws(InvalidArgumentException::class);

it('accepts only https urls from external sources', function (string $url) {
    new Repository(
        githubId: 1,
        name: 'r',
        fullName: 'o/r',
        owner: 'o',
        private: false,
        htmlUrl: $url,
    );
})->throws(InvalidArgumentException::class)->with([
    'javascript:alert(1)',
    'http://insecure.example.test/o/r',
    'not-a-url',
]);
