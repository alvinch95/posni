<?php

return [
    // Shared secret the Next.js server sends in the X-Website-Key header. Requests that
    // present it get the higher server rate limit (all SSR traffic shares one IP/bucket).
    'api_key' => env('WEBSITE_API_KEY'),

    // Requests per minute: trusted Next.js server vs. anyone else (per IP).
    'server_rate_per_minute' => 1000,
    'public_rate_per_minute' => 60,
];
