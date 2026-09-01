<?php

declare(strict_types=1);

namespace App\Domain\PullRequest\Repositories;

use App\Domain\PullRequest\PullRequest;

interface PullRequestRepositoryInterface
{
    /** Upserts a batch for one repository; returns number of PRs stored. */
    public function upsertForRepository(int $repositoryId, array $pullRequests): int;

    /** @return list<PullRequest> */
    public function allForRepository(int $repositoryId): array;

    public function countOpenForRepository(int $repositoryId): int;

    /** @return array<int, int> repository_id => open count */
    public function openCountsByRepository(): array;

    /** @return array<int, list<PullRequest>> repository_id => list<PullRequest> (open only, ordered by last_activity_at desc) */
    public function allOpenGrouped(): array;
}
