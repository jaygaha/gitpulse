<?php

declare(strict_types=1);

namespace App\Domain\Issue\Repositories;

use App\Domain\Issue\Issue;

interface IssueRepositoryInterface
{
    /** Upserts a batch for one repository; returns number of issues stored. */
    public function upsertForRepository(int $repositoryId, array $issues): int;

    /** @return list<Issue> */
    public function allForRepository(int $repositoryId): array;

    public function countOpenForRepository(int $repositoryId): int;

    /** @return array<int, int> repository_id => open count */
    public function openCountsByRepository(): array;

    /** @return array<int, list<Issue>> repository_id => list<Issue> (open only, ordered by last_activity_at desc) */
    public function allOpenGrouped(): array;
}
