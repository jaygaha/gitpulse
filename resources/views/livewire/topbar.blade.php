<div x-data="{ open: @entangle('showModal') }" @keydown.window.meta.k.prevent="open = true; $wire.openModal()" @keydown.window.ctrl.k.prevent="open = true; $wire.openModal()" @keydown.window.escape="open = false; $wire.closeModal()">
<header class="topbar">
    <div class="topbar-left">
        <a href="{{ route('dashboard') }}" wire:navigate class="logo">
            <span class="logo-dot"></span>
            <span>gitpulse</span>
        </a>
        <button class="repo-trigger" @click="open = true; $wire.openModal()" :aria-expanded="open.toString()" x-bind:aria-expanded="open.toString()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <span>{{ $current?->fullName ?? 'Select repo' }}</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </button>
    </div>
    <div class="topbar-right">
        <button class="icon-button" id="themeToggle" title="Toggle theme" aria-label="Toggle theme">
            <svg id="themeIconMoon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            <svg id="themeIconSun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
        </button>
        <div class="cmd-hint"><kbd>⌘</kbd> <kbd>K</kbd> search</div>
    </div>
</header>

    <div class="modal-backdrop" :class="{ 'open': open }" x-show="open" x-transition.opacity @click.self="open = false; $wire.closeModal()" @keydown.escape.window="open = false; $wire.closeModal()" x-effect="if (open) $nextTick(() => $refs.searchInput?.focus())" style="display: none;">
        <div class="modal" @click.outside="open = false; $wire.closeModal()">
            <div class="modal-input-wrap">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input class="modal-input" wire:model.live.debounce.150ms="search" placeholder="Search repositories..." x-ref="searchInput"
                       wire:keydown.arrow-up.prevent="moveUp"
                       wire:keydown.arrow-down.prevent="moveDown({{ $filtered->count() }})"
                       wire:keydown.enter.prevent="selectActive"
                       wire:keydown.escape="open = false; $wire.closeModal()">
            </div>
            <div class="modal-list">
                @forelse ($filtered as $idx => $repo)
                    <div class="modal-item {{ $idx === $activeIndex ? 'active' : '' }}" wire:click="selectRepo('{{ $repo->name }}')">
                        <svg class="modal-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                        <span class="modal-item-text">{{ $repo->fullName }}</span>
                        @if ($repo->private) <span class="ml-auto text-[10px] text-amber-600">private</span> @endif
                    </div>
                @empty
                    <div class="p-4 text-center text-xs" style="color: var(--text-secondary)">No repositories.</div>
                @endforelse
            </div>
            <div class="modal-footer">
                <span><kbd>↵</kbd> select · <kbd>↑↓</kbd> navigate · <kbd>esc</kbd> close</span>
                <span>{{ $filtered->count() }} repos</span>
            </div>
        </div>
    </div>
</div>

