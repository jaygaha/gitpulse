<?php

namespace App\Application\Handlers;

use App\Domain\GitHub\GitHubRestClientInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Infrastructure\GitHub\Mappers\RepositoryMapper;

final class SyncRepositoriesHandler
{
    public function __construct(
        private readonly GitHubRestClientInterface $github,
        private readonly RepositoryRepositoryInterface $repositories,
    ) {}

    /** Returns the number of repositories discovered upstream. */
    public function __invoke(): int
    {
        $payloads = $this->github->getPaginated('/user/repos', [
            'visibility' => 'all',
            'affiliation' => 'owner,collaborator,organization_member',
            'sort' => 'full_name',
        ]);

        $githubIds = [];

        foreach ($payloads as $payload) {
            $repository = RepositoryMapper::fromApiResponse($payload);
            $this->repositories->upsertFromEntity($repository);
            $githubIds[] = $repository->githubId;
        }

        if ($githubIds !== []) {
            $this->repositories->archiveMissing($githubIds);
        }

        return count($payloads);
    }
}
