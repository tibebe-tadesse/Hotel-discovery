<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/Migration.php';

$stmt = $pdo->query("
    SELECT migration, batch, created_at 
    FROM migrations 
    ORDER BY batch, id
");

echo "Migration Status:\n";
echo str_repeat('-', 80) . "\n";
echo sprintf("%-40s %-10s %-20s\n", 'Migration', 'Batch', 'Created At');
echo str_repeat('-', 80) . "\n";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf(
        "%-40s %-10s %-20s\n",
        $row['migration'],
        $row['batch'],
        $row['created_at']
    );
} 