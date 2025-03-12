<?php

return [
    'paths' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Content-Disposition', 'Content-Type'],
    'max_age' => 0,
    'supports_credentials' => true,
];
