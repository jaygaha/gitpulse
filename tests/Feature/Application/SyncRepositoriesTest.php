<?php

use App\Application\Handlers\SyncRepositoriesHandler;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use App\Infrastructure\GitHub\GitHubRestClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('discovers repos from GitHub, upserts them, and archives missing ones', function () {
    $reposPayload = json_decode(file_get_contents(__DIR__.'/../../Fixtures/GitHub/repos.json'), true);

    // A previously-synced repo that no longer exists upstream.
    app(RepositoryRepositoryInterface::class)->upsertFromEntity(new Repository(
        githubId: 1,
        name: 'ghost',
        fullName: 'jay/ghost',
        owner: 'jay',
        private: false,
        htmlUrl: 'https://github.com/jay/ghost',
    ));

    $rest = Mockery::mock(GitHubRestClientInterface::class);
    $rest->shouldReceive('getPaginated')->once()->andReturn($reposPayload);

    $handler = new SyncRepositoriesHandler($rest, app(RepositoryRepositoryInterface::class));
    $count = $handler();

    $repoRepo = app(RepositoryRepositoryInterface::class);
    $all = collect($repoRepo->all())->keyBy('githubId');

    expect($count)->toBe(2)
        ->and($all[501]->fullName)->toBe('jaygaha/gitpulse')
        ->and($all[502]->isPrivate())->toBeFalse()
        ->and($all[1]->archived)->toBeTrue(); // ghost archived
});
