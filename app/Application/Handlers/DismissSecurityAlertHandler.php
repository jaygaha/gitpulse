<?php

namespace App\Application\Handlers;

use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
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
    public function __invoke(int $repositoryId, int $alertGithubId, string $type = 'dependabot', ?string $reason = null): void
    {
        $repository = $this->repositories->find($repositoryId)
            ?? throw new \InvalidArgumentException("Repository {$repositoryId} not found.");

        if ($type !== 'dependabot') {
            throw new \InvalidArgumentException("Dismissal for type '{$type}' is not supported yet.");
        }

        $this->github->patch(
            "/repos/{$repository->fullName}/dependabot/alerts/{$alertGithubId}",
            ['state' => 'dismissed', ...($reason ? ['dismissed_reason' => $reason] : [])],
        );

        $this->alerts->markDismissed($repositoryId, $alertGithubId);
    }
}
