<?php

namespace App\Providers;

use App\Domain\Issue\Repositories\IssueRepositoryInterface;
use App\Domain\PullRequest\Repositories\PullRequestRepositoryInterface;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\ScanJob\Repositories\ScanJobRepositoryInterface;
use App\Domain\SecurityAlert\Repositories\SecurityAlertRepositoryInterface;
use App\Domain\Setting\Repositories\SettingRepositoryInterface;
use App\Infrastructure\GitHub\GitHubGraphQLClient;
use App\Infrastructure\GitHub\GitHubGraphQLClientInterface;
use App\Infrastructure\GitHub\GitHubRestClient;
use App\Infrastructure\GitHub\GitHubRestClientInterface;
use App\Infrastructure\GitHub\RateLimitGuard;
use App\Infrastructure\Persistence\EloquentIssueRepository;
use App\Infrastructure\Persistence\EloquentPullRequestRepository;
use App\Infrastructure\Persistence\EloquentRepositoryRepository;
use App\Infrastructure\Persistence\EloquentScanJobRepository;
use App\Infrastructure\Persistence\EloquentSecurityAlertRepository;
use App\Infrastructure\Persistence\EloquentSettingRepository;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepositoryRepositoryInterface::class, EloquentRepositoryRepository::class);
        $this->app->bind(IssueRepositoryInterface::class, EloquentIssueRepository::class);
        $this->app->bind(PullRequestRepositoryInterface::class, EloquentPullRequestRepository::class);
        $this->app->bind(SecurityAlertRepositoryInterface::class, EloquentSecurityAlertRepository::class);
        $this->app->bind(ScanJobRepositoryInterface::class, EloquentScanJobRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, EloquentSettingRepository::class);

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
