<?php

use App\Application\Handlers\SyncRepositoryDataHandler;
use App\Domain\GitHub\GitHubGraphQLClientInterface;
use App\Domain\GitHub\GitHubRestClientInterface;
use App\Domain\Issue\Repositories\IssueRepositoryInterface;
use App\Domain\PullRequest\Repositories\PullRequestRepositoryInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use App\Domain\SecurityAlert\Repositories\SecurityAlertRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedRepo(): Repository
{
    return app(RepositoryRepositoryInterface::class)->upsertFromEntity(new Repository(
        githubId: 501,
        name: 'gitpulse',
        fullName: 'jay/gitpulse',
        owner: 'jay',
        private: true,
        htmlUrl: 'https://github.com/jay/gitpulse',
    ));
}

it('syncs issues, pull requests, and both alert types for one repository', function () {
    $repo = seedRepo();

    $issues = json_decode(file_get_contents(__DIR__.'/../../Fixtures/GitHub/issues.json'), true);
    $pulls = json_decode(file_get_contents(__DIR__.'/../../Fixtures/GitHub/pulls.json'), true);
    $dependabot = json_decode(file_get_contents(__DIR__.'/../../Fixtures/GitHub/dependabot-alerts.json'), true);
    $codeScanning = ['repository' => [
        'codeScanningAlerts' => ['nodes' => [json_decode(file_get_contents(__DIR__.'/../../Fixtures/GitHub/code-scanning-alerts.json'), true)]],
    ]];

    $rest = Mockery::mock(GitHubRestClientInterface::class);
    $rest->shouldReceive('getPaginated')->once()->with('/repos/jay/gitpulse/issues', Mockery::type('array'))->andReturn($issues);
    $rest->shouldReceive('getPaginated')->once()->with('/repos/jay/gitpulse/pulls', Mockery::type('array'))->andReturn($pulls);
    $rest->shouldReceive('getPaginated')->once()->with('/repos/jay/gitpulse/dependabot/alerts', Mockery::type('array'))->andReturn($dependabot);

    $graphql = Mockery::mock(GitHubGraphQLClientInterface::class);
    $graphql->shouldReceive('query')->once()->with(Mockery::type('string'), Mockery::hasKey('after'))->andReturn([
        'repository' => [
            'codeScanningAlerts' => [
                'nodes' => $codeScanning['repository']['codeScanningAlerts']['nodes'],
                'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
            ],
        ],
    ]);

    $handler = new SyncRepositoryDataHandler(
        $rest,
        $graphql,
        app(RepositoryRepositoryInterface::class),
        app(IssueRepositoryInterface::class),
        app(PullRequestRepositoryInterface::class),
        app(SecurityAlertRepositoryInterface::class),
    );
    $items = $handler($repo->id);

    expect($items)->toBe(6) // 2 issues + 2 PRs + 1 dependabot + 1 code scanning
        ->and(app(IssueRepositoryInterface::class)->countOpenForRepository($repo->id))->toBe(2)
        ->and(app(PullRequestRepositoryInterface::class)->countOpenForRepository($repo->id))->toBe(1)
        ->and(app(SecurityAlertRepositoryInterface::class)->openForRepository($repo->id))->toHaveCount(2);

    // last_scanned_at touched
    expect(app(RepositoryRepositoryInterface::class)->find($repo->id)->lastScannedAt)->not->toBeNull();
});
