<div class="rounded-lg border p-4" style="border-color: var(--border); background: var(--surface);">
    @if ($status)
        <div class="mb-3 rounded px-3 py-2 text-xs" style="background: rgba(34,197,94,0.1); color: var(--success); border: 1px solid var(--success);">{{ $status }}</div>
    @endif
    @if ($error)
        <div class="mb-3 rounded px-3 py-2 text-xs" style="background: var(--error-dim); color: var(--error); border: 1px solid var(--error);">{{ $error }}</div>
    @endif

    <div class="mb-3 flex flex-wrap items-center gap-2">
        <input type="text" wire:model.live.debounce.250ms="search" placeholder="Search alerts..." class="rounded border px-2 py-1 text-xs" style="border-color: var(--border); background: var(--elevated); color: var(--text-primary); width: 200px;">
        <select wire:model.live="typeFilter" class="rounded border px-2 py-1 text-xs" style="border-color: var(--border); background: var(--elevated); color: var(--text-primary);">
            <option value="all">All types</option>
            <option value="dependabot">Dependabot</option>
            <option value="code_scanning">Code scanning</option>
        </select>
        <select wire:model.live="severityFilter" class="rounded border px-2 py-1 text-xs" style="border-color: var(--border); background: var(--elevated); color: var(--text-primary);">
            <option value="all">All severities</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
        </select>
        <span class="ml-auto text-xs" style="color: var(--text-secondary);">{{ $total }} alerts</span>
    </div>

    <div class="overflow-auto">
        <table class="w-full text-left text-xs" style="color: var(--text-primary);">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); color: var(--text-secondary);">
                    <th class="px-2 py-2">Severity</th>
                    <th class="px-2 py-2">Type</th>
                    <th class="px-2 py-2">Summary</th>
                    <th class="px-2 py-2">Package</th>
                    <th class="px-2 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($paginator as $alert)
                    <tr wire:key="alert-{{ $alert->githubId }}-{{ $alert->type->value }}" style="border-bottom: 1px solid var(--border);">
                        <td class="px-2 py-2">
                            <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold
                                @if ($alert->severity->value === 'critical') bg-red-500/10 text-red-600
                                @elseif ($alert->severity->value === 'high') bg-orange-500/10 text-orange-600
                                @elseif ($alert->severity->value === 'medium') bg-amber-500/10 text-amber-600
                                @else bg-slate-500/10 text-slate-500
                                @endif
                            ">{{ $alert->severity->value }}</span>
                        </td>
                        <td class="px-2 py-2 font-mono text-[11px]">{{ $alert->type->value }}</td>
                        <td class="px-2 py-2">
                            <div class="max-w-[260px] truncate">{{ $alert->summary ?? '—' }}</div>
                            <a href="{{ $alert->htmlUrl }}" target="_blank" class="text-[10px] underline" style="color: var(--brand);">#{{ $alert->githubId }}</a>
                        </td>
                        <td class="px-2 py-2 font-mono text-[11px]">{{ $alert->packageName ?? '—' }}</td>
                        <td class="px-2 py-2">
                            <button wire:click="dismiss({{ $alert->githubId }}, '{{ $alert->type->value }}')" wire:confirm="Dismiss alert #{{ $alert->githubId }}?" class="rounded px-2 py-1 text-[11px] font-semibold" style="background: var(--elevated); border: 1px solid var(--border); color: var(--text-primary);">Dismiss</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-2 py-8 text-center" style="color: var(--text-secondary);">No alerts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $paginator->links() }}
    </div>
</div>
