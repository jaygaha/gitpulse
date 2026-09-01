<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Application\Queries\GetDashboardQuery;
use App\Livewire\Concerns\HasStaleFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Livewire\Component;

final class PullRequestsTab extends Component
{
    use HasStaleFilters;

    public int $repositoryId;

    public function mount(int $repositoryId): void
    {
        $this->repositoryId = $repositoryId;
    }

    public function render(GetDashboardQuery $query): View
    {
        $rows = $query->pullRequestsForRepository($this->repositoryId);

        $filtered = collect($rows)
            ->when($this->search !== '', function ($c) {
                $term = strtolower($this->search);

                return $c->filter(fn ($r) => str_contains(strtolower($r['pr']->title), $term)
                    || str_contains((string) $r['pr']->number, $term)
                    || str_contains(strtolower((string) ($r['pr']->author ?? '')), $term));
            })
            ->when($this->stateFilter !== 'all', fn ($c) => $c->filter(fn ($r) => $r['pr']->state === $this->stateFilter))
            ->sortBy(function ($r) {
                return match ($this->sortField) {
                    'number' => $r['pr']->number,
                    'title' => strtolower($r['pr']->title),
                    'state' => $r['pr']->state,
                    default => $r['isStale'] ? 0 : 1,
                };
            }, SORT_REGULAR, $this->sortDir === 'desc')
            ->values();

        if ($this->sortField === 'stale') {
            $filtered = $filtered->sortBy(fn ($r) => $r['isStale'] ? 0 : 1)->values();
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = new Paginator(
            $filtered->forPage($this->getPage(), $this->perPage)->values(),
            $filtered->count(),
            $this->perPage,
            $this->getPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.pull-requests-tab', [
            'paginator' => $paginator,
            'total' => $filtered->count(),
        ]);
    }
}
