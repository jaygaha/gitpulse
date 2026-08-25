<?php

namespace App\Infrastructure\GitHub;

use App\Infrastructure\GitHub\Exceptions\GraphQLException;

interface GitHubGraphQLClientInterface
{
    /**
     * Runs a query and returns the decoded "data" payload.
     *
     * @throws GraphQLException on GraphQL errors, HTTP errors, or malformed JSON
     */
    public function query(string $query, array $variables = []): array;
}
