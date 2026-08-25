<?php

namespace App\Infrastructure\GitHub\Mappers;

use App\Domain\PullRequest\PullRequest;
use Carbon\Carbon;

final class PullRequestMapper
{
    public static function fromApiResponse(array $data): PullRequest
    {
        return new PullRequest(
            githubId: $data['id'],
            number: $data['number'],
            title: $data['title'],
            state: strtolower($data['state']),
            author: $data['user']['login'] ?? null,
            baseRef: $data['base']['ref'] ?? 'unknown',
            headRef: $data['head']['ref'] ?? 'unknown',
            lastActivityAt: isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : null,
            checksStatus: [],
            htmlUrl: $data['html_url'],
            mergedAt: isset($data['merged_at']) ? Carbon::parse($data['merged_at']) : null,
        );
    }
}
