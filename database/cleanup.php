<?php
require_once __DIR__ . '/../config/db_connect.php';

// Get all migration files
$migrationFiles = glob(__DIR__ . '/versions/*.php');

// Sort by timestamp
usort($migrationFiles, function($a, $b) {
    return basename($a) <=> basename($b);
});

// Keep only the latest initial schema
$latestInitial = null;
foreach ($migrationFiles as $file) {
    if (strpos(basename($file), 'initial_schema') !== false) {
        $latestInitial = $file;
    }
}

// Delete older initial schema files
foreach ($migrationFiles as $file) {
    if ($file !== $latestInitial && strpos(basename($file), 'initial_schema') !== false) {
        unlink($file);
        echo "Deleted: " . basename($file) . "\n";
    }
}

echo "Cleanup completed. Latest initial schema: " . basename($latestInitial) . "\n"; 