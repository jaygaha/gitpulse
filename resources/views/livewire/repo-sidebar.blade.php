<nav class="space-y-1">
    @foreach ($repos as $repo)
        <button wire:click="selectRepo('{{ $repo->name }}')"
                class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm
                       {{ $repo->name === $selectedSlug
                           ? 'bg-slate-200 font-semibold dark:bg-slate-800'
                           : 'hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
            <span class="truncate">{{ $repo->name }}</span>
            @if ($repo->private)
                <span class="ml-2 rounded bg-amber-100 px-1.5 text-[10px] font-medium text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">private</span>
            @endif
        </button>
    @endforeach
</nav>
