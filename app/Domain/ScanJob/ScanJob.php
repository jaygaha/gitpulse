<?php

namespace App\Domain\ScanJob;

use Carbon\CarbonInterface;
use InvalidArgumentException;

final class ScanJob
{
    public function __construct(
        public readonly ScanType $type,
        public readonly ScanStatus $status,
        public readonly ?CarbonInterface $startedAt = null,
        public readonly ?CarbonInterface $finishedAt = null,
        public readonly int $reposScanned = 0,
        public readonly int $itemsFetched = 0,
        public readonly ?string $error = null,
    ) {
        if ($reposScanned < 0 || $itemsFetched < 0) {
            throw new InvalidArgumentException('Scan counters cannot be negative.');
        }

        if (! $status->isActive()) {
            if ($finishedAt === null) {
                throw new InvalidArgumentException("A {$status->value} scan requires a finishedAt timestamp.");
            }

            if ($status === ScanStatus::FAILED && $error === null) {
                throw new InvalidArgumentException('A failed scan requires an error message.');
            }

            if ($status === ScanStatus::COMPLETED && $error !== null) {
                throw new InvalidArgumentException('A completed scan cannot carry an error message.');
            }
        }
    }

    public static function start(ScanType $type, ?CarbonInterface $startedAt = null): self
    {
        return new self(type: $type, status: ScanStatus::RUNNING, startedAt: $startedAt ?? now());
    }

    public function finish(
        ScanStatus $status,
        int $reposScanned,
        int $itemsFetched,
        ?string $error = null,
        ?CarbonInterface $finishedAt = null,
    ): self {
        if ($this->status !== ScanStatus::RUNNING) {
            throw new InvalidArgumentException("Only a running scan can be finished, current status is {$this->status->value}.");
        }

        if ($status->isActive()) {
            throw new InvalidArgumentException('A finished scan cannot have an active status.');
        }

        return new self(
            type: $this->type,
            status: $status,
            startedAt: $this->startedAt,
            finishedAt: $finishedAt ?? now(),
            reposScanned: $reposScanned,
            itemsFetched: $itemsFetched,
            error: $error,
        );
    }
}
