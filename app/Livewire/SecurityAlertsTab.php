<?php

namespace App\Livewire;

use App\Application\Handlers\DismissSecurityAlertHandler;
use App\Domain\SecurityAlert\Repositories\SecurityAlertRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

final class SecurityAlertsTab extends Component
{
    use WithPagination;

    public int $repositoryId;

    public string $severityFilter = 'all';

    public string $typeFilter = 'all';

    public string $search = '';

    public ?string $status = null;

    public ?string $error = null;

    public function mount(int $repositoryId): void
    {
        $this->repositoryId = $repositoryId;
    }

    public function updatingSeverityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function dismiss(int $githubId, string $type = 'dependabot'): void
    {
        $this->error = null;
        $this->status = null;

        try {
            app(DismissSecurityAlertHandler::class)($this->repositoryId, $githubId, $type);
            $this->status = 'Alert dismissed.';
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render(SecurityAlertRepositoryInterface $alerts): View
    {
        $all = $alerts->openForRepository($this->repositoryId);

        $filtered = collect($all)
            ->when($this->typeFilter !== 'all', fn ($c) => $c->filter(fn ($a) => $a->type->value === $this->typeFilter))
            ->when($this->severityFilter !== 'all', fn ($c) => $c->filter(fn ($a) => $a->severity->value === $this->severityFilter))
            ->when($this->search !== '', function ($c) {
                $term = strtolower($this->search);

                return $c->filter(fn ($a) => str_contains(strtolower((string) ($a->summary ?? '')), $term)
                    || str_contains(strtolower((string) ($a->packageName ?? '')), $term)
                    || str_contains((string) $a->githubId, $term));
            })
            ->sortBy(fn ($a) => match ($a->severity->value) {
                'critical' => 0,
                'high' => 1,
                'medium' => 2,
                'low' => 3,
                default => 4,
            })
            ->values();

        $perPage = 25;
        $page = $this->getPage();
        $paginator = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.security-alerts-tab', [
            'paginator' => $paginator,
            'total' => $filtered->count(),
        ]);
    }
}
