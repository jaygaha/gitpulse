<?php

declare(strict_types=1);

namespace App\Domain\SecurityAlert;

use App\Domain\Shared\Url;
use Carbon\CarbonInterface;

final class SecurityAlert
{
    public readonly int $githubId;

    public readonly ?string $advisoryUrl;

    public readonly string $htmlUrl;

    public function __construct(
        int $githubId,
        public readonly AlertType $type,
        public readonly Severity $severity,
        public readonly ?string $packageName,
        public readonly ?string $summary,
        ?string $advisoryUrl,
        public readonly ?CarbonInterface $dismissedAt,
        public readonly ?CarbonInterface $fixedAt,
        string $htmlUrl,
    ) {
        if ($githubId <= 0) {
            throw new \InvalidArgumentException("Security alert githubId must be positive, got {$githubId}.");
        }

        $this->githubId = $githubId;
        $this->advisoryUrl = Url::nullable($advisoryUrl);
        $this->htmlUrl = Url::assertHttps($htmlUrl);
    }

    public function isDismissed(): bool
    {
        return $this->dismissedAt !== null;
    }

    public function isFixed(): bool
    {
        return $this->fixedAt !== null;
    }
}
