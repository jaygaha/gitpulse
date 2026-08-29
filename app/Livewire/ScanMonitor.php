<?php

namespace App\Livewire;

use App\Domain\ScanJob\Repositories\ScanJobRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

final class ScanMonitor extends Component
{
    public function render(ScanJobRepositoryInterface $scanJobs): View
    {
        return view('livewire.scan-monitor', [
            'lastScan' => $scanJobs->latest(),
            'queued' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
        ]);
    }

    public function clearFailed(): void
    {
        DB::table('failed_jobs')->truncate();
    }
}
