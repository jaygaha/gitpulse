<?php

namespace App\Livewire;

use App\Application\Queries\GetDashboardQuery;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\ScanJob\Repositories\ScanJobRepositoryInterface;
use App\Domain\ScanJob\ScanType;
use App\Jobs\ScanRepositoryJob;
use App\Models\ScanJob;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Dashboard extends Component
{
    public function render(
        RepositoryRepositoryInterface $repositories,
        GetDashboardQuery $dashboardQuery,
    ): View {
        $repos = collect($repositories->all())->filter(fn ($r) => ! $r->archived)->values();

        $baseRows = $dashboardQuery->portfolioRows();

        $rows = collect($baseRows)->map(function ($row) {
            $critical = $row['critical'];
            $warning = $row['warning'];
            $staleIssues = $row['staleIssues'];
            $stalePrs = $row['stalePrs'];

            $status = 'healthy';
            if ($critical > 0) {
                $status = 'critical';
            } elseif ($warning > 0 || $staleIssues > 0 || $stalePrs > 0) {
                $status = 'warning';
            }

            return [
                'repo' => $row['repo'],
                'status' => $status,
                'issues' => $row['issues'],
                'prs' => $row['prs'],
                'alerts' => [],
                'critical' => $critical,
                'warning' => $warning,
                'staleIssues' => $staleIssues,
                'stalePrs' => $stalePrs,
            ];
        })->sort(function ($a, $b) {
            $order = ['critical' => 0, 'warning' => 1, 'healthy' => 2];

            $cmp = $order[$a['status']] <=> $order[$b['status']];

            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = $b['critical'] <=> $a['critical'];

            if ($cmp !== 0) {
                return $cmp;
            }

            return $a['repo']->fullName <=> $b['repo']->fullName;
        })->values();

        $kpis = [
            'repos' => $repos->count(),
            'enabled' => $repos->count(),
            'critical' => $rows->filter(fn ($r) => $r['status'] === 'critical')->count(),
            'warning' => $rows->filter(fn ($r) => $r['status'] === 'warning')->count(),
            'healthy' => $rows->filter(fn ($r) => $r['status'] === 'healthy')->count(),
        ];

        return view('livewire.dashboard', [
            'rows' => $rows,
            'kpis' => $kpis,
            'lastScan' => ScanJob::query()->latest('id')->first(),
        ])->layout('layouts.app');
    }

    public function scanNow(ScanJobRepositoryInterface $scanJobs, RepositoryRepositoryInterface $repositories): void
    {
        $latest = $scanJobs->latest();

        if ($latest !== null && $latest->status->isActive()) {
            session()->flash('status', 'A scan is already running.');

            return;
        }

        $active = collect($repositories->all())
            ->filter(fn ($r) => ! $r->archived)
            ->values();

        if ($active->isEmpty()) {
            session()->flash('status', 'No active repositories to scan.');

            return;
        }

        $scanJobs->startLatest(ScanType::MANUAL);

        foreach ($active as $repository) {
            ScanRepositoryJob::dispatch($repository->id, $active->count());
        }

        session()->flash('status', "Scan queued for {$active->count()} repo(s).");
    }
}
