<?php

declare(strict_types=1);

namespace App\Domain\SecurityAlert\Repositories;

use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\SecurityAlert;

interface SecurityAlertRepositoryInterface
{
    /** Upserts a batch for one repository + alert type; returns count stored. */
    public function upsertForRepository(int $repositoryId, AlertType $type, array $alerts): int;

    /** Open (non-dismissed, non-fixed) alerts for a repository. @return list<SecurityAlert> */
    public function openForRepository(int $repositoryId): array;

    /** Open alerts across all repositories. @return list<array{alert: SecurityAlert, repository_id: int}> */
    public function allOpen(int $limit = 500, int $offset = 0): array;

    public function markDismissed(int $repositoryId, int $githubId): void;
}
