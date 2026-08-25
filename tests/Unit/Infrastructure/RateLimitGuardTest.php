<?php

use App\Infrastructure\GitHub\RateLimitGuard;
use GuzzleHttp\Psr7\Response;

function guardWithLog(&$log, int $pauseSeconds = 1, int $retryCapSeconds = 30): RateLimitGuard
{
    return new RateLimitGuard(function (int $seconds) use (&$log) {
        $log[] = $seconds;
    }, pauseSeconds: $pauseSeconds, retryCapSeconds: $retryCapSeconds);
}

it('does not sleep when the rate limit header is absent', function () {
    $log = [];
    $guard = guardWithLog($log);

    $guard->onResponse(new Response(200));

    expect($log)->toBe([]);
});

it('sleeps only when remaining budget drops below the threshold', function () {
    $log = [];
    $guard = guardWithLog($log);

    $guard->onResponse(new Response(200, ['X-RateLimit-Remaining' => '10']));
    expect($log)->toBe([]);

    $guard->onResponse(new Response(200, ['X-RateLimit-Remaining' => '9']));
    expect($log)->toBe([1]);
});

it('ignores non-numeric rate limit headers', function () {
    $log = [];
    $guard = guardWithLog($log);

    $guard->onResponse(new Response(200, ['X-RateLimit-Remaining' => 'garbage']));

    expect($log)->toBe([]);
});

it('honors retry-after capped at the configured maximum', function () {
    $response = new Response(403, ['Retry-After' => '120']);

    expect(guardWithLog($log, retryCapSeconds: 30)->retryDelaySeconds($response))->toBe(30)
        ->and(guardWithLog($log2, retryCapSeconds: 300)->retryDelaySeconds($response))->toBe(120);
});

it('derives the retry delay from the reset timestamp when remaining is zero', function () {
    $inNineSeconds = time() + 9;

    $delay = guardWithLog($log)->retryDelaySeconds(new Response(403, [
        'X-RateLimit-Remaining' => '0',
        'X-RateLimit-Reset' => (string) $inNineSeconds,
    ]));

    expect($delay)->toBeGreaterThanOrEqual(9)
        ->and($delay)->toBeLessThanOrEqual(10);
});

it('returns null for throttled responses without retry evidence', function () {
    $guard = guardWithLog($log);

    expect($guard->retryDelaySeconds(new Response(403)))->toBeNull()
        ->and($guard->retryDelaySeconds(new Response(429)))->toBeNull();
});

it('never proposes retries for non-throttle statuses', function () {
    $guard = guardWithLog($log);

    expect($guard->retryDelaySeconds(new Response(500, ['Retry-After' => '5'])))->toBeNull()
        ->and($guard->retryDelaySeconds(new Response(200)))->toBeNull();
});

it('pauses manually through the injected sleeper', function () {
    $log = [];
    guardWithLog($log)->pause(4);

    expect($log)->toBe([4]);
});
