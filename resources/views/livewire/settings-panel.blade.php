<div class="rounded-lg border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <h2 class="mb-4 text-sm font-semibold">Settings</h2>

    @if ($status)
        <div class="mb-3 rounded bg-emerald-100 px-3 py-2 text-xs text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $status }}</div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">Global stale threshold (days)</label>
            <div class="mt-1 flex items-center gap-2">
                <input type="number" wire:model="staleThresholdDays" min="1" max="365"
                       class="w-24 rounded-md border border-slate-300 px-2 py-1 text-sm dark:border-slate-700 dark:bg-slate-800">
                <button type="submit" class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900">Save</button>
            </div>
            @error('staleThresholdDays') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            <p class="mt-1 text-xs text-slate-500">Default for all repos. Per-repo overrides below.</p>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">GitHub token</label>
            <p class="mt-1 font-mono text-xs text-slate-500">{{ $githubToken ?? 'Not configured (set GITHUB_TOKEN env)' }}</p>
        </div>
    </form>

    <div class="mt-6 border-t border-slate-200 pt-4 dark:border-slate-800">
        <h3 class="mb-3 text-xs font-semibold uppercase text-slate-500">Per-repository overrides</h3>
        @forelse ($repos as $repo)
            @php $repoId = $repo->id; $threshold = $repoThresholds[$repoId] ?? null; @endphp
                <div class="mb-3 flex items-center justify-between gap-2">
                    <span class="truncate text-xs font-medium">{{ $repo->name }}</span>
                    <div class="flex items-center gap-1">
                        <input type="number" wire:model="repoThresholds.{{ $repoId }}" placeholder="global"
                               class="w-20 rounded-md border border-slate-300 px-2 py-1 text-xs dark:border-slate-700 dark:bg-slate-800">
                        <button wire:click="saveRepoThreshold({{ $repoId }})" class="rounded bg-slate-100 px-2 py-1 text-xs hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700">Save</button>
                    </div>
                </div>
                @error("repoThresholds.{$repoId}") <span class="mb-2 block text-xs text-red-600">{{ $message }}</span> @enderror
        @empty
            <p class="text-xs text-slate-500">No repositories synced yet.</p>
        @endforelse
        <p class="text-xs text-slate-400">Leave blank to use global threshold.</p>
    </div>
</div>
