<?php

namespace App\Infrastructure\Persistence;

use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\Repositories\SecurityAlertRepositoryInterface;
use App\Domain\SecurityAlert\SecurityAlert;
use App\Domain\SecurityAlert\Severity;
use App\Models\SecurityAlert as SecurityAlertModel;

final class EloquentSecurityAlertRepository implements SecurityAlertRepositoryInterface
{
    public function upsertForRepository(int $repositoryId, AlertType $type, array $alerts): int
    {
        if ($alerts === []) {
            return 0;
        }

        if (count($alerts) < 20) {
            foreach ($alerts as $alert) {
                SecurityAlertModel::updateOrCreate(
                    ['repository_id' => $repositoryId, 'type' => $type->value, 'github_id' => $alert->githubId],
                    [
                        'severity' => $alert->severity->value,
                        'package_name' => $alert->packageName,
                        'summary' => $alert->summary,
                        'advisory_url' => $alert->advisoryUrl,
                        'dismissed_at' => $alert->dismissedAt,
                        'fixed_at' => $alert->fixedAt,
                        'html_url' => $alert->htmlUrl,
                    ],
                );
            }

            return count($alerts);
        }

        $now = now();
        $rows = array_map(fn ($alert) => [
            'repository_id' => $repositoryId,
            'type' => $type->value,
            'github_id' => $alert->githubId,
            'severity' => $alert->severity->value,
            'package_name' => $alert->packageName,
            'summary' => $alert->summary,
            'advisory_url' => $alert->advisoryUrl,
            'dismissed_at' => $alert->dismissedAt,
            'fixed_at' => $alert->fixedAt,
            'html_url' => $alert->htmlUrl,
            'created_at' => $now,
            'updated_at' => $now,
        ], $alerts);

        SecurityAlertModel::upsert($rows, ['repository_id', 'type', 'github_id'], ['severity', 'package_name', 'summary', 'advisory_url', 'dismissed_at', 'fixed_at', 'html_url', 'updated_at']);

        return count($alerts);
    }

    public function openForRepository(int $repositoryId): array
    {
        return SecurityAlertModel::where('repository_id', $repositoryId)
            ->whereNull('dismissed_at')
            ->whereNull('fixed_at')
            ->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function allOpen(int $limit = 500, int $offset = 0): array
    {
        return SecurityAlertModel::with('repository')
            ->whereNull('dismissed_at')
            ->whereNull('fixed_at')
            ->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn ($m) => ['alert' => $this->toDomain($m), 'repository_id' => $m->repository_id])
            ->all();
    }

    public function markDismissed(int $repositoryId, int $githubId): void
    {
        SecurityAlertModel::where('repository_id', $repositoryId)
            ->where('github_id', $githubId)
            ->update(['dismissed_at' => now()]);
    }

    private function toDomain(SecurityAlertModel $m): SecurityAlert
    {
        return new SecurityAlert(
            githubId: $m->github_id,
            type: AlertType::from($m->type),
            severity: Severity::from($m->severity),
            packageName: $m->package_name,
            summary: $m->summary,
            advisoryUrl: $m->advisory_url,
            dismissedAt: $m->dismissed_at,
            fixedAt: $m->fixed_at,
            htmlUrl: $m->html_url,
        );
    }
}
