<?php

namespace App\Infrastructure\GitHub\Mappers;

use App\Domain\SecurityAlert\AlertType;
use App\Domain\SecurityAlert\SecurityAlert;
use App\Domain\SecurityAlert\Severity;
use Carbon\Carbon;

final class SecurityAlertMapper
{
    public static function fromDependabotResponse(array $data): SecurityAlert
    {
        return new SecurityAlert(
            githubId: $data['number'],
            type: AlertType::DEPENDABOT,
            severity: Severity::fromString(self::normalizeSeverity($data['security_advisory']['severity'] ?? 'low')),
            packageName: $data['dependency']['package']['name'] ?? null,
            summary: $data['security_advisory']['summary'] ?? null,
            advisoryUrl: $data['security_advisory']['html_url']
                ?? (isset($data['security_advisory']['ghsa_id'])
                    ? 'https://github.com/advisories/'.$data['security_advisory']['ghsa_id']
                    : null),
            dismissedAt: isset($data['dismissed_at']) ? Carbon::parse($data['dismissed_at']) : null,
            fixedAt: isset($data['fixed_at']) ? Carbon::parse($data['fixed_at']) : null,
            htmlUrl: $data['html_url'],
        );
    }

    public static function fromCodeScanningNode(array $node): SecurityAlert
    {
        return new SecurityAlert(
            githubId: $node['number'],
            type: AlertType::CODE_SCANNING,
            severity: Severity::fromString(self::normalizeSeverity(strtolower($node['securitySeverityLevel'] ?? 'note'))),
            packageName: null,
            summary: $node['description'] ?? $node['rule']['description'] ?? null,
            advisoryUrl: null,
            dismissedAt: isset($node['dismissedAt']) ? Carbon::parse($node['dismissedAt']) : null,
            fixedAt: isset($node['fixedAt']) ? Carbon::parse($node['fixedAt']) : null,
            htmlUrl: $node['url'],
        );
    }

    private static function normalizeSeverity(string $value): string
    {
        return match (strtolower(trim($value))) {
            'error' => 'high',
            'warning', 'moderate' => 'medium',
            'note', 'none' => 'low',
            default => strtolower(trim($value)),
        };
    }
}
