<?php

use App\Infrastructure\GitHub\Exceptions\GraphQLException;
use App\Infrastructure\GitHub\GitHubGraphQLClient;
use GuzzleHttp\Client;
use Tests\Integration\GitHub\FixtureReplayHandler;

function graphqlClient(array $routes, ?string $token = 't0k3n'): GitHubGraphQLClient
{
    return new GitHubGraphQLClient(
        new Client(['handler' => new FixtureReplayHandler($routes)]),
        token: $token,
    );
}

it('returns the data payload of a successful query', function () {
    $client = graphqlClient([
        'POST /graphql' => fn () => ['data' => ['viewer' => ['login' => 'jaygaha']]],
    ]);

    $data = $client->query('query { viewer { login } }');

    expect($data['viewer']['login'])->toBe('jaygaha');
});

it('throws a GraphQLException listing query errors', function () {
    $client = graphqlClient([
        'POST /graphql' => fn () => [
            'errors' => [['message' => 'Field unknown is not defined']],
        ],
    ]);

    $client->query('query { unknown } ');
})->throws(GraphQLException::class, 'not defined');

it('treats http error statuses as failures instead of empty data', function () {
    $client = graphqlClient([
        'POST /graphql' => fn () => ['__status' => 502, '__raw' => '<html>bad gateway</html>'],
    ]);

    $client->query('query { viewer { login } }');
})->throws(GraphQLException::class, 'HTTP 502');

it('rejects malformed json responses', function () {
    $client = graphqlClient([
        'POST /graphql' => fn () => ['__raw' => 'not-json-at-all'],
    ]);

    $client->query('query { viewer { login } }');
})->throws(GraphQLException::class, 'malformed JSON');

it('sends the bearer token when configured and omits it otherwise', function () {
    $seen = [];

    $withToken = graphqlClient([
        'POST /graphql' => function ($q, $request) use (&$seen) {
            $seen['authed'] = $request->getHeaderLine('Authorization');

            return ['data' => ['ok' => true]];
        },
    ], token: 't0k3n');
    $withToken->query('query { viewer { login } }');

    $withoutToken = graphqlClient([
        'POST /graphql' => function ($q, $request) use (&$seen) {
            $seen['anon'] = $request->getHeaderLine('Authorization');

            return ['data' => ['ok' => true]];
        },
    ], token: null);
    $withoutToken->query('query { viewer { login } }');

    expect($seen['authed'])->toBe('Bearer t0k3n')
        ->and($seen['anon'])->toBe('');
});
