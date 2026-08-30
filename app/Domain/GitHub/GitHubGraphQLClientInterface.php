<?php

declare(strict_types=1);

namespace App\Domain\GitHub;

use App\Infrastructure\GitHub\Exceptions\GraphQLException;

interface GitHubGraphQLClientInterface
{
    /**
     * @throws GraphQLException
     */
    public function query(string $query, array $variables = []): array;
}
