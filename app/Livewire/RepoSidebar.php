<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class RepoSidebar extends Component
{
    public $repos;

    public ?string $selectedSlug = null;

    public function selectRepo(string $slug): void
    {
        $this->selectedSlug = $slug;
        $this->dispatch('repo-selected', slug: $slug);
    }

    public function render(): View
    {
        return view('livewire.repo-sidebar');
    }
}
