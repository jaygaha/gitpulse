<?php

use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('smoke: dashboard, repo detail and settings pages render without Vite', function () {
    $this->withoutVite();

    $this->get('/')->assertOk();
    $this->get('/settings')->assertOk();

    $repo = app(RepositoryRepositoryInterface::class)->upsertFromEntity(new Repository(
        githubId: 999, name: 'smoke-repo', fullName: 'jay/smoke-repo', owner: 'jay', private: false, htmlUrl: 'https://github.com/jay/smoke-repo',
    ));

    $this->get("/repo/{$repo->name}")->assertOk();
});
