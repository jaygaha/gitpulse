<?php

namespace App\Domain\Shared;

final readonly class Url
{
    public function __construct(public string $value)
    {
        $parts = parse_url($value);

        if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            throw new \InvalidArgumentException("External URL must be a valid https URL, got \"{$value}\".");
        }
    }

    public static function nullable(?string $value): ?string
    {
        return $value === null ? null : (new self($value))->value;
    }
}
