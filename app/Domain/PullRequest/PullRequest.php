<?php

declare(strict_types=1);

namespace App\Domain\PullRequest;

use App\Domain\Shared\Url;
use Carbon\CarbonInterface;

final class PullRequest
{
    public readonly int $githubId;

    public readonly int $number;

    public readonly string $htmlUrl;

    /** @param  array<string, mixed>  $checksStatus */
    public function __construct(
        int $githubId,
        int $number,
        public readonly string $title,
        public readonly string $state,
        public readonly ?string $author,
        public readonly string $baseRef,
        public readonly string $headRef,
        public readonly ?CarbonInterface $lastActivityAt,
        public readonly array $checksStatus,
        string $htmlUrl,
        public readonly ?CarbonInterface $mergedAt = null,
    ) {
        if ($githubId <= 0) {
            throw new \InvalidArgumentException("Pull request githubId must be positive, got {$githubId}.");
        }

        $this->githubId = $githubId;
        $this->number = (new PRNumber($number))->value;
        $this->htmlUrl = Url::assertHttps($htmlUrl);
    }

    public function isOpen(): bool
    {
        return $this->state === 'open';
    }

    public function isMerged(): bool
    {
        return $this->mergedAt !== null;
    }
}
