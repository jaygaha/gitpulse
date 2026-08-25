<?php

namespace App\Infrastructure\GitHub;

use App\Infrastructure\GitHub\Exceptions\GraphQLException;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Str;

final class GitHubGraphQLClient implements GitHubGraphQLClientInterface
{
    private const ENDPOINT = 'https://api.github.com/graphql';

    public function __construct(
        private readonly ClientInterface $http,
        private readonly RateLimitGuard $guard = new RateLimitGuard,
        private readonly ?string $token = null,
    ) {}

    public function query(string $query, array $variables = []): array
    {
        $options = [
            'headers' => array_filter([
                'Authorization' => $this->token ? "Bearer {$this->token}" : null,
                'Content-Type' => 'application/json',
            ]),
            'json' => ['query' => $query, 'variables' => $variables],
        ];

        $response = $this->http->request('POST', self::ENDPOINT, $options);
        $this->guard->onResponse($response);

        if ($this->guard->retryDelaySeconds($response) !== null) {
            $this->guard->pause((int) $this->guard->retryDelaySeconds($response));
            $response = $this->http->request('POST', self::ENDPOINT, $options);
            $this->guard->onResponse($response);
        }

        $body = (string) $response->getBody();

        if ($response->getStatusCode() >= 400) {
            throw new GraphQLException(
                "GitHub GraphQL query failed: HTTP {$response->getStatusCode()} ".Str::limit($body, 300),
            );
        }

        $payload = json_decode($body, true);

        if (! is_array($payload)) {
            throw new GraphQLException('GitHub GraphQL returned malformed JSON: '.Str::limit($body, 300));
        }

        if (isset($payload['errors']) && $payload['errors'] !== []) {
            $messages = implode('; ', array_column($payload['errors'], 'message'));

            throw new GraphQLException("GitHub GraphQL query failed: {$messages}");
        }

        return $payload['data'] ?? [];
    }
}
