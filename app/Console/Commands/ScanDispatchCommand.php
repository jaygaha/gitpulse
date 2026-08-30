<?php

namespace App\Console\Commands;

use App\Application\Handlers\SyncRepositoriesHandler;
use App\Application\Handlers\SyncRepositoryDataHandler;
use App\Domain\Repository\Repositories\RepositoryRepositoryInterface;
use App\Domain\ScanJob\Repositories\ScanJobRepositoryInterface;
use App\Domain\ScanJob\ScanStatus;
use App\Domain\ScanJob\ScanType;
use App\Domain\Services\StalenessCalculator;
use App\Domain\Setting\Repositories\SettingRepositoryInterface;
use App\Jobs\ScanRepositoryJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class ScanDispatchCommand extends Command
{
    protected $signature = 'scan:dispatch {--sync : Run synchronously without queue} {--force : Ignore staleness and scan all repos}';

    protected $description = 'Discover repositories and dispatch per-repo sync jobs';

    public function handle(
        SyncRepositoriesHandler $syncRepos,
        SyncRepositoryDataHandler $syncData,
        ScanJobRepositoryInterface $scanJobs,
        SettingRepositoryInterface $settings,
    ): int {
        if (! config('github.token')) {
            $this->error('GITHUB_TOKEN not configured.');

            return self::FAILURE;
        }

        $scan = $scanJobs->startLatest(ScanType::SCHEDULED);
        $this->info("Scan {$scan->type->value} started at {$scan->startedAt}.");

        try {
            $discovered = $syncRepos();
            $this->info("Discovered {$discovered} repositories upstream.");

            $repositories = collect(app(RepositoryRepositoryInterface::class)->all())
                ->filter(fn ($r) => ! $r->archived)
                ->when(! $this->option('force'), fn ($c) => $c->filter(function ($repo) use ($settings) {
                    $global = $settings->getInt('stale_threshold_days', 30);

                    return StalenessCalculator::isStale(
                        $repo->lastScannedAt,
                        $repo->staleThresholdDays,
                        $global,
                    ) || $repo->lastScannedAt === null;
                }))
                ->values();

            $count = $repositories->count();

            if ($count === 0) {
                $this->info('No repositories due for scan.');
                $scanJobs->finishLatest(ScanStatus::COMPLETED, $discovered, 0);

                return self::SUCCESS;
            }

            $this->info("Dispatching {$count} repository sync jobs".($this->option('sync') ? ' (sync)' : '').'.');

            $totalItems = 0;

            foreach ($repositories as $repo) {
                if ($this->option('sync')) {
                    $totalItems += $syncData($repo->id);
                } else {
                    ScanRepositoryJob::dispatch($repo->id, $count);
                }
            }

            if ($this->option('sync')) {
                $scanJobs->finishLatest(ScanStatus::COMPLETED, $discovered, $totalItems);
                $this->info("Sync completed: {$totalItems} items fetched.");
            } else {
                // For queued jobs, mark dispatch as completed; per-repo jobs will update counts async
                $scanJobs->finishLatest(ScanStatus::COMPLETED, $discovered, 0);
                $this->info('Jobs queued. Run `php artisan queue:work --queue=scans,default` to process.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Scan dispatch failed', ['exception' => $e]);
            $scanJobs->finishLatest(ScanStatus::FAILED, 0, 0, $e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
