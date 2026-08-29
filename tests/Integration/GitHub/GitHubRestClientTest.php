<?php

use App\Infrastructure\GitHub\Exceptions\GitHubApiException;
use App\Infrastructure\GitHub\GitHubRestClient;
use App\Infrastructure\GitHub\RateLimitGuard;
use GuzzleHttp\Client;
use Tests\Integration\GitHub\FixtureReplayHandler;

function restClient(array $routes, ?array &$sleepLog = null, int $maxPages = 100): GitHubRestClient
{
    $sleepLog = [];
    $guard = new RateLimitGuard(function (int $seconds) use (&$sleepLog) {
        $sleepLog[] = $seconds;
    }, pauseSeconds: 1);

    return new GitHubRestClient(
        new Client(['handler' => new FixtureReplayHandler($routes)]),
        guard: $guard,
        perPage: 2,
        token: 't0k3n',
        maxPages: $maxPages,
    );
}

it('flattens all pages of a paginated endpoint', function () {
    $repos = json_decode(file_get_contents(__DIR__.'/../../Fixtures/GitHub/repos.json'), true);

    $client = restClient([
        'GET /user/repos' => fn ($q) => match ((int) ($q['page'] ?? 1)) {
            1 => array_slice($repos, 0, 2),
            2 => array_slice($repos, 2), // short page terminates pagination
        },
    ]);

    $all = $client->getPaginated('/user/repos');

    expect($all)->toHaveCount(2)
        ->and($all[0]['id'])->toBe(501)
        ->and($all[1]['id'])->toBe(502);
});

it('keeps caller query params and injected per_page on every page request', function () {
    $seenQueries = [];

    $client = restClient([
        'GET /repos/jaygaha/gitpulse/issues' => function ($q) use (&$seenQueries) {
            $seenQueries[] = $q;

            return (int) ($q['page'] ?? 1) === 1 ? [['id' => 9001], ['id' => 9002]] : [];
        },
    ]);

    $client->getPaginated('/repos/jaygaha/gitpulse/issues', ['state' => 'open']);

    expect($seenQueries)->toHaveCount(2)
        ->and($seenQueries[0]['state'])->toBe('open')
        ->and((int) $seenQueries[0]['per_page'])->toBe(2)
        ->and((int) $seenQueries[1]['page'])->toBe(2);
});

it('stops paginating once the page cap is exceeded', function () {
    $client = restClient([
        'GET /user/repos' => fn () => array_fill(0, 2, ['id' => 1]),
    ], sleepLog: $ignored, maxPages: 3);

    $client->getPaginated('/user/repos');
})->throws(GitHubApiException::class, 'exceeded 3 pages');

it('performs patch requests and returns decoded json', function () {
    $bodies = [];

    $client = restClient([
        'PATCH /repos/jaygaha/gitpulse/dependabot/alerts/77' => function ($q, $request) use (&$bodies) {
            $bodies[] = json_decode((string) $request->getBody(), true);

            return ['number' => 77, 'state' => 'dismissed'];
        },
    ]);

    $result = $client->patch('/repos/jaygaha/gitpulse/dependabot/alerts/77', ['state' => 'dismissed']);

    expect($result['number'])->toBe(77)
        ->and($bodies[0])->toBe(['state' => 'dismissed']);
});

it('throws a typed exception including the response body on http errors', function () {
    $client = restClient([
        'GET /user/repos' => fn () => ['__status' => 404, '__headers' => [], 'message' => 'Not Found'],
    ]);

    $client->get('/user/repos');
})->throws(GitHubApiException::class, 'HTTP 404');

it('rejects malformed json responses instead of returning empty data', function () {
    $client = restClient([
        'GET /user/repos' => fn () => ['__raw' => '<html>gateway timeout</html>'],
    ]);

    $client->get('/user/repos');
})->throws(GitHubApiException::class, 'malformed JSON');

it('refuses to send authenticated requests to foreign hosts', function () {
    $client = restClient([]);

    $client->get('https://evil.example/collections');
})->throws(InvalidArgumentException::class, 'non-GitHub URI');

it('allows absolute URIs on the github api origin', function () {
    $client = restClient([
        'GET /user' => fn () => ['login' => 'jaygaha'],
    ]);

    $result = $client->get('https://api.github.com/user');

    expect($result['login'])->toBe('jaygaha');
});

it('sends the bearer token and versioned accept headers', function () {
    $headers = null;

    $client = restClient([
        'GET /user' => function ($q, $request) use (&$headers) {
            $headers = $request->getHeaders();

            return ['login' => 'jaygaha'];
        },
    ]);

    $client->get('/user');

    expect($headers['Authorization'])->toBe(['Bearer t0k3n'])
        ->and($headers['Accept'])->toBe(['application/vnd.github+json'])
        ->and($headers['X-GitHub-Api-Version'])->toBe(['2022-11-28']);
});

it('omits the authorization header when no token is configured', function () {
    $headers = null;
    $guard = new RateLimitGuard(fn () => null);

    $client = new GitHubRestClient(
        new Client(['handler' => new FixtureReplayHandler([
            'GET /user' => function ($q, $request) use (&$headers) {
                $headers = $request->getHeaders();

                return ['login' => 'jaygaha'];
            },
        ])]),
        guard: $guard,
        perPage: 2,
    );

    $client->get('/user');

    expect($headers)->not->toHaveKey('Authorization');
});

it('retries once after sleeping when throttled with retry-after', function () {
    $sleepLog = null;
    $calls = 0;

    $client = restClient([
        'GET /user/repos' => function () use (&$calls) {
            $calls++;

            return $calls === 1
                ? ['__status' => 403, '__headers' => ['Retry-After' => '5'], 'message' => 'rate limit']
                : ['id' => 501];
        },
    ], $sleepLog);

    $result = $client->get('/user/repos');

    expect($result)->toBe(['id' => 501])
        ->and($sleepLog)->toBe([5])
        ->and($calls)->toBe(2);
});
