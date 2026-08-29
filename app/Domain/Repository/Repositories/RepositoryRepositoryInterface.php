<?php

namespace App\Domain\Repository\Repositories;

use App\Domain\Repository\Repository;

interface RepositoryRepositoryInterface
{
    /** Insert or update by github_id; returns the persisted entity with local id. */
    public function upsertFromEntity(Repository $repository): Repository;

    /** @return list<Repository> */
    public function all(): array;

    public function find(int $id): ?Repository;

    public function findByGithubId(int $githubId): ?Repository;

    /**
     * Archives repos whose github_id is not in $githubIds.
     *
     * @param  list<int>  $githubIds
     *
     * @throws \InvalidArgumentException on empty input
     */
    public function archiveMissing(array $githubIds): int;

    /** @param  list<int>  $ids  @return list<Repository> */
    public function findByIds(array $ids): array;

    public function findBySlug(string $slug): ?Repository;

    public function updateStaleThreshold(int $id, ?int $days): void;

    public function touchLastScanned(int $id): void;
}
