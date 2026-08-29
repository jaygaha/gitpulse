<?php

use App\Application\Handlers\DismissSecurityAlertHandler;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\Repositories\SecurityAlertRepositoryInterface;
use App\Domain\SecurityAlert\SecurityAlert;
use App\Domain\SecurityAlert\Severity;
use App\Infrastructure\GitHub\GitHubRestClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('dismisses an alert on GitHub and locally', function () {
    $repo = app(RepositoryRepositoryInterface::class)->upsertFromEntity(new Repository(
        githubId: 501, name: 'gitpulse', fullName: 'jay/gitpulse', owner: 'jay',
        private: true, htmlUrl: 'https://github.com/jay/gitpulse',
    ));

    $alerts = app(SecurityAlertRepositoryInterface::class);
    $alerts->upsertForRepository($repo->id, AlertType::DEPENDABOT, [
        new SecurityAlert(
            77, AlertType::DEPENDABOT, Severity::CRITICAL,
            'guzzlehttp/guzzle', 'SSRF', 'https://advisory', null, null, 'https://x/77',
        ),
    ]);

    $rest = Mockery::mock(GitHubRestClientInterface::class);
    $rest->shouldReceive('patch')
        ->once()
        ->with('/repos/jay/gitpulse/dependabot/alerts/77', ['state' => 'dismissed'])
        ->andReturn(['number' => 77]);

    $handler = new DismissSecurityAlertHandler($rest, app(RepositoryRepositoryInterface::class), $alerts);
    $handler($repo->id, 77);

    expect($alerts->openForRepository($repo->id))->toHaveCount(0);
});

it('leaves the local alert untouched when GitHub rejects the dismiss', function () {
    $repo = app(RepositoryRepositoryInterface::class)->upsertFromEntity(new Repository(
        githubId: 501, name: 'gitpulse', fullName: 'jay/gitpulse', owner: 'jay',
        private: true, htmlUrl: 'https://github.com/jay/gitpulse',
    ));

    $alerts = app(SecurityAlertRepositoryInterface::class);
    $alerts->upsertForRepository($repo->id, AlertType::DEPENDABOT, [
        new SecurityAlert(
            78, AlertType::DEPENDABOT, Severity::HIGH,
            null, 'XSS', null, null, null, 'https://x/78',
        ),
    ]);

    $rest = Mockery::mock(GitHubRestClientInterface::class);
    $rest->shouldReceive('patch')->once()->andThrow(new RuntimeException('HTTP 403'));

    $handler = new DismissSecurityAlertHandler($rest, app(RepositoryRepositoryInterface::class), $alerts);

    expect(fn () => $handler($repo->id, 78))->toThrow(RuntimeException::class)
        ->and($alerts->openForRepository($repo->id))->toHaveCount(1); // untouched
});
