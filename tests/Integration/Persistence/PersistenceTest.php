<?php

use App\Domain\Issue\Issue;
use App\Domain\Issue\Repositories\IssueRepositoryInterface;
use App\Domain\PullRequest\PullRequest;
use App\Domain\PullRequest\Repositories\PullRequestRepositoryInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use App\Domain\ScanJob\Repositories\ScanJobRepositoryInterface;
use App\Domain\ScanJob\ScanStatus;
use App\Domain\ScanJob\ScanType;
use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\Repositories\SecurityAlertRepositoryInterface;
use App\Domain\SecurityAlert\SecurityAlert;
use App\Domain\SecurityAlert\Severity;
use App\Domain\Setting\Repositories\SettingRepositoryInterface;
use App\Infrastructure\Persistence\EloquentIssueRepository;
use App\Infrastructure\Persistence\EloquentPullRequestRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeRepository(): Repository
{
    return new Repository(
        githubId: 501,
        name: 'gitpulse',
        fullName: 'jay/gitpulse',
        owner: 'jay',
        private: true,
        htmlUrl: 'https://github.com/jay/gitpulse',
        staleThresholdDays: 21,
    );
}

it('persists and retrieves a repository by upsert semantics', function () {
    /** @var RepositoryRepositoryInterface $repo */
    $repo = app(RepositoryRepositoryInterface::class);
    $stored = $repo->upsertFromEntity(makeRepository());

    expect($stored->id)->toBeInt()
        ->and($repo->findByGithubId(501)->fullName)->toBe('jay/gitpulse');

    $updated = $repo->upsertFromEntity(new Repository(
        githubId: 501, name: 'gitpulse', fullName: 'jay/gitpulse',
        owner: 'jay', private: false, htmlUrl: 'https://github.com/jay/gitpulse',
    ));

    expect($updated->id)->toBe($stored->id)
        ->and($updated->isPrivate())->toBeFalse();
});

it('archives repositories missing from the latest sync', function () {
    /** @var RepositoryRepositoryInterface $repo */
    $repo = app(RepositoryRepositoryInterface::class);
    $stored = $repo->upsertFromEntity(makeRepository());

    expect($repo->archiveMissing([999]))->toBe(1)
        ->and($repo->find($stored->id)->archived)->toBeTrue();

    expect(fn () => $repo->archiveMissing([]))->toThrow(InvalidArgumentException::class);
});

it('upserts issues for a repository', function () {
    /** @var RepositoryRepositoryInterface $repos */
    $repos = app(RepositoryRepositoryInterface::class);
    $repositoryId = $repos->upsertFromEntity(makeRepository())->id;

    /** @var EloquentIssueRepository $issues */
    $issues = app(IssueRepositoryInterface::class);
    $issue = new Issue(9001, 12, 'Broken sync', 'open', ['bug'], 'jay', now()->subDays(40), 'https://x/12');

    expect($issues->upsertForRepository($repositoryId, [$issue]))->toBe(1)
        ->and($issues->countOpenForRepository($repositoryId))->toBe(1);

    $renamed = new Issue(9001, 12, 'Broken sync on private repos', 'closed', [], null, now(), 'https://x/12');
    $issues->upsertForRepository($repositoryId, [$renamed]);

    expect($issues->allForRepository($repositoryId)[0]->title)->toBe('Broken sync on private repos')
        ->and($issues->allForRepository($repositoryId)[0]->isOpen())->toBeFalse()
        ->and($issues->countOpenForRepository($repositoryId))->toBe(0);
});

it('upserts pull requests for a repository', function () {
    /** @var RepositoryRepositoryInterface $repos */
    $repos = app(RepositoryRepositoryInterface::class);
    $repositoryId = $repos->upsertFromEntity(makeRepository())->id;

    /** @var EloquentPullRequestRepository $prs */
    $prs = app(PullRequestRepositoryInterface::class);
    $pr = new PullRequest(9100, 3, 'Add GraphQL client', 'open', 'octocat', 'main', 'feature/graphql', now(), [], 'https://x/3');

    expect($prs->upsertForRepository($repositoryId, [$pr]))->toBe(1)
        ->and($prs->allForRepository($repositoryId)[0]->headRef)->toBe('feature/graphql');
});

it('tracks security alerts and dismissal state', function () {
    /** @var RepositoryRepositoryInterface $repos */
    $repos = app(RepositoryRepositoryInterface::class);
    $repositoryId = $repos->upsertFromEntity(makeRepository())->id;

    /** @var SecurityAlertRepositoryInterface $alerts */
    $alerts = app(SecurityAlertRepositoryInterface::class);
    $alert = new SecurityAlert(77, AlertType::DEPENDABOT, Severity::CRITICAL, 'guzzlehttp/guzzle',
        'SSRF in redirect handling', 'https://github.com/advisories/GHSA-x', null, null, 'https://x/77');

    expect($alerts->upsertForRepository($repositoryId, AlertType::DEPENDABOT, [$alert]))->toBe(1)
        ->and($alerts->openForRepository($repositoryId))->toHaveCount(1);

    $alerts->markDismissed($repositoryId, 77);

    expect($alerts->openForRepository($repositoryId))->toHaveCount(0);
});

it('implements latest-only scan job lifecycle', function () {
    /** @var ScanJobRepositoryInterface $jobs */
    $jobs = app(ScanJobRepositoryInterface::class);

    expect($jobs->latest())->toBeNull();

    $started = $jobs->startLatest(ScanType::MANUAL);
    expect($started->status->isActive())->toBeTrue();

    $jobs->finishLatest(ScanStatus::COMPLETED, 4, 120);

    $latest = $jobs->latest();
    expect($latest->status)->toBe(ScanStatus::COMPLETED)
        ->and($latest->reposScanned)->toBe(4)
        ->and($latest->itemsFetched)->toBe(120);

    // A new scan resets the same row.
    $again = $jobs->startLatest(ScanType::SCHEDULED);
    expect($again->type)->toBe(ScanType::SCHEDULED)
        ->and($again->status->isActive())->toBeTrue();
});

it('reads and writes global settings', function () {
    /** @var SettingRepositoryInterface $settings */
    $settings = app(SettingRepositoryInterface::class);

    expect($settings->get('stale_threshold_days'))->toBe('30')
        ->and($settings->getInt('stale_threshold_days'))->toBe(30)
        ->and($settings->get('missing_key', 'fallback'))->toBe('fallback');

    $settings->set('stale_threshold_days', '14');

    expect($settings->getInt('stale_threshold_days'))->toBe(14);
});
