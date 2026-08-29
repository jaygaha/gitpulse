<div wire:poll.5s class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
    <div class="mb-2 flex items-center justify-between">
        <h3 class="text-xs font-semibold uppercase text-slate-500">Scan status</h3>
        <button wire:click="$refresh" class="text-xs text-slate-400 hover:text-slate-600">↻</button>
    </div>
    @if ($lastScan)
        <p class="text-sm">
            <span class="inline-flex items-center gap-1">
                <span class="h-2 w-2 rounded-full {{ $lastScan->status === 'running' ? 'bg-amber-500 animate-pulse' : ($lastScan->status === 'failed' ? 'bg-red-500' : 'bg-emerald-500') }}"></span>
                <strong class="capitalize">{{ $lastScan->status }}</strong>
            </span> ·
            @if ($lastScan->finished_at) {{ $lastScan->finished_at->diffForHumans() }} · @endif
            {{ $lastScan->repos_scanned }} repo(s), {{ $lastScan->items_fetched }} items
        </p>
        <p class="mt-1 text-xs text-slate-500">
            @if ($queued) {{ $queued }} queued · @endif
            @if ($failed) {{ $failed }} failed <button wire:click="clearFailed" class="underline hover:text-red-600">clear</button> · @endif
            <span wire:loading class="animate-pulse">checking…</span>
        </p>
        @if ($lastScan->error)
            <p class="mt-2 rounded bg-red-50 p-2 text-xs text-red-600 dark:bg-red-900/20 dark:text-red-400">{{ \Illuminate\Support\Str::limit($lastScan->error, 200) }}</p>
        @endif
    @else
        <p class="text-sm text-slate-500">No scans yet. Click Scan now to start.</p>
    @endif
</div>
