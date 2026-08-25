<?php

namespace App\Providers;

use App\Infrastructure\GitHub\GitHubGraphQLClient;
use App\Infrastructure\GitHub\GitHubGraphQLClientInterface;
use App\Infrastructure\GitHub\GitHubRestClient;
use App\Infrastructure\GitHub\GitHubRestClientInterface;
use App\Infrastructure\GitHub\RateLimitGuard;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GitHubRestClientInterface::class, function () {
            return new GitHubRestClient(
                new Client(['timeout' => 30, 'connect_timeout' => 10, 'http_errors' => false]),
                guard: $this->rateLimitGuard(),
                perPage: (int) config('github.per_page', 100),
                token: config('github.token'),
            );
        });

        $this->app->bind(GitHubGraphQLClientInterface::class, function () {
            return new GitHubGraphQLClient(
                new Client(['timeout' => 30, 'connect_timeout' => 10, 'http_errors' => false]),
                guard: $this->rateLimitGuard(),
                token: config('github.token'),
            );
        });
    }

    public function boot(): void
    {
        //
    }

    private function rateLimitGuard(): RateLimitGuard
    {
        return new RateLimitGuard(
            pauseSeconds: (int) config('github.rate_limit_pause', 1),
            retryCapSeconds: (int) config('github.rate_limit_retry_cap', 30),
        );
    }
}
