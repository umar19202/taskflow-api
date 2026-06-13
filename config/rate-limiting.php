<?php

return [
    'api' => (int) env('RATE_LIMIT_API', 60),
    'auth' => (int) env('RATE_LIMIT_AUTH', 10),
    'writes' => (int) env('RATE_LIMIT_WRITES', 30),
];
