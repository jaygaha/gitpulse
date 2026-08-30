<?php

namespace App\Livewire;

use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class RepoSidebar extends Component
{
    public ?string $selectedSlug = null;

    public function mount(?string $selectedSlug = null): void
    {
        $this->selectedSlug = $selectedSlug ?? request()->route('slug') ?? request()->query('repo');
    }

    public function selectRepo(string $slug): void
    {
        $this->selectedSlug = $slug;
        $this->dispatch('repo-selected', slug: $slug);
    }

    public function render(RepositoryRepositoryInterface $repositories): View
    {
        $repos = collect($repositories->all())
            ->filter(fn ($r) => ! $r->archived)
            ->sortBy(fn ($r) => strtolower($r->fullName))
            ->values();

        return view('livewire.repo-sidebar', [
            'repos' => $repos,
        ]);
    }
}
