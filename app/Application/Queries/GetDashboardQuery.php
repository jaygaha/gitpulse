<?php

namespace App\Application\Queries;

use App\Domain\Issue\Issue;
use App\Domain\Issue\Repositories\IssueRepositoryInterface;
use App\Domain\PullRequest\PullRequest;
use App\Domain\PullRequest\Repositories\PullRequestRepositoryInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\SecurityAlert\Repositories\SecurityAlertRepositoryInterface;
use App\Domain\Services\StalenessCalculator;
use App\Domain\Setting\Repositories\SettingRepositoryInterface;

/**
 * Read-side for the dashboard. Staleness is computed here — never stored.
 *
 * Rows are shaped as ['issue'|'pr' => entity, 'isStale' => bool] so Livewire
 * can render badges without re-deriving thresholds per row.
 */
final class GetDashboardQuery
{
    public function __construct(
        private readonly RepositoryRepositoryInterface $repositories,
        private readonly IssueRepositoryInterface $issues,
        private readonly PullRequestRepositoryInterface $pullRequests,
        private readonly SecurityAlertRepositoryInterface $alerts,
        private readonly SettingRepositoryInterface $settings,
    ) {}

    /** @return list<array{issue: Issue, isStale: bool}> */
    public function issuesForRepository(int $repositoryId): array
    {
        $default = $this->globalThreshold();
        $override = $this->repositories->find($repositoryId)?->staleThresholdDays;

        return array_map(
            fn (Issue $issue) => [
                'issue' => $issue,
                'isStale' => StalenessCalculator::isStale($issue->lastActivityAt, $override, $default),
            ],
            $this->issues->allForRepository($repositoryId),
        );
    }

    /** @return list<array{pr: PullRequest, isStale: bool}> */
    public function pullRequestsForRepository(int $repositoryId): array
    {
        $default = $this->globalThreshold();
        $override = $this->repositories->find($repositoryId)?->staleThresholdDays;

        return array_map(
            fn (PullRequest $pr) => [
                'pr' => $pr,
                'isStale' => StalenessCalculator::isStale($pr->lastActivityAt, $override, $default),
            ],
            $this->pullRequests->allForRepository($repositoryId),
        );
    }

    public function allOpenAlerts(): array
    {
        return $this->alerts->allOpen();
    }

    private function globalThreshold(): int
    {
        return $this->settings->getInt('stale_threshold_days', 30);
    }
}
