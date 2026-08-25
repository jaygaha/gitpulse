<?php

namespace App\Infrastructure\Persistence;

use App\Domain\ScanJob\Repositories\ScanJobRepositoryInterface;
use App\Domain\ScanJob\ScanJob;
use App\Domain\ScanJob\ScanStatus;
use App\Domain\ScanJob\ScanType;
use App\Models\ScanJob as ScanJobModel;

final class EloquentScanJobRepository implements ScanJobRepositoryInterface
{
    public function startLatest(ScanType $type): ScanJob
    {
        $model = ScanJobModel::query()->firstOrNew([]);

        // validated by ScanType enum before fill

        $model->fill([
            'type' => $type->value,
            'status' => ScanStatus::RUNNING->value,
            'started_at' => now(),
            'finished_at' => null,
            'repos_scanned' => 0,
            'items_fetched' => 0,
            'error' => null,
        ])->save();

        return new ScanJob(
            type: ScanType::from($model->type),
            status: ScanStatus::from($model->status),
            startedAt: $model->started_at,
        );
    }

    public function finishLatest(ScanStatus $status, int $reposScanned, int $itemsFetched, ?string $error = null): void
    {
        ScanJobModel::query()->firstOrFail()->update([
            'status' => $status->value,
            'finished_at' => now(),
            'repos_scanned' => $reposScanned,
            'items_fetched' => $itemsFetched,
            'error' => $error,
        ]);
    }

    public function latest(): ?ScanJob
    {
        $model = ScanJobModel::query()->latest('id')->first();

        if (! $model) {
            return null;
        }

        return new ScanJob(
            type: ScanType::from($model->type),
            status: ScanStatus::from($model->status),
            startedAt: $model->started_at,
            finishedAt: $model->finished_at,
            reposScanned: $model->repos_scanned,
            itemsFetched: $model->items_fetched,
            error: $model->error,
        );
    }
}
