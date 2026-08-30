<?php

use App\Domain\GitHub\GitHubRestClientInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\Repositories\SecurityAlertRepositoryInterface;
use App\Domain\SecurityAlert\SecurityAlert;
use App\Domain\SecurityAlert\Severity;
use App\Livewire\SecurityAlertsTab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function alertTabRepo(int $githubId): Repository
{
    return app(RepositoryRepositoryInterface::class)->upsertFromEntity(new Repository(
        githubId: $githubId,
        name: "alrepo{$githubId}",
        fullName: "jay/alrepo{$githubId}",
        owner: 'jay',
        private: false,
        htmlUrl: "https://github.com/jay/alrepo{$githubId}",
    ));
}

it('renders alerts grouped by severity and filters', function () {
    $repo = alertTabRepo(801);
    app(SecurityAlertRepositoryInterface::class)->upsertForRepository($repo->id, AlertType::DEPENDABOT, [
        new SecurityAlert(90, AlertType::DEPENDABOT, Severity::CRITICAL, 'pkg-a', 'Critical CVE', null, null, null, 'https://x/90'),
        new SecurityAlert(91, AlertType::DEPENDABOT, Severity::LOW, 'pkg-b', 'Low risk', null, null, null, 'https://x/91'),
    ]);

    Livewire::test(SecurityAlertsTab::class, ['repositoryId' => $repo->id])
        ->assertSee('Critical CVE')
        ->assertSee('Low risk')
        ->set('severityFilter', 'critical')
        ->assertSee('Critical CVE')
        ->assertDontSee('Low risk');
});

it('dismisses an alert via GitHub and shows status', function () {
    $repo = alertTabRepo(802);
    app(SecurityAlertRepositoryInterface::class)->upsertForRepository($repo->id, AlertType::DEPENDABOT, [
        new SecurityAlert(92, AlertType::DEPENDABOT, Severity::HIGH, 'pkg-c', 'XSS', null, null, null, 'https://x/92'),
    ]);

    $mock = Mockery::mock(GitHubRestClientInterface::class);
    $mock->shouldReceive('patch')
        ->once()
        ->with('/repos/jay/alrepo802/dependabot/alerts/92', ['state' => 'dismissed'])
        ->andReturn(['number' => 92]);
    app()->instance(GitHubRestClientInterface::class, $mock);

    Livewire::test(SecurityAlertsTab::class, ['repositoryId' => $repo->id])
        ->call('dismiss', 92, 'dependabot')
        ->assertSee('dismissed')
        ->assertDontSee('XSS');

    expect(app(SecurityAlertRepositoryInterface::class)->openForRepository($repo->id))->toHaveCount(0);
});

it('surfaces error when GitHub dismiss fails', function () {
    $repo = alertTabRepo(803);
    app(SecurityAlertRepositoryInterface::class)->upsertForRepository($repo->id, AlertType::DEPENDABOT, [
        new SecurityAlert(93, AlertType::DEPENDABOT, Severity::MEDIUM, null, 'Medi', null, null, null, 'https://x/93'),
    ]);

    $mock = Mockery::mock(GitHubRestClientInterface::class);
    $mock->shouldReceive('patch')->once()->andThrow(new RuntimeException('HTTP 403'));
    app()->instance(GitHubRestClientInterface::class, $mock);

    Livewire::test(SecurityAlertsTab::class, ['repositoryId' => $repo->id])
        ->call('dismiss', 93, 'dependabot')
        ->assertSee('HTTP 403');

    expect(app(SecurityAlertRepositoryInterface::class)->openForRepository($repo->id))->toHaveCount(1);
});
