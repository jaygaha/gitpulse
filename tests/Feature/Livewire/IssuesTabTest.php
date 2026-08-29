<?php

use App\Domain\Issue\Issue;
use App\Domain\Issue\Repositories\IssueRepositoryInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use App\Livewire\IssuesTab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function issueTabRepo(int $githubId): Repository
{
    return app(RepositoryRepositoryInterface::class)->upsertFromEntity(new Repository(
        githubId: $githubId,
        name: "repo{$githubId}",
        fullName: "jay/repo{$githubId}",
        owner: 'jay',
        private: false,
        htmlUrl: "https://github.com/jay/repo{$githubId}",
    ));
}

it('renders stale badge for ancient issue', function () {
    $repo = issueTabRepo(601);
    app(IssueRepositoryInterface::class)->upsertForRepository($repo->id, [
        new Issue(10, 1, 'Ancient bug', 'open', [], null, now()->subDays(90), 'https://x/1'),
        new Issue(11, 2, 'Fresh bug', 'open', [], null, now()->subDays(1), 'https://x/2'),
    ]);

    Livewire::test(IssuesTab::class, ['repositoryId' => $repo->id])
        ->assertSee('Ancient bug')
        ->assertSee('Fresh bug')
        ->assertSee('stale');
});

it('filters by state and search', function () {
    $repo = issueTabRepo(602);
    app(IssueRepositoryInterface::class)->upsertForRepository($repo->id, [
        new Issue(20, 10, 'Open issue', 'open', [], null, now(), 'https://x/10'),
        new Issue(21, 11, 'Closed issue', 'closed', [], null, now(), 'https://x/11'),
    ]);

    Livewire::test(IssuesTab::class, ['repositoryId' => $repo->id])
        ->set('stateFilter', 'open')
        ->assertSee('Open issue')
        ->assertDontSee('Closed issue')
        ->set('stateFilter', 'all')
        ->set('search', 'Closed')
        ->assertSee('Closed issue')
        ->assertDontSee('Open issue');
});

it('sorts and paginates', function () {
    $repo = issueTabRepo(603);
    $issues = [];
    for ($i = 1; $i <= 30; $i++) {
        $issues[] = new Issue(100 + $i, $i, "Issue {$i}", 'open', [], null, now()->subDays($i), "https://x/{$i}");
    }
    app(IssueRepositoryInterface::class)->upsertForRepository($repo->id, $issues);

    Livewire::test(IssuesTab::class, ['repositoryId' => $repo->id])
        ->set('perPage', 5)
        ->assertSee('Issue')
        ->call('nextPage')
        ->assertSee('Issue');
});
