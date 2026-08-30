<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Shared\Url;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class Repository
{
    public readonly int $githubId;

    public readonly string $htmlUrl;

    public function __construct(
        int $githubId,
        public readonly string $name,
        public readonly string $fullName,
        public readonly string $owner,
        public readonly bool $private,
        string $htmlUrl,
        public readonly ?int $staleThresholdDays = null,
        public readonly ?CarbonInterface $lastScannedAt = null,
        public readonly bool $archived = false,
        public readonly ?int $id = null,
    ) {
        if ($githubId <= 0) {
            throw new InvalidArgumentException("Repository githubId must be positive, got {$githubId}.");
        }

        if ($staleThresholdDays !== null && $staleThresholdDays <= 0) {
            throw new InvalidArgumentException("Staleness override must be positive when set, got {$staleThresholdDays}.");
        }

        $this->githubId = $githubId;
        $this->htmlUrl = (new Url($htmlUrl))->value;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }
}
