<?php

namespace Tests\Integration\GitHub;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/**
 * Test double: serves canned fixture JSON instead of hitting the network.
 * Route table: "METHOD /path" => callable(array $queryParams, RequestInterface $request): mixed
 *
 * Array payloads may carry reserved keys:
 *   __status  => HTTP status code
 *   __headers => extra response headers
 *   __raw     => raw body string, sent verbatim instead of json_encode
 */
final class FixtureReplayHandler
{
    public function __construct(private array $routes) {}

    public function __invoke(RequestInterface $request, array $options)
    {
        $uri = $request->getUri();
        $key = strtoupper($request->getMethod()).' '.$uri->getPath();
        parse_str($uri->getQuery(), $query);

        if (! isset($this->routes[$key])) {
            return Create::rejectionFor(
                new RuntimeException("No fixture route for {$key}"),
            );
        }

        $payload = ($this->routes[$key])($query, $request);

        $status = 200;
        $headers = ['Content-Type' => 'application/json'];

        if (is_array($payload)) {
            $status = $payload['__status'] ?? 200;

            foreach (($payload['__headers'] ?? []) as $name => $value) {
                $headers[$name] = $value;
            }

            $rawBody = $payload['__raw'] ?? null;
            unset($payload['__status'], $payload['__headers'], $payload['__raw']);
            $body = $rawBody ?? json_encode($payload);
        } else {
            $body = json_encode($payload);
        }

        return new FulfilledPromise(
            new Response($status, $headers, $body),
        );
    }
}
