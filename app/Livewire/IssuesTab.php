<?php

namespace App\Livewire;

use App\Application\Queries\GetDashboardQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Livewire\Component;
use Livewire\WithPagination;

final class IssuesTab extends Component
{
    use WithPagination;

    public int $repositoryId;

    public string $search = '';

    public string $stateFilter = 'all';

    public string $sortField = 'stale';

    public string $sortDir = 'desc';

    public int $perPage = 25;

    public function mount(int $repositoryId): void
    {
        $this->repositoryId = $repositoryId;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStateFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = $field === 'number' ? 'asc' : 'desc';
        }
    }

    public function render(GetDashboardQuery $query): View
    {
        $rows = $query->issuesForRepository($this->repositoryId);

        $filtered = collect($rows)
            ->when($this->search !== '', function ($c) {
                $term = strtolower($this->search);

                return $c->filter(fn ($r) => str_contains(strtolower($r['issue']->title), $term)
                    || str_contains((string) $r['issue']->number, $term)
                    || collect($r['issue']->labels)->contains(fn ($l) => str_contains(strtolower((string) $l), $term)));
            })
            ->when($this->stateFilter !== 'all', fn ($c) => $c->filter(fn ($r) => $r['issue']->state === $this->stateFilter))
            ->sortBy(function ($r) {
                return match ($this->sortField) {
                    'number' => $r['issue']->number,
                    'title' => strtolower($r['issue']->title),
                    'state' => $r['issue']->state,
                    default => $r['isStale'] ? 0 : 1,
                };
            }, SORT_REGULAR, $this->sortDir === 'desc')
            ->values();

        // secondary sort: stale first when sorting by stale
        if ($this->sortField === 'stale') {
            $filtered = $filtered->sortBy(fn ($r) => [$r['isStale'] ? 0 : 1, $r['issue']->number])->values();
            if ($this->sortDir === 'desc') {
                $filtered = $filtered->reverse()->values();
                // ensure stale still first when desc? keep stale first
                $filtered = $filtered->sortBy(fn ($r) => $r['isStale'] ? 0 : 1)->values();
            }
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = new Paginator(
            $filtered->forPage($this->getPage(), $this->perPage)->values(),
            $filtered->count(),
            $this->perPage,
            $this->getPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.issues-tab', [
            'paginator' => $paginator,
            'total' => $filtered->count(),
        ]);
    }
}
