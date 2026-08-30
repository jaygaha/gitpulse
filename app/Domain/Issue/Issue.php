<?php

declare(strict_types=1);

namespace App\Domain\Issue;

use App\Domain\Shared\Url;
use Carbon\CarbonInterface;

final class Issue
{
    public readonly int $githubId;

    public readonly int $number;

    public readonly string $htmlUrl;

    /** @param  list<string>  $labels */
    public function __construct(
        int $githubId,
        int $number,
        public readonly string $title,
        public readonly string $state,
        public readonly array $labels,
        public readonly ?string $assignee,
        public readonly ?CarbonInterface $lastActivityAt,
        string $htmlUrl,
    ) {
        if ($githubId <= 0) {
            throw new \InvalidArgumentException("Issue githubId must be positive, got {$githubId}.");
        }

        $this->githubId = $githubId;
        $this->number = (new IssueNumber($number))->value;
        $this->htmlUrl = (new Url($htmlUrl))->value;
    }

    public function isOpen(): bool
    {
        return $this->state === 'open';
    }
}
