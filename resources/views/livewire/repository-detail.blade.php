<div>
    <div class="repo-detail-wrap" style="margin-top: 24px;">
        <div class="repo-header">
            <div class="repo-header-title">
                <span class="repo-header-name">{{ $repository->fullName }}</span>
                <span class="repo-header-badge {{ $critical + $high > 0 ? 'repo-header-badge--enabled' : 'repo-header-badge--disabled' }}">{{ $critical + $high > 0 ? 'attention' : 'healthy' }}</span>
            </div>
            <div class="repo-header-description">Synced from GitHub · {{ $repository->htmlUrl }}</div>
            <div class="repo-header-meta">
                <span class="repo-lang"><span class="repo-lang-dot" style="background-color: #f59e0b;"></span> {{ $repository->private ? 'Private' : 'Public' }}</span>
                <span class="repo-meta-sep">·</span>
                <span class="repo-meta-item">{{ $totalIssues }} issues</span>
                <span class="repo-meta-sep">·</span>
                <span class="repo-meta-item">{{ $totalPrs }} pull requests</span>
                <span class="repo-meta-sep">·</span>
                <a href="{{ $repository->htmlUrl }}" target="_blank" class="repo-meta-item" style="color: var(--brand);">View on GitHub →</a>
            </div>
        </div>
    </div>

    <div class="kpi-strip" style="margin-top: 24px;">
        <div class="kpi-card">
            <div class="kpi-label">Critical</div>
            <div class="kpi-value error">{{ $critical }}</div>
            <div class="kpi-sub">security alerts</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">High</div>
            <div class="kpi-value" style="color: var(--warning);">{{ $high }}</div>
            <div class="kpi-sub">security alerts</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Moderate</div>
            <div class="kpi-value">{{ $moderate }}</div>
            <div class="kpi-sub">security alerts</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Low</div>
            <div class="kpi-value">{{ $low }}</div>
            <div class="kpi-sub">security alerts</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Stale issues</div>
            <div class="kpi-value" style="color: var(--warning);">{{ $staleIssues }}</div>
            <div class="kpi-sub">{{ $totalIssues }} open</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Stale PRs</div>
            <div class="kpi-value" style="color: var(--warning);">{{ $stalePrs }}</div>
            <div class="kpi-sub">{{ $totalPrs }} open</div>
        </div>
    </div>

    @if ($lastScan)
        <div class="scan-banner">
            <div class="scan-status">
                <div class="scan-indicator {{ $critical > 0 ? 'error' : ($staleIssues + $stalePrs > 0 ? 'warning' : '') }}"></div>
                <div class="scan-text"><strong>Last scan:</strong> {{ $lastScan->updated_at->diffForHumans() }} · <strong>Findings:</strong> {{ $critical }} critical, {{ $staleIssues }} stale</div>
            </div>
            <div class="scan-meta">next scan in 28m</div>
        </div>
    @endif

    <div class="mt-6 flex gap-2 border-b" style="border-color: var(--border);">
        <button wire:click="setTab('issues')" class="px-3 py-2 text-xs font-semibold {{ $activeTab === 'issues' ? 'border-b-2' : '' }}" style="{{ $activeTab === 'issues' ? 'border-color: var(--brand); color: var(--brand);' : 'color: var(--text-secondary);' }}">Issues ({{ $totalIssues }}) @if($staleIssues>0)<span class="ml-1 rounded px-1 text-[10px]" style="background: var(--warning-dim); color: var(--warning);">{{ $staleIssues }} stale</span>@endif</button>
        <button wire:click="setTab('prs')" class="px-3 py-2 text-xs font-semibold {{ $activeTab === 'prs' ? 'border-b-2' : '' }}" style="{{ $activeTab === 'prs' ? 'border-color: var(--brand); color: var(--brand);' : 'color: var(--text-secondary);' }}">Pull Requests ({{ $totalPrs }}) @if($stalePrs>0)<span class="ml-1 rounded px-1 text-[10px]" style="background: var(--warning-dim); color: var(--warning);">{{ $stalePrs }} stale</span>@endif</button>
        <button wire:click="setTab('alerts')" class="px-3 py-2 text-xs font-semibold {{ $activeTab === 'alerts' ? 'border-b-2' : '' }}" style="{{ $activeTab === 'alerts' ? 'border-color: var(--brand); color: var(--brand);' : 'color: var(--text-secondary);' }}">Security Alerts ({{ $critical + $high + $moderate + $low }}) @if($critical>0)<span class="ml-1 rounded px-1 text-[10px]" style="background: var(--error-dim); color: var(--error);">{{ $critical }} critical</span>@endif</button>
    </div>

    <div class="mt-4">
        @if ($activeTab === 'issues')
            <livewire:issues-tab :repository-id="$repository->id" :key="'issues-'.$repository->id" />
        @elseif ($activeTab === 'prs')
            <livewire:pull-requests-tab :repository-id="$repository->id" :key="'prs-'.$repository->id" />
        @elseif ($activeTab === 'alerts')
            <livewire:security-alerts-tab :repository-id="$repository->id" :key="'alerts-'.$repository->id" />
        @endif
    </div>

    @if ($critical === 0 && $staleIssues === 0 && $stalePrs === 0)
        <div class="empty-state" style="margin-top: 24px;">
            <div class="empty-state-icon">⌀</div>
            <div class="empty-state-title">No findings yet</div>
            <div class="empty-state-desc">All clear — no actionable findings for {{ $repository->name }}. Use the tabs above to browse all items.</div>
        </div>
    @endif
</div>
