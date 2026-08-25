<?php

namespace App\Infrastructure\Persistence;

use App\Domain\PullRequest\PullRequest;
use App\Domain\PullRequest\Repositories\PullRequestRepositoryInterface;
use App\Models\PullRequest as PullRequestModel;

final class EloquentPullRequestRepository implements PullRequestRepositoryInterface
{
    public function upsertForRepository(int $repositoryId, array $pullRequests): int
    {
        foreach ($pullRequests as $pr) {
            PullRequestModel::updateOrCreate(
                ['repository_id' => $repositoryId, 'github_id' => $pr->githubId],
                [
                    'number' => $pr->number,
                    'title' => $pr->title,
                    'state' => $pr->state,
                    'author' => $pr->author,
                    'base_ref' => $pr->baseRef,
                    'head_ref' => $pr->headRef,
                    'last_activity_at' => $pr->lastActivityAt,
                    'merged_at' => $pr->mergedAt,
                    'checks_status' => $pr->checksStatus ?: null,
                    'html_url' => $pr->htmlUrl,
                ],
            );
        }

        return count($pullRequests);
    }

    public function allForRepository(int $repositoryId): array
    {
        return PullRequestModel::where('repository_id', $repositoryId)
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(fn ($m) => new PullRequest(
                githubId: $m->github_id,
                number: $m->number,
                title: $m->title,
                state: $m->state,
                author: $m->author,
                baseRef: $m->base_ref ?? 'unknown',
                headRef: $m->head_ref ?? 'unknown',
                lastActivityAt: $m->last_activity_at,
                checksStatus: $m->checks_status ?? [],
                htmlUrl: $m->html_url,
                mergedAt: $m->merged_at,
            ))
            ->all();
    }

    public function countOpenForRepository(int $repositoryId): int
    {
        return PullRequestModel::where('repository_id', $repositoryId)->where('state', 'open')->count();
    }

    public function openCountsByRepository(): array
    {
        return PullRequestModel::where('state', 'open')
            ->selectRaw('repository_id, count(*) as aggregate')
            ->groupBy('repository_id')
            ->pluck('aggregate', 'repository_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
