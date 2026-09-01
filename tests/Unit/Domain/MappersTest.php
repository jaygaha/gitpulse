<?php

use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\Severity;
use App\Infrastructure\GitHub\Mappers\IssueMapper;
use App\Infrastructure\GitHub\Mappers\PullRequestMapper;
use App\Infrastructure\GitHub\Mappers\RepositoryMapper;
use App\Infrastructure\GitHub\Mappers\SecurityAlertMapper;

$repos = json_decode(file_get_contents(__DIR__.'/../../Fixtures/GitHub/repos.json'), true);
$issues = json_decode(file_get_contents(__DIR__.'/../../Fixtures/GitHub/issues.json'), true);
$pulls = json_decode(file_get_contents(__DIR__.'/../../Fixtures/GitHub/pulls.json'), true);
$dependabot = json_decode(file_get_contents(__DIR__.'/../../Fixtures/GitHub/dependabot-alerts.json'), true);
$codeScanning = json_decode(file_get_contents(__DIR__.'/../../Fixtures/GitHub/code-scanning-alerts.json'), true);

it('maps a repository payload', function () use ($repos) {
    $repo = RepositoryMapper::fromApiResponse($repos[0]);

    expect($repo->githubId)->toBe(501)
        ->and($repo->fullName)->toBe('jaygaha/gitpulse')
        ->and($repo->owner)->toBe('jaygaha')
        ->and($repo->private)->toBeTrue()
        ->and((string) $repo->htmlUrl)->toBe('https://github.com/jaygaha/gitpulse');
});

it('maps an issue payload including label names and nullable assignee', function () use ($issues) {
    $issue = IssueMapper::fromApiResponse($issues[0]);
    $unassigned = IssueMapper::fromApiResponse($issues[1]);

    expect($issue->number)->toBe(12)
        ->and($issue->labels)->toEqual(['bug', 'api'])
        ->and($issue->assignee)->toBe('jaygaha')
        ->and($issue->isOpen())->toBeTrue()
        ->and($issue->lastActivityAt)->not->toBeNull()
        ->and($unassigned->assignee)->toBeNull();
});

it('maps open and merged pull request payloads', function () use ($pulls) {
    $open = PullRequestMapper::fromApiResponse($pulls[0]);
    $merged = PullRequestMapper::fromApiResponse($pulls[1]);

    expect($open->number)->toBe(3)
        ->and($open->author)->toBe('octocat')
        ->and($open->baseRef)->toBe('main')
        ->and($open->headRef)->toBe('feature/graphql')
        ->and($open->lastActivityAt)->not->toBeNull()
        ->and($open->isMerged())->toBeFalse()
        ->and($merged->number)->toBe(4)
        ->and($merged->isMerged())->toBeTrue();
});

it('maps dependabot alerts to domain entities', function () use ($dependabot) {
    $alert = SecurityAlertMapper::fromDependabotResponse($dependabot[0]);

    expect($alert->type)->toBe(AlertType::DEPENDABOT)
        ->and($alert->severity)->toBe(Severity::CRITICAL)
        ->and($alert->packageName)->toBe('guzzlehttp/guzzle')
        ->and($alert->advisoryUrl)->toContain('GHSA')
        ->and($alert->isDismissed())->toBeFalse();
});

it('leaves the advisory url null when no advisory link exists', function () {
    $alert = SecurityAlertMapper::fromDependabotResponse([
        'number' => 78,
        'state' => 'open',
        'dependency' => ['package' => ['name' => 'symfony/process']],
        'security_advisory' => ['severity' => 'medium', 'summary' => 'Path traversal'],
        'html_url' => 'https://github.com/jaygaha/gitpulse/security/dependabot/78',
    ]);

    expect($alert->advisoryUrl)->toBeNull();
});

it('maps code scanning alerts, normalizing severity vocabulary', function () use ($codeScanning) {
    $alert = SecurityAlertMapper::fromCodeScanningNode($codeScanning);

    expect($alert->type)->toBe(AlertType::CODE_SCANNING)
        ->and($alert->severity)->toBe(Severity::HIGH)
        ->and($alert->packageName)->toBeNull()
        ->and($alert->summary)->not->toBeEmpty()
        ->and($alert->isDismissed())->toBeFalse();
});

it('keeps real timestamps for dismissed and fixed code scanning states', function () {
    $dismissed = SecurityAlertMapper::fromCodeScanningNode([
        'number' => 6,
        'state' => 'DISMISSED',
        'securitySeverityLevel' => 'MEDIUM',
        'description' => 'Weak hashing algorithm',
        'url' => 'https://github.com/jaygaha/gitpulse/security/code-scanning/6',
        'dismissedAt' => '2026-07-01T08:00:00Z',
    ]);

    $fixed = SecurityAlertMapper::fromCodeScanningNode([
        'number' => 7,
        'state' => 'FIXED',
        'securitySeverityLevel' => 'LOW',
        'description' => 'Open redirect',
        'url' => 'https://github.com/jaygaha/gitpulse/security/code-scanning/7',
    ]);

    $fixedWithTimestamp = SecurityAlertMapper::fromCodeScanningNode([
        'number' => 8,
        'state' => 'FIXED',
        'securitySeverityLevel' => 'LOW',
        'description' => 'Open redirect',
        'url' => 'https://github.com/jaygaha/gitpulse/security/code-scanning/8',
        'fixedAt' => '2026-08-01T12:00:00Z',
    ]);

    expect($dismissed->isDismissed())->toBeTrue()
        ->and($dismissed->dismissedAt?->toIso8601String())->toBe('2026-07-01T08:00:00+00:00')
        ->and($fixed->fixedAt)->toBeNull()
        ->and($fixedWithTimestamp->isFixed())->toBeTrue()
        ->and($fixedWithTimestamp->fixedAt?->toIso8601String())->toBe('2026-08-01T12:00:00+00:00');
});
