<?php

namespace App\Jobs;

use App\Application\Handlers\SyncRepositoryDataHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ScanRepositoryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public function __construct(
        public readonly int $repositoryId,
        public readonly int $totalRepos = 1,
    ) {
        $this->onQueue('scans');
    }

    public function handle(SyncRepositoryDataHandler $handler): void
    {
        $handler($this->repositoryId);
    }

    public function backoff(): array
    {
        return [60, 120, 180];
    }
}
