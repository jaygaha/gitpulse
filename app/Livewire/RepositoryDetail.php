<?php

namespace App\Livewire;

use App\Application\Queries\GetDashboardQuery;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Models\ScanJob;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class RepositoryDetail extends Component
{
    public string $slug;

    public string $activeTab = 'issues';

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['issues', 'prs', 'alerts'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function render(
        RepositoryRepositoryInterface $repositories,
        GetDashboardQuery $query,
    ): View {
        $repository = $repositories->findBySlug($this->slug);

        abort_if(! $repository, 404);

        $issueRows = $query->issuesForRepository($repository->id);
        $prRows = $query->pullRequestsForRepository($repository->id);
        $alerts = $query->allOpenAlerts();

        // Filter alerts for this repo only
        $repoAlerts = collect($alerts)->filter(fn ($a) => $a['repository_id'] === $repository->id)->map(fn ($a) => $a['alert'])->values();

        $critical = $repoAlerts->filter(fn ($a) => $a->severity->value === 'critical')->count();
        $high = $repoAlerts->filter(fn ($a) => $a->severity->value === 'high')->count();
        $moderate = $repoAlerts->filter(fn ($a) => $a->severity->value === 'medium')->count();
        $low = $repoAlerts->filter(fn ($a) => $a->severity->value === 'low')->count();

        $staleIssues = collect($issueRows)->filter(fn ($r) => $r['isStale'] && $r['issue']->isOpen())->count();
        $stalePrs = collect($prRows)->filter(fn ($r) => $r['isStale'] && $r['pr']->isOpen())->count();

        $totalIssues = count($issueRows);
        $totalPrs = count($prRows);

        // Build feed groups
        $criticalFeed = $repoAlerts->filter(fn ($a) => $a->severity->value === 'critical')->map(fn ($a) => [
            'type' => 'critical',
            'title' => $a->summary ?? $a->packageName ?? 'Security alert',
            'meta' => $a->type->value,
            'time' => $a->dismissedAt?->diffForHumans() ?? 'now',
        ])->values();

        $staleIssuesFeed = collect($issueRows)->filter(fn ($r) => $r['isStale'] && $r['issue']->isOpen())->map(fn ($r) => [
            'type' => 'warning',
            'title' => $r['issue']->title,
            'meta' => $repository->fullName.'#'.$r['issue']->number.' · opened '.optional($r['issue']->lastActivityAt)->diffForHumans(),
            'badge' => 'stale',
        ])->values();

        $stalePrsFeed = collect($prRows)->filter(fn ($r) => $r['isStale'] && $r['pr']->isOpen())->map(fn ($r) => [
            'type' => 'warning',
            'title' => $r['pr']->title,
            'meta' => $repository->fullName.'#'.$r['pr']->number.' · opened '.optional($r['pr']->lastActivityAt)->diffForHumans(),
            'badge' => 'stale',
        ])->values();

        return view('livewire.repository-detail', [
            'repository' => $repository,
            'critical' => $critical,
            'high' => $high,
            'moderate' => $moderate,
            'low' => $low,
            'staleIssues' => $staleIssues,
            'stalePrs' => $stalePrs,
            'totalIssues' => $totalIssues,
            'totalPrs' => $totalPrs,
            'criticalFeed' => $criticalFeed,
            'staleIssuesFeed' => $staleIssuesFeed,
            'stalePrsFeed' => $stalePrsFeed,
            'lastScan' => ScanJob::query()->latest('id')->first(),
            'activeTab' => $this->activeTab,
        ])->layout('layouts.app');
    }
}
