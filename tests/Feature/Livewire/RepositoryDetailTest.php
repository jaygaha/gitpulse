<?php

use App\Domain\Issue\Issue;
use App\Domain\Issue\Repositories\IssueRepositoryInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use App\Livewire\RepositoryDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function detailRepo(int $githubId): Repository
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

it('renders header KPIs and tabs', function () {
    $repo = detailRepo(901);

    Livewire::test(RepositoryDetail::class, ['slug' => 'repo901'])
        ->assertSee('jay/repo901')
        ->assertSee('Issues')
        ->assertSee('Pull Requests')
        ->assertSee('Security Alerts');
});

it('switches active tab', function () {
    $repo = detailRepo(902);
    app(IssueRepositoryInterface::class)->upsertForRepository($repo->id, [
        new Issue(1, 1, 'Bug', 'open', [], null, now(), 'https://x/1'),
    ]);

    Livewire::test(RepositoryDetail::class, ['slug' => 'repo902'])
        ->call('setTab', 'prs')
        ->assertSee('Pull Requests')
        ->call('setTab', 'alerts')
        ->assertSee('Security Alerts');
});

it('returns 404 for unknown slug', function () {
    Livewire::test(RepositoryDetail::class, ['slug' => 'missing'])->assertStatus(404);
});
