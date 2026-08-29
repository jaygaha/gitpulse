<?php

namespace App\Livewire;

use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Setting\Repositories\SettingRepositoryInterface;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class SettingsPanel extends Component
{
    public int $staleThresholdDays = 30;

    public ?string $githubToken = null;

    public string $status = '';

    public array $repoThresholds = [];

    public function mount(SettingRepositoryInterface $settings): void
    {
        $this->staleThresholdDays = $settings->getInt('stale_threshold_days', 30);
        $this->githubToken = config('github.token') ? '••••••••'.substr((string) config('github.token'), -4) : null;

        $repos = app(RepositoryRepositoryInterface::class)->all();

        foreach ($repos as $repo) {
            $this->repoThresholds[$repo->id] = $repo->staleThresholdDays;
        }
    }

    public function save(SettingRepositoryInterface $settings): void
    {
        $this->validate([
            'staleThresholdDays' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $settings->set('stale_threshold_days', (string) $this->staleThresholdDays);
        $this->status = 'Settings saved.';
    }

    public function saveRepoThreshold(int $repoId, RepositoryRepositoryInterface $repos): void
    {
        $value = $this->repoThresholds[$repoId] ?? null;

        if ($value === '' || $value === null) {
            $repos->updateStaleThreshold($repoId, null);
            $this->repoThresholds[$repoId] = null;
        } else {
            $days = (int) $value;

            if ($days < 1 || $days > 365) {
                $this->addError("repoThresholds.{$repoId}", 'Must be between 1 and 365.');

                return;
            }

            $repos->updateStaleThreshold($repoId, $days);
        }

        $this->status = 'Per-repo threshold saved.';
    }

    public function render(): View
    {
        return view('livewire.settings-panel');
    }
}
