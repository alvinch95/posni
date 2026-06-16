<?php

return [
    // Base domain the Chen subdomain hangs off of. Subdomain = "chen." . this value.
    // Defaults to posni.test so the test suite works with no extra setup.
    'domain' => env('CHEN_DOMAIN', 'posni.test'),
];
