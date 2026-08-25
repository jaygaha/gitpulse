<?php

namespace App\Infrastructure\GitHub;

use App\Infrastructure\GitHub\Exceptions\GitHubApiException;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

final class GitHubRestClient implements GitHubRestClientInterface
{
    private const API_ROOT = 'https://api.github.com';

    private const MAX_ATTEMPTS = 2;

    /** @var array<string, string> */
    private const DEFAULT_HEADERS = [
        'Accept' => 'application/vnd.github+json',
        'X-GitHub-Api-Version' => '2022-11-28',
    ];

    public function __construct(
        private readonly ClientInterface $http,
        private readonly RateLimitGuard $guard = new RateLimitGuard,
        private readonly int $perPage = 100,
        private readonly ?string $token = null,
        private readonly int $maxPages = 100,
    ) {}

    public function getPaginated(string $uri, array $query = []): array
    {
        $pages = [];
        $page = 1;

        do {
            $query['page'] = $page;
            $batch = $this->get($uri, $query);
            $count = count($batch);
            $pages[] = $batch;
            $page++;

            if ($count === $this->perPage && $page > $this->maxPages) {
                throw new GitHubApiException("Pagination for {$uri} exceeded {$this->maxPages} pages.");
            }
        } while ($count === $this->perPage);

        return array_merge([], ...$pages);
    }

    public function get(string $uri, array $query = []): array
    {
        return $this->request('GET', $uri, ['query' => $query]);
    }

    public function patch(string $uri, array $body): array
    {
        return $this->request('PATCH', $uri, ['json' => $body]);
    }

    private function request(string $method, string $uri, array $options): array
    {
        if ($method === 'GET') {
            $options['query'] = array_merge(
                $options['query'] ?? [],
                ['per_page' => $this->perPage],
            );
        }

        $options['headers'] = array_merge(
            $options['headers'] ?? [],
            $this->authHeaders(),
            self::DEFAULT_HEADERS,
        );

        $response = $this->http->request($method, $this->url($uri), $options);
        $this->guard->onResponse($response);

        if ($this->shouldRetry($response)) {
            $delay = (int) $this->guard->retryDelaySeconds($response);
            $this->guard->pause($delay);
            $response = $this->http->request($method, $this->url($uri), $options);
            $this->guard->onResponse($response);
        }

        $body = (string) $response->getBody();

        if ($response->getStatusCode() >= 400) {
            throw new GitHubApiException(
                "GitHub API {$method} {$uri} failed: HTTP {$response->getStatusCode()} ".Str::limit($body, 300),
            );
        }

        return $this->decodeJson($body);
    }

    private function shouldRetry(ResponseInterface $response): bool
    {
        return $this->guard->retryDelaySeconds($response) !== null;
    }

    private function decodeJson(string $body): array
    {
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            throw new GitHubApiException('GitHub API returned malformed JSON: '.Str::limit($body, 300));
        }

        return $decoded;
    }

    private function url(string $uri): string
    {
        if (! str_starts_with($uri, 'http')) {
            return self::API_ROOT.$uri;
        }

        if (str_starts_with($uri, self::API_ROOT.'/')) {
            return $uri;
        }

        throw new \InvalidArgumentException("Refusing to send authenticated request to non-GitHub URI: {$uri}");
    }

    /** @return array<string, string> */
    private function authHeaders(): array
    {
        return $this->token ? ['Authorization' => "Bearer {$this->token}"] : [];
    }
}
