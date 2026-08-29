<div>
    <div class="repo-detail-wrap" style="margin-top: 24px;">
        <div class="repo-header">
            <div class="repo-header-title">
                <span class="repo-header-name">{{ $repository->fullName }}</span>
                <span class="repo-header-badge {{ $critical + $high > 0 ? 'repo-header-badge--enabled' : 'repo-header-badge--disabled' }}">{{ $critical + $high > 0 ? 'attention' : 'healthy' }}</span>
            </div>
            <div class="repo-header-description">Synced from GitHub · {{ $repository->htmlUrl }}</div>
            <div class="repo-header-meta">
                <span class="repo-lang"><span class="repo-lang-dot" style="background-color: #f59e0b;"></span> {{ $repository->isPrivate() ? 'Private' : 'Public' }}</span>
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

    @if ($critical === 0 && $staleIssues === 0 && $stalePrs === 0)
        <div class="empty-state" style="margin-top: 24px;">
            <div class="empty-state-icon">⌀</div>
            <div class="empty-state-title">No findings yet</div>
            <div class="empty-state-desc">All clear — no actionable findings for {{ $repository->name }}.</div>
        </div>
    @else
        <div class="feed">
            @if ($criticalFeed->isNotEmpty())
                <div class="feed-section">
                    <div class="feed-section-header">Critical alerts — {{ $criticalFeed->count() }}</div>
                    @foreach ($criticalFeed as $item)
                        <div class="feed-item critical">
                            <div class="feed-icon critical">!</div>
                            <div class="feed-content">
                                <div class="feed-title">{{ $item['title'] }}</div>
                                <div class="feed-meta">
                                    <span class="feed-badge critical">critical</span>
                                    <span class="feed-meta-sep">·</span>
                                    <span class="feed-meta-item">{{ $repository->fullName }}</span>
                                    <span class="feed-meta-sep">·</span>
                                    <span class="feed-meta-item">{{ $item['meta'] }}</span>
                                    <span class="feed-meta-sep">·</span>
                                    <span class="feed-meta-item">{{ $item['time'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($staleIssuesFeed->isNotEmpty())
                <div class="feed-section">
                    <div class="feed-section-header">Stale issues — {{ $staleIssuesFeed->count() }}</div>
                    @foreach ($staleIssuesFeed as $item)
                        <div class="feed-item warning">
                            <div class="feed-icon warning">#</div>
                            <div class="feed-content">
                                <div class="feed-title">{{ $item['title'] }}</div>
                                <div class="feed-meta">
                                    <span class="feed-badge warning">stale</span>
                                    <span class="feed-meta-sep">·</span>
                                    <span class="feed-meta-item">{{ $item['meta'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($stalePrsFeed->isNotEmpty())
                <div class="feed-section">
                    <div class="feed-section-header">Stale PRs — {{ $stalePrsFeed->count() }}</div>
                    @foreach ($stalePrsFeed as $item)
                        <div class="feed-item warning">
                            <div class="feed-icon warning">PR</div>
                            <div class="feed-content">
                                <div class="feed-title">{{ $item['title'] }}</div>
                                <div class="feed-meta">
                                    <span class="feed-badge warning">stale</span>
                                    <span class="feed-meta-sep">·</span>
                                    <span class="feed-meta-item">{{ $item['meta'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($critical === 0 && $staleIssues === 0 && $stalePrs === 0)
                <div class="feed-section">
                    <div class="feed-section-header">Healthy</div>
                    <div class="feed-item info">
                        <div class="feed-icon info">—</div>
                        <div class="feed-content">
                            <div class="feed-title">All clear — no actionable findings</div>
                            <div class="feed-meta"><span class="feed-badge success">healthy</span></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
