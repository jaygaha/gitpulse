<?php

return [

    'token' => env('GITHUB_TOKEN'),

    'per_page' => (int) env('GITHUB_PER_PAGE', 100),

    // Seconds to sleep when X-RateLimit-Remaining drops below the guard threshold.
    'rate_limit_pause' => (int) env('GITHUB_RATE_LIMIT_PAUSE', 1),

    // Upper bound, in seconds, for any single throttled-retry wait.
    'rate_limit_retry_cap' => (int) env('GITHUB_RATE_LIMIT_RETRY_CAP', 30),

];
