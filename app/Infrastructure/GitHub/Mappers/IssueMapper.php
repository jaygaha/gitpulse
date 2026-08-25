<?php

namespace App\Infrastructure\GitHub\Mappers;

use App\Domain\Issue\Issue;
use Carbon\Carbon;

final class IssueMapper
{
    public static function fromApiResponse(array $data): Issue
    {
        return new Issue(
            githubId: $data['id'],
            number: $data['number'],
            title: $data['title'],
            state: strtolower($data['state']),
            labels: array_values(array_map(
                fn ($label) => is_array($label) ? $label['name'] : $label,
                $data['labels'] ?? [],
            )),
            assignee: isset($data['assignee']['login']) ? $data['assignee']['login'] : null,
            lastActivityAt: isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : null,
            htmlUrl: $data['html_url'],
        );
    }
}
