<?php

namespace App\Livewire;

use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

final class Topbar extends Component
{
    public string $search = '';

    public bool $showModal = false;

    public int $activeIndex = 0;

    public function render(RepositoryRepositoryInterface $repositories): View
    {
        $repos = collect($repositories->all())->filter(fn ($r) => ! $r->archived)->values();

        $filtered = $repos
            ->when($this->search !== '', fn ($c) => $c->filter(fn ($r) => str_contains(strtolower($r->fullName), strtolower($this->search)) || str_contains(strtolower($r->name), strtolower($this->search))))
            ->values();

        // Uniform route: /repo/{slug} for detail, fallback to query param for dashboard
        $slug = request()->route('slug') ?? request()->query('repo');
        $current = $slug ? $repos->firstWhere('name', $slug) : null;
        $current ??= $repos->first();

        return view('livewire.topbar', [
            'repos' => $repos,
            'filtered' => $filtered,
            'current' => $current,
        ]);
    }

    public function updatedSearch(): void
    {
        $this->activeIndex = 0;
    }

    public function openModal(): void
    {
        $this->showModal = true;
        $this->search = '';
        $this->activeIndex = 0;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function moveUp(): void
    {
        $this->activeIndex = max(0, $this->activeIndex - 1);
    }

    public function moveDown(int $max): void
    {
        $this->activeIndex = min($max - 1, $this->activeIndex + 1);
    }

    public function selectActive(RepositoryRepositoryInterface $repositories): void
    {
        $repos = collect($repositories->all())
            ->filter(fn ($r) => ! $r->archived)
            ->when($this->search !== '', fn ($c) => $c->filter(fn ($r) => str_contains(strtolower($r->fullName), strtolower($this->search)) || str_contains(strtolower($r->name), strtolower($this->search))))
            ->values();

        if ($repos->has($this->activeIndex)) {
            $this->selectRepo($repos[$this->activeIndex]->name);
        }
    }

    #[On('repo-selected')]
    public function selectRepo(string $slug = '', string $name = ''): void
    {
        $resolved = $slug !== '' ? $slug : $name;

        if ($resolved === '') {
            return;
        }

        $this->showModal = false;

        $this->redirect(route('repo.detail', $resolved), navigate: true);
    }
}
