<?php
return [
    'migrations' => [
        'table' => 'migrations',
        'path' => __DIR__ . '/versions',
        'log_path' => __DIR__ . '/logs',
    ],
    'backup' => [
        'enabled' => true,
        'path' => __DIR__ . '/backups',
        'keep_for_days' => 7
    ]
]; 