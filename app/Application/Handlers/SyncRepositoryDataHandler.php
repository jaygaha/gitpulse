<?php

namespace App\Application\Handlers;

use App\Domain\Issue\Repositories\IssueRepositoryInterface;
use App\Domain\PullRequest\Repositories\PullRequestRepositoryInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\Repositories\SecurityAlertRepositoryInterface;
use App\Infrastructure\GitHub\GitHubGraphQLClientInterface;
use App\Infrastructure\GitHub\GitHubRestClientInterface;
use App\Infrastructure\GitHub\Mappers\IssueMapper;
use App\Infrastructure\GitHub\Mappers\PullRequestMapper;
use App\Infrastructure\GitHub\Mappers\SecurityAlertMapper;

final class SyncRepositoryDataHandler
{
    private const CODE_SCANNING_QUERY = <<<'GRAPHQL'
        query($owner: String!, $name: String!) {
            repository(owner: $owner, name: $name) {
                codeScanningAlerts(first: 100, states: [OPEN, DISMISSED, FIXED]) {
                    nodes {
                        number
                        state
                        securitySeverityLevel
                        description
                        url
                    }
                }
            }
        }
        GRAPHQL;

    public function __construct(
        private readonly GitHubRestClientInterface $github,
        private readonly GitHubGraphQLClientInterface $graphql,
        private readonly RepositoryRepositoryInterface $repositories,
        private readonly IssueRepositoryInterface $issues,
        private readonly PullRequestRepositoryInterface $pullRequests,
        private readonly SecurityAlertRepositoryInterface $alerts,
    ) {}

    /** Syncs one repository; returns the number of items fetched. */
    public function __invoke(int $repositoryId): int
    {
        $repository = $this->repositories->find($repositoryId)
            ?? throw new \InvalidArgumentException("Repository {$repositoryId} not found.");

        [$owner, $name] = explode('/', $repository->fullName, 2);

        $issuePayloads = array_filter(
            $this->github->getPaginated("/repos/{$repository->fullName}/issues", ['state' => 'all']),
            fn (array $payload) => ! isset($payload['pull_request']),
        );

        $issueEntities = array_map(
            fn (array $payload) => IssueMapper::fromApiResponse($payload),
            $issuePayloads,
        );

        $prEntities = array_map(
            fn (array $payload) => PullRequestMapper::fromApiResponse($payload),
            $this->github->getPaginated("/repos/{$repository->fullName}/pulls", ['state' => 'all']),
        );

        $dependabot = array_map(
            fn (array $payload) => SecurityAlertMapper::fromDependabotResponse($payload),
            $this->safeDependabot($repository->fullName),
        );

        $codeScanning = [];
        foreach ($this->safeCodeScanningNodes($owner, $name) as $node) {
            $codeScanning[] = SecurityAlertMapper::fromCodeScanningNode($node);
        }

        $count = 0;
        $count += $this->issues->upsertForRepository($repositoryId, $issueEntities);
        $count += $this->pullRequests->upsertForRepository($repositoryId, $prEntities);
        if ($dependabot !== []) {
            $count += $this->alerts->upsertForRepository($repositoryId, AlertType::DEPENDABOT, $dependabot);
        }
        if ($codeScanning !== []) {
            $count += $this->alerts->upsertForRepository($repositoryId, AlertType::CODE_SCANNING, $codeScanning);
        }

        $this->repositories->touchLastScanned($repositoryId);

        return $count;
    }

    /** Dependabot alerts require a scope some tokens lack; a miss must not kill the scan. */
    private function safeDependabot(string $fullName): array
    {
        try {
            // Dependabot endpoint does not support `page` pagination (see GitHub docs)
            return $this->github->get("/repos/{$fullName}/dependabot/alerts", ['state' => 'open']);
        } catch (\Exception $e) {
            report($e);

            return [];
        }
    }

    private function safeCodeScanningNodes(string $owner, string $name): array
    {
        try {
            $data = $this->graphql->query(self::CODE_SCANNING_QUERY, ['owner' => $owner, 'name' => $name]);

            return $data['repository']['codeScanningAlerts']['nodes'] ?? [];
        } catch (\Exception $e) {
            report($e);

            return [];
        }
    }
}
