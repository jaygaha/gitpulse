<div>
    @if (session('status'))
        <div style="max-width: 900px; margin: 24px auto 0; padding: 0 24px;">
            <div style="background: var(--success); color: white; padding: 8px 12px; font-family: var(--font-mono); font-size: 12px;">{{ session('status') }}</div>
        </div>
    @endif

    <div class="section-title">Dashboard</div>
    <div class="section-desc">At-a-glance portfolio health. Every repo, one screen. Click a row to drill into findings.</div>

    <div class="kpi-strip" style="margin-top: 24px;">
        <div class="kpi-card">
            <div class="kpi-label">Repos</div>
            <div class="kpi-value">{{ $kpis['repos'] }}</div>
            <div class="kpi-sub">{{ $kpis['enabled'] }} enabled</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Critical</div>
            <div class="kpi-value error">{{ $kpis['critical'] }}</div>
            <div class="kpi-sub">needs action</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Warnings</div>
            <div class="kpi-value" style="color: var(--warning);">{{ $kpis['warning'] }}</div>
            <div class="kpi-sub">stale + alerts</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Healthy</div>
            <div class="kpi-value success">{{ $kpis['healthy'] }}</div>
            <div class="kpi-sub">all clear</div>
        </div>
    </div>

    @if ($lastScan)
        <div class="scan-banner">
            <div class="scan-status">
                <div class="scan-indicator {{ $lastScan->status === 'failed' ? 'error' : ($lastScan->status === 'running' ? 'warning' : '') }}"></div>
                <div class="scan-text"><strong>{{ $lastScan->status }}</strong> · {{ $lastScan->updated_at->diffForHumans() }} · {{ $lastScan->repos_scanned }} repos</div>
            </div>
            <div class="scan-meta">
                <button wire:click="scanNow" style="background: var(--brand); color: #000; border: none; padding: 4px 10px; font-family: var(--font-mono); font-size: 11px; cursor: pointer;">Scan now</button>
            </div>
        </div>
    @else
        <div class="scan-banner">
            <div class="scan-status">
                <div class="scan-indicator"></div>
                <div class="scan-text">No scans yet</div>
            </div>
            <button wire:click="scanNow" style="background: var(--brand); color: #000; border: none; padding: 8px 16px; font-family: var(--font-mono); font-size: 12px; cursor: pointer;">Scan now</button>
        </div>
    @endif

    <div class="repo-table-wrap" style="margin-top: 24px;">
        <div class="repo-table">
            <div class="repo-table-header">
                <div class="repo-table-cell repo-table-cell--status">Status</div>
                <div class="repo-table-cell repo-table-cell--name">Repository</div>
                <div class="repo-table-cell repo-table-cell--issues">Issues</div>
                <div class="repo-table-cell repo-table-cell--prs">PRs</div>
                <div class="repo-table-cell repo-table-cell--alerts">Alerts</div>
                <div class="repo-table-cell repo-table-cell--updated">Last scan</div>
            </div>

            @forelse ($rows as $row)
                <a wire:key="repo-{{ $row['repo']->id }}" href="{{ route('repo.detail', $row['repo']->name) }}" wire:navigate class="repo-table-row repo-table-row--{{ $row['status'] }}" style="text-decoration: none; color: inherit;">
                    <div class="repo-table-cell repo-table-cell--status">
                        <span class="repo-status-dot repo-status-dot--{{ $row['status'] }}"></span>
                        <span class="repo-status-label">{{ $row['status'] }}</span>
                    </div>
                    <div class="repo-table-cell repo-table-cell--name">
                        <span class="repo-name">{{ $row['repo']->fullName }}</span>
                    </div>
                    <div class="repo-table-cell repo-table-cell--issues">
                        <span class="repo-mono">{{ $row['issues'] }}</span>
                        @if (($row['staleIssues'] ?? 0) > 0)
                            <span class="ml-1 rounded px-1 text-[10px] font-semibold" style="background: var(--warning-dim); color: var(--warning); border: 1px solid var(--warning);">{{ $row['staleIssues'] }} stale</span>
                        @endif
                    </div>
                    <div class="repo-table-cell repo-table-cell--prs">
                        <span class="repo-mono">{{ $row['prs'] }}</span>
                        @if (($row['stalePrs'] ?? 0) > 0)
                            <span class="ml-1 rounded px-1 text-[10px] font-semibold" style="background: var(--warning-dim); color: var(--warning); border: 1px solid var(--warning);">{{ $row['stalePrs'] }} stale</span>
                        @endif
                    </div>
                    <div class="repo-table-cell repo-table-cell--alerts">
                        @if ($row['critical'] > 0)
                            <span class="repo-alert-count repo-alert-count--critical">{{ $row['critical'] }} critical</span>
                        @elseif ($row['warning'] > 0)
                            <span class="repo-alert-count repo-alert-count--warning">{{ $row['warning'] }} high</span>
                        @else
                            <span class="repo-alert-count repo-alert-count--none">0</span>
                        @endif
                    </div>
                    <div class="repo-table-cell repo-table-cell--updated">
                        <span class="repo-mono">{{ $row['repo']->lastScannedAt ? $row['repo']->lastScannedAt->diffForHumans() : 'never' }}</span>
                    </div>
                </a>
            @empty
                <div class="empty-state">
                    <div class="empty-state-icon">⌀</div>
                    <div class="empty-state-title">No findings yet</div>
                    <div class="empty-state-desc">Run your first scan to start monitoring repositories.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
