<?php

namespace App\Infrastructure\GitHub;

use Psr\Http\Message\ResponseInterface;

/**
 * Pauses briefly when GitHub's rate limit budget runs low, so long scans
 * back off instead of hammering the API into a hard 403.
 */
final class RateLimitGuard
{
    private const REMAINING_THRESHOLD = 10;

    /** @var callable(int): void */
    private $sleep;

    public function __construct(?callable $sleep = null, private readonly int $pauseSeconds = 1, private readonly int $retryCapSeconds = 30)
    {
        $this->sleep = $sleep ?? fn (int $seconds) => sleep($seconds);
    }

    public function onResponse(ResponseInterface $response): void
    {
        $remaining = $this->intHeader($response, 'X-RateLimit-Remaining');

        if ($remaining !== null && $remaining < self::REMAINING_THRESHOLD) {
            ($this->sleep)($this->pauseSeconds);
        }
    }

    public function pause(int $seconds): void
    {
        ($this->sleep)($seconds);
    }

    /**
     * Seconds to wait before one retry of a throttled request (403/429), or
     * null when the response carries no evidence of a transient rate limit.
     */
    public function retryDelaySeconds(ResponseInterface $response): ?int
    {
        if ($response->getStatusCode() !== 403 && $response->getStatusCode() !== 429) {
            return null;
        }

        $retryAfter = $this->intHeader($response, 'Retry-After');

        if ($retryAfter !== null) {
            return min(max($retryAfter, 1), $this->retryCapSeconds);
        }

        $remaining = $this->intHeader($response, 'X-RateLimit-Remaining');
        $reset = $this->intHeader($response, 'X-RateLimit-Reset');

        if ($remaining === 0 && $reset !== null && $reset > time()) {
            return min($reset - time() + 1, $this->retryCapSeconds);
        }

        return null;
    }

    private function intHeader(ResponseInterface $response, string $name): ?int
    {
        $header = $response->getHeaderLine($name);

        if ($header === '') {
            return null;
        }

        $filtered = filter_var($header, FILTER_VALIDATE_INT);

        return $filtered === false ? null : $filtered;
    }
}
