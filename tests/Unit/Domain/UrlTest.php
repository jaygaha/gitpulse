<?php

use App\Domain\Shared\Url;

it('accepts https urls', function () {
    expect((new Url('https://github.com/o/r'))->value)->toBe('https://github.com/o/r');
});

it('rejects non-https or malformed urls', function (string $url) {
    new Url($url);
})->throws(InvalidArgumentException::class)->with([
    'javascript:alert(1)',
    'data:text/html,<script>',
    'http://insecure.example.test',
    'ftp://files.example.test',
    'not-a-url',
]);

it('allows null through nullable helpers', function () {
    expect(Url::nullable(null))->toBeNull()
        ->and(Url::nullable('https://github.com/a/b'))->toBe('https://github.com/a/b');
});
