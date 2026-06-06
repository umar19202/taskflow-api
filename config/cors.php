<?php

return [
    'paths'                => ['api/*'],
    'allowed_methods'      => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins'      => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),
    'allowed_origins_patterns' => [],
    'allowed_headers'      => ['Content-Type', 'Authorization', 'X-Request-ID', 'Accept'],
    'exposed_headers'      => ['X-Request-ID'],
    'max_age'              => 86400,
    'supports_credentials' => false,
];
