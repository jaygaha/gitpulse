<?php

use App\Domain\PullRequest\PullRequest;
use App\Domain\PullRequest\Repositories\PullRequestRepositoryInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use App\Livewire\PullRequestsTab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function prTabRepo(int $githubId): Repository
{
    return app(RepositoryRepositoryInterface::class)->upsertFromEntity(new Repository(
        githubId: $githubId,
        name: "prrepo{$githubId}",
        fullName: "jay/prrepo{$githubId}",
        owner: 'jay',
        private: false,
        htmlUrl: "https://github.com/jay/prrepo{$githubId}",
    ));
}

it('renders stale badge for ancient PR', function () {
    $repo = prTabRepo(701);
    app(PullRequestRepositoryInterface::class)->upsertForRepository($repo->id, [
        new PullRequest(50, 5, 'Ancient PR', 'open', 'alice', 'main', 'feature', now()->subDays(90), [], 'https://x/5'),
        new PullRequest(51, 6, 'Fresh PR', 'open', 'bob', 'main', 'feature2', now()->subDays(1), [], 'https://x/6'),
    ]);

    Livewire::test(PullRequestsTab::class, ['repositoryId' => $repo->id])
        ->assertSee('Ancient PR')
        ->assertSee('Fresh PR')
        ->assertSee('stale');
});

it('filters PRs by search', function () {
    $repo = prTabRepo(702);
    app(PullRequestRepositoryInterface::class)->upsertForRepository($repo->id, [
        new PullRequest(60, 10, 'Fix login', 'open', 'carol', 'main', 'fix-login', now(), [], 'https://x/10'),
        new PullRequest(61, 11, 'Add dashboard', 'open', 'dave', 'main', 'add-dash', now(), [], 'https://x/11'),
    ]);

    Livewire::test(PullRequestsTab::class, ['repositoryId' => $repo->id])
        ->set('search', 'dashboard')
        ->assertSee('Add dashboard')
        ->assertDontSee('Fix login');
});
