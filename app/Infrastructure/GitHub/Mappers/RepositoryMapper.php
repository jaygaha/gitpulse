<?php

namespace App\Infrastructure\GitHub\Mappers;

use App\Domain\Repository\Repository;

final class RepositoryMapper
{
    public static function fromApiResponse(array $data): Repository
    {
        return new Repository(
            githubId: $data['id'],
            name: $data['name'],
            fullName: $data['full_name'],
            owner: $data['owner']['login'],
            private: (bool) $data['private'],
            htmlUrl: $data['html_url'],
        );
    }
}
