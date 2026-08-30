<?php

use App\Application\Queries\GetDashboardQuery;
use App\Domain\Issue\Issue;
use App\Domain\Issue\Repositories\IssueRepositoryInterface;
use App\Domain\PullRequest\PullRequest;
use App\Domain\PullRequest\Repositories\PullRequestRepositoryInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\Repositories\SecurityAlertRepositoryInterface;
use App\Domain\SecurityAlert\SecurityAlert;
use App\Domain\SecurityAlert\Severity;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedDashboardRepo(int $githubId = 501, ?int $thresholdOverride = null): Repository
{
    return app(RepositoryRepositoryInterface::class)->upsertFromEntity(new Repository(
        githubId: $githubId,
        name: "gitpulse-{$githubId}",
        fullName: "jay/gitpulse-{$githubId}",
        owner: 'jay',
        private: true,
        htmlUrl: "https://github.com/jay/gitpulse-{$githubId}",
        staleThresholdDays: $thresholdOverride,
    ));
}

function seedStaleAndFreshIssues(int $repositoryId): void
{
    app(IssueRepositoryInterface::class)->upsertForRepository($repositoryId, [
        new Issue(9001, 12, 'Old issue', 'open', ['bug'], 'jay', now()->subDays(60), 'https://x/12'),
        new Issue(9002, 13, 'Fresh issue', 'open', [], null, now()->subDays(2), 'https://x/13'),
    ]);
}

it('flags issues as stale using the global default threshold', function () {
    $repo = seedDashboardRepo();
    seedStaleAndFreshIssues($repo->id);

    $query = app(GetDashboardQuery::class);
    $issues = $query->issuesForRepository($repo->id);

    expect($issues)->toHaveCount(2)
        ->and(collect($issues)->first(fn ($row) => $row['issue']->number === 12)['isStale'])->toBeTrue()
        ->and(collect($issues)->first(fn ($row) => $row['issue']->number === 13)['isStale'])->toBeFalse();
});

it('honors a per-repo staleness override over the global default', function () {
    // Global default 30d; repo override 90d → a 60-day-old issue is NOT stale.
    $repo = seedDashboardRepo(thresholdOverride: 90);
    seedStaleAndFreshIssues($repo->id);

    $query = app(GetDashboardQuery::class);
    $rows = collect($query->issuesForRepository($repo->id));

    expect($rows->first(fn ($row) => $row['issue']->number === 12)['isStale'])->toBeFalse();
});

it('returns PRs with staleness flags', function () {
    $repo = seedDashboardRepo();

    app(PullRequestRepositoryInterface::class)->upsertForRepository($repo->id, [
        new PullRequest(9100, 3, 'Abandoned PR', 'open', 'octocat', 'main', 'feature/a', now()->subDays(45), [], 'https://x/3'),
        new PullRequest(9101, 4, 'Active PR', 'open', 'octocat', 'main', 'feature/b', now()->subHour(), [], 'https://x/4'),
    ]);

    $rows = collect(app(GetDashboardQuery::class)->pullRequestsForRepository($repo->id));

    expect($rows->first(fn ($row) => $row['pr']->number === 3)['isStale'])->toBeTrue()
        ->and($rows->first(fn ($row) => $row['pr']->number === 4)['isStale'])->toBeFalse();
});

it('lists open alerts across all repositories ordered by severity', function () {
    $a = seedDashboardRepo(501);
    $b = seedDashboardRepo(502);

    $alerts = app(SecurityAlertRepositoryInterface::class);
    $alerts->upsertForRepository($a->id, AlertType::DEPENDABOT, [
        new SecurityAlert(1, AlertType::DEPENDABOT, Severity::LOW, 'pkg/low', 'minor', null, null, null, 'https://x/1'),
    ]);
    $alerts->upsertForRepository($b->id, AlertType::CODE_SCANNING, [
        new SecurityAlert(2, AlertType::CODE_SCANNING, Severity::CRITICAL, null, 'SQLi', null, null, null, 'https://x/2'),
    ]);

    $open = collect(app(GetDashboardQuery::class)->allOpenAlerts());

    expect($open)->toHaveCount(2)
        ->and($open->first()['alert']->severity)->toBe(Severity::CRITICAL);
});
