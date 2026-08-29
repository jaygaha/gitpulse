<?php

namespace App\Livewire;

use App\Models\ScanJob;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

final class ScanMonitor extends Component
{
    public function render(): View
    {
        return view('livewire.scan-monitor', [
            'lastScan' => ScanJob::query()->latest('id')->first(),
            'queued' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
        ]);
    }

    public function clearFailed(): void
    {
        DB::table('failed_jobs')->truncate();
    }
}
