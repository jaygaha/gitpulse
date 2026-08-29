<nav class="space-y-1" style="min-width: 220px;">
    @forelse ($repos as $repo)
        <button wire:click="selectRepo('{{ $repo->name }}')"
                class="flex w-full items-center justify-between rounded px-3 py-2 text-left text-xs"
                style="{{ $repo->name === $selectedSlug ? 'background: var(--elevated); border: 1px solid var(--border); color: var(--text-primary); font-weight: 600;' : 'background: transparent; border: 1px solid transparent; color: var(--text-secondary);' }}">
            <span class="truncate font-mono">{{ $repo->fullName }}</span>
            @if ($repo->private)
                <span class="ml-2 rounded px-1.5 py-0.5 text-[10px] font-medium" style="background: var(--warning-dim); color: var(--warning); border: 1px solid var(--warning);">private</span>
            @endif
        </button>
    @empty
        <p class="px-3 py-4 text-xs" style="color: var(--text-tertiary);">No repositories.</p>
    @endforelse
</nav>
