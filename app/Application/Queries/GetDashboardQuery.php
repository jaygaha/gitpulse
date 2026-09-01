<?php

namespace App\Application\Queries;

use App\Domain\Issue\Issue;
use App\Domain\Issue\Repositories\IssueRepositoryInterface;
use App\Domain\PullRequest\PullRequest;
use App\Domain\PullRequest\Repositories\PullRequestRepositoryInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
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

    /**
     * Batch portfolio health — 5 queries total instead of N*5.
     *
     * @return list<array{repo: Repository, issues: int, prs: int, critical: int, warning: int, staleIssues: int, stalePrs: int}>
     */
    public function portfolioRows(): array
    {
        $repos = collect($this->repositories->all())->filter(fn ($r) => ! $r->archived)->values();
        if ($repos->isEmpty()) {
            return [];
        }

        $global = $this->globalThreshold();
        $issueCounts = $this->issues->openCountsByRepository();
        $prCounts = $this->pullRequests->openCountsByRepository();
        $issuesGrouped = $this->issues->allOpenGrouped();
        $prsGrouped = $this->pullRequests->allOpenGrouped();

        $alertsGrouped = collect($this->alerts->allOpen(5000))
            ->groupBy(fn ($row) => $row['repository_id']);

        return $repos->map(function ($repo) use ($issueCounts, $prCounts, $issuesGrouped, $prsGrouped, $alertsGrouped, $global) {
            $issueCount = $issueCounts[$repo->id] ?? 0;
            $prCount = $prCounts[$repo->id] ?? 0;
            $openAlerts = $alertsGrouped->get($repo->id, collect())->map(fn ($r) => $r['alert'])->values();
            $critical = $openAlerts->filter(fn ($a) => $a->severity->value === 'critical')->count();
            $warning = $openAlerts->filter(fn ($a) => in_array($a->severity->value, ['high', 'medium'], true))->count();

            $override = $repo->staleThresholdDays;
            $repoIssues = $issuesGrouped[$repo->id] ?? [];
            $repoPrs = $prsGrouped[$repo->id] ?? [];

            $staleIssues = collect($repoIssues)->filter(fn (Issue $i) => $i->isOpen() && StalenessCalculator::isStale($i->lastActivityAt, $override, $global))->count();
            $stalePrs = collect($repoPrs)->filter(fn (PullRequest $pr) => $pr->isOpen() && StalenessCalculator::isStale($pr->lastActivityAt, $override, $global))->count();

            return [
                'repo' => $repo,
                'issues' => $issueCount,
                'prs' => $prCount,
                'critical' => $critical,
                'warning' => $warning,
                'staleIssues' => $staleIssues,
                'stalePrs' => $stalePrs,
            ];
        })->all();
    }

    private function globalThreshold(): int
    {
        return $this->settings->getInt('stale_threshold_days', 30);
    }
}
