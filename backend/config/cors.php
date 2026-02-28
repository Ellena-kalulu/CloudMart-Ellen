<?php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    // Development: allow all origins to avoid CORS-related "Failed to fetch"
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];