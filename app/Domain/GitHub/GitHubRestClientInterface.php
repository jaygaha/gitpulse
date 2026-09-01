<?php

declare(strict_types=1);

namespace App\Domain\GitHub;

use App\Infrastructure\GitHub\Exceptions\GitHubApiException;

interface GitHubRestClientInterface
{
    /**
     * @throws GitHubApiException
     */
    public function getPaginated(string $uri, array $query = []): array;

    /**
     * @throws GitHubApiException
     */
    public function get(string $uri, array $query = []): array;

    /**
     * @throws GitHubApiException
     */
    public function patch(string $uri, array $body): array;
}
