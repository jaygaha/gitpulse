<div class="rounded-lg border p-6" style="border-color: var(--border); background: var(--surface); color: var(--text-primary);">
    <h2 class="mb-4 text-sm font-semibold">Settings</h2>

    @if ($status)
        <div class="mb-3 rounded px-3 py-2 text-xs" style="background: rgba(34,197,94,0.1); color: var(--success); border: 1px solid var(--success);">{{ $status }}</div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="block text-xs font-medium" style="color: var(--text-secondary);">Global stale threshold (days)</label>
            <div class="mt-1 flex items-center gap-2">
                <input type="number" wire:model="staleThresholdDays" min="1" max="365"
                       class="w-24 rounded border px-2 py-1 text-sm" style="border-color: var(--border); background: var(--elevated); color: var(--text-primary);">
                <button type="submit" class="rounded px-3 py-1.5 text-xs font-semibold" style="background: var(--brand); color: #000;">Save</button>
            </div>
            @error('staleThresholdDays') <span class="text-xs" style="color: var(--error);">{{ $message }}</span> @enderror
            <p class="mt-1 text-xs" style="color: var(--text-tertiary);">Default for all repos. Per-repo overrides below.</p>
        </div>

        <div>
            <label class="block text-xs font-medium" style="color: var(--text-secondary);">GitHub token</label>
            <p class="mt-1 font-mono text-xs" style="color: var(--text-tertiary);">{{ $githubToken ?? 'Not configured (set GITHUB_TOKEN env)' }}</p>
        </div>
    </form>

    <div class="mt-6 border-t pt-4" style="border-color: var(--border);">
        <h3 class="mb-3 text-xs font-semibold uppercase" style="color: var(--text-secondary);">Per-repository overrides</h3>
        @forelse ($repos as $repo)
            @php $repoId = $repo->id; $threshold = $repoThresholds[$repoId] ?? null; @endphp
                <div class="mb-3 flex items-center justify-between gap-2">
                    <span class="truncate text-xs font-medium">{{ $repo->name }}</span>
                    <div class="flex items-center gap-1">
                        <input type="number" wire:model="repoThresholds.{{ $repoId }}" placeholder="global"
                               class="w-20 rounded border px-2 py-1 text-xs" style="border-color: var(--border); background: var(--elevated); color: var(--text-primary);">
                        <button wire:click="saveRepoThreshold({{ $repoId }})" class="rounded px-2 py-1 text-xs" style="background: var(--elevated); border: 1px solid var(--border); color: var(--text-primary);">Save</button>
                    </div>
                </div>
                @error("repoThresholds.{$repoId}") <span class="mb-2 block text-xs" style="color: var(--error);">{{ $message }}</span> @enderror
        @empty
            <p class="text-xs text-slate-500">No repositories synced yet.</p>
        @endforelse
        <p class="text-xs text-slate-400">Leave blank to use global threshold.</p>
    </div>
</div>
