<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Issue\Issue;
use App\Domain\Issue\Repositories\IssueRepositoryInterface;
use App\Models\Issue as IssueModel;

final class EloquentIssueRepository implements IssueRepositoryInterface
{
    public function upsertForRepository(int $repositoryId, array $issues): int
    {
        if ($issues === []) {
            return 0;
        }

        if (count($issues) < 20) {
            foreach ($issues as $issue) {
                IssueModel::updateOrCreate(
                    ['repository_id' => $repositoryId, 'github_id' => $issue->githubId],
                    [
                        'number' => $issue->number,
                        'title' => $issue->title,
                        'state' => $issue->state,
                        'labels' => $issue->labels,
                        'assignee' => $issue->assignee,
                        'last_activity_at' => $issue->lastActivityAt,
                        'html_url' => (string) $issue->htmlUrl,
                    ],
                );
            }

            return count($issues);
        }

        $now = now();
        $rows = array_map(fn ($issue) => [
            'repository_id' => $repositoryId,
            'github_id' => $issue->githubId,
            'number' => $issue->number,
            'title' => $issue->title,
            'state' => $issue->state,
            'labels' => json_encode($issue->labels),
            'assignee' => $issue->assignee,
            'last_activity_at' => $issue->lastActivityAt,
            'html_url' => (string) $issue->htmlUrl,
            'created_at' => $now,
            'updated_at' => $now,
        ], $issues);

        IssueModel::upsert($rows, ['repository_id', 'github_id'], ['number', 'title', 'state', 'labels', 'assignee', 'last_activity_at', 'html_url', 'updated_at']);

        return count($issues);
    }

    public function allForRepository(int $repositoryId): array
    {
        return IssueModel::where('repository_id', $repositoryId)
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(fn ($m) => new Issue(
                githubId: $m->github_id,
                number: $m->number,
                title: $m->title,
                state: $m->state,
                labels: $m->labels ?? [],
                assignee: $m->assignee,
                lastActivityAt: $m->last_activity_at,
                htmlUrl: $m->html_url,
            ))
            ->all();
    }

    public function countOpenForRepository(int $repositoryId): int
    {
        return IssueModel::where('repository_id', $repositoryId)->where('state', 'open')->count();
    }

    public function openCountsByRepository(): array
    {
        return IssueModel::where('state', 'open')
            ->selectRaw('repository_id, count(*) as aggregate')
            ->groupBy('repository_id')
            ->pluck('aggregate', 'repository_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    public function allOpenGrouped(): array
    {
        return IssueModel::where('state', 'open')
            ->orderByDesc('last_activity_at')
            ->get()
            ->groupBy('repository_id')
            ->map(fn ($group) => $group->map(fn ($m) => new Issue(
                githubId: $m->github_id,
                number: $m->number,
                title: $m->title,
                state: $m->state,
                labels: $m->labels ?? [],
                assignee: $m->assignee,
                lastActivityAt: $m->last_activity_at,
                htmlUrl: $m->html_url,
            ))->all())
            ->all();
    }
}
