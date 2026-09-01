<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\WithPagination;

trait HasStaleFilters
{
    use WithPagination;

    public string $search = '';

    public string $stateFilter = 'all';

    public string $sortField = 'stale';

    public string $sortDir = 'desc';

    public int $perPage = 25;

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
}
