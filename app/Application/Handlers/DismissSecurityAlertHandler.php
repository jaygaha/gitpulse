<?php

namespace App\Application\Handlers;

use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\Repositories\SecurityAlertRepositoryInterface;
use App\Infrastructure\GitHub\GitHubRestClientInterface;

final class DismissSecurityAlertHandler
{
    public function __construct(
        private readonly GitHubRestClientInterface $github,
        private readonly RepositoryRepositoryInterface $repositories,
        private readonly SecurityAlertRepositoryInterface $alerts,
    ) {}

    /** Dismisses upstream first; only on success marks the local row dismissed. */
    public function __invoke(int $repositoryId, int $alertGithubId, AlertType|string $type = AlertType::DEPENDABOT, ?string $reason = null): void
    {
        $repository = $this->repositories->find($repositoryId)
            ?? throw new \InvalidArgumentException("Repository {$repositoryId} not found.");

        $alertType = $type instanceof AlertType ? $type : AlertType::fromString($type);

        $endpoint = match ($alertType) {
            AlertType::DEPENDABOT => "/repos/{$repository->fullName}/dependabot/alerts/{$alertGithubId}",
            AlertType::CODE_SCANNING => "/repos/{$repository->fullName}/code-scanning/alerts/{$alertGithubId}",
        };

        $this->github->patch(
            $endpoint,
            ['state' => 'dismissed', ...($reason ? ['dismissed_reason' => $reason] : [])],
        );

        $this->alerts->markDismissed($repositoryId, $alertGithubId);
    }
}
