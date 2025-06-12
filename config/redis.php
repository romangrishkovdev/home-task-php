<?php

return [
    'host' => env('REDIS_HOST', 'redis'),
    'password' => env('REDIS_PASSWORD', null),
    'port' => env('REDIS_PORT', 6379),
    'database' => 0,
    'batch_size' => 1000,
    'ttl' => 3600,
    'max_csv_size' => 104857600, // 100MB
    'queue' => 'csv_queue',
]; 