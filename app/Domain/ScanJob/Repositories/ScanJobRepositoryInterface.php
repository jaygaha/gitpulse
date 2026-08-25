<?php

namespace App\Domain\ScanJob\Repositories;

use App\Domain\ScanJob\ScanJob;
use App\Domain\ScanJob\ScanStatus;
use App\Domain\ScanJob\ScanType;

interface ScanJobRepositoryInterface
{
    /** Latest-only semantics: creates (or resets) the single scan row as RUNNING. */
    public function startLatest(ScanType $type): ScanJob;

    public function finishLatest(ScanStatus $status, int $reposScanned, int $itemsFetched, ?string $error = null): void;

    public function latest(): ?ScanJob;
}
