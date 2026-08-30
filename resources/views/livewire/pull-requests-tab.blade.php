<div class="rounded-lg border p-4" style="border-color: var(--border); background: var(--surface);">
    <div class="mb-3 flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <input type="text" wire:model.live.debounce.250ms="search" placeholder="Search PRs..." class="rounded border px-2 py-1 text-xs" style="border-color: var(--border); background: var(--elevated); color: var(--text-primary); width: 200px;">
            <select wire:model.live="stateFilter" class="rounded border px-2 py-1 text-xs" style="border-color: var(--border); background: var(--elevated); color: var(--text-primary);">
                <option value="all">All states</option>
                <option value="open">Open</option>
                <option value="closed">Closed</option>
                <option value="merged">Merged</option>
            </select>
        </div>
        <span class="text-xs" style="color: var(--text-secondary);">{{ $total }} PRs</span>
    </div>

    <div class="overflow-auto">
        <table class="w-full text-left text-xs" style="color: var(--text-primary);">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); color: var(--text-secondary);">
                    <th class="px-2 py-2 cursor-pointer" wire:click="sortBy('number')">#</th>
                    <th class="px-2 py-2 cursor-pointer" wire:click="sortBy('title')">Title</th>
                    <th class="px-2 py-2">Author</th>
                    <th class="px-2 py-2 cursor-pointer" wire:click="sortBy('state')">State</th>
                    <th class="px-2 py-2 cursor-pointer" wire:click="sortBy('stale')">Stale</th>
                    <th class="px-2 py-2">Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($paginator as $row)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-2 py-2 font-mono"><a href="{{ $row['pr']->htmlUrl }}" target="_blank" style="color: var(--brand);">#{{ $row['pr']->number }}</a></td>
                        <td class="px-2 py-2">
                            <div class="font-medium">{{ $row['pr']->title }}</div>
                            <div class="font-mono text-[10px]" style="color: var(--text-secondary);">{{ $row['pr']->baseRef }} ← {{ $row['pr']->headRef }}</div>
                        </td>
                        <td class="px-2 py-2">{{ $row['pr']->author ?? '—' }}</td>
                        <td class="px-2 py-2"><span class="rounded px-1.5 py-0.5 text-[10px] {{ $row['pr']->state === 'open' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-slate-500/10 text-slate-500' }}">{{ $row['pr']->state }}</span></td>
                        <td class="px-2 py-2">
                            @if ($row['isStale'] && $row['pr']->isOpen())
                                <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold" style="background: var(--warning-dim); color: var(--warning); border: 1px solid var(--warning);">stale</span>
                            @else
                                <span class="text-[10px]" style="color: var(--text-tertiary);">—</span>
                            @endif
                        </td>
                        <td class="px-2 py-2 font-mono text-[11px]" style="color: var(--text-secondary);">{{ $row['pr']->lastActivityAt?->diffForHumans() ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-2 py-8 text-center" style="color: var(--text-secondary);">No pull requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $paginator->links() }}
    </div>
</div>
