<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\Repository\Repository;
use App\Models\Repository as RepositoryModel;

final class EloquentRepositoryRepository implements RepositoryRepositoryInterface
{
    public function upsertFromEntity(Repository $repository): Repository
    {
        $model = RepositoryModel::updateOrCreate(
            ['github_id' => $repository->githubId],
            [
                'name' => $repository->name,
                'full_name' => $repository->fullName,
                'owner' => $repository->owner,
                'private' => $repository->private,
                'html_url' => $repository->htmlUrl,
                'stale_threshold_days' => $repository->staleThresholdDays,
            ],
        );

        return $this->toDomain($model);
    }

    public function all(): array
    {
        return RepositoryModel::query()
            ->orderBy('full_name')
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function find(int $id): ?Repository
    {
        $model = RepositoryModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByGithubId(int $githubId): ?Repository
    {
        $model = RepositoryModel::where('github_id', $githubId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function archiveMissing(array $githubIds): int
    {
        if ($githubIds === []) {
            throw new \InvalidArgumentException('archiveMissing requires at least one github id.');
        }

        return RepositoryModel::query()
            ->where('archived', false)
            ->whereNotIn('github_id', $githubIds)
            ->update(['archived' => true]);
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return RepositoryModel::whereIn('id', $ids)
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function touchLastScanned(int $id): void
    {
        RepositoryModel::whereKey($id)->update(['last_scanned_at' => now()]);
    }

    private function toDomain(RepositoryModel $model): Repository
    {
        return new Repository(
            githubId: $model->github_id,
            name: $model->name,
            fullName: $model->full_name,
            owner: $model->owner,
            private: $model->private,
            htmlUrl: $model->html_url,
            staleThresholdDays: $model->stale_threshold_days,
            lastScannedAt: $model->last_scanned_at,
            archived: (bool) $model->archived,
            id: $model->id,
        );
    }
}
