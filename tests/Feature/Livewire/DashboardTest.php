<?php

use App\Domain\Issue\Issue;
use App\Domain\Issue\Repositories\IssueRepositoryInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use App\Jobs\ScanRepositoryJob;
use App\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function uiRepo(int $githubId, bool $private = false): Repository
{
    return app(RepositoryRepositoryInterface::class)->upsertFromEntity(new Repository(
        githubId: $githubId,
        name: "repo{$githubId}",
        fullName: "jay/repo{$githubId}",
        owner: 'jay',
        private: $private,
        htmlUrl: "https://github.com/jay/repo{$githubId}",
    ));
}

it('renders the dashboard with KPI strip and repo table', function () {
    uiRepo(501);
    uiRepo(502, private: true);

    Livewire::test(Dashboard::class)
        ->assertSee('Dashboard')
        ->assertSee('repo501')
        ->assertSee('repo502')
        ->assertSee('Repos');
});

it('navigates to repo detail from the table', function () {
    $repo = uiRepo(501);

    app(IssueRepositoryInterface::class)->upsertForRepository($repo->id, [
        new Issue(1, 12, 'Ancient issue', 'open', [], null, now()->subDays(90), 'https://x/12'),
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('jay/repo501')
        ->assertSeeHtml('href="http://localhost/repo/repo501"');
});

it('queues a manual scan when Scan Now is pressed', function () {
    Queue::fake();
    config()->set('github.token', 'fake');
    uiRepo(501);

    Livewire::test(Dashboard::class)
        ->call('scanNow');

    Queue::assertPushed(ScanRepositoryJob::class);
});
