<?php

namespace App\Infrastructure\GitHub;

use App\Infrastructure\GitHub\Exceptions\GitHubApiException;
use InvalidArgumentException;

interface GitHubRestClientInterface
{
    /**
     * Flattens all pages of a paginated GET into one array.
     *
     * @throws GitHubApiException on HTTP errors, malformed JSON, or pagination overrun
     */
    public function getPaginated(string $uri, array $query = []): array;

    /**
     * @throws GitHubApiException on HTTP errors or malformed JSON
     * @throws InvalidArgumentException when an absolute URI points outside the GitHub API
     */
    public function get(string $uri, array $query = []): array;

    /**
     * @throws GitHubApiException on HTTP errors or malformed JSON
     * @throws InvalidArgumentException when an absolute URI points outside the GitHub API
     */
    public function patch(string $uri, array $body): array;
}
