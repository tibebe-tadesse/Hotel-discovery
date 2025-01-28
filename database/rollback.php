<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/Migration.php';

use Database\Migration;

// Get the last batch number
$lastBatch = (int)$pdo->query("SELECT MAX(batch) FROM migrations")->fetchColumn();

// Get migrations from the last batch
$stmt = $pdo->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC");
$stmt->execute([$lastBatch]);
$migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($migrations as $migrationName) {
    $file = __DIR__ . '/versions/' . $migrationName . '.php';
    
    if (!file_exists($file)) {
        echo "Migration file not found: $migrationName\n";
        continue;
    }
    
    require_once $file;
    $className = 'Database\\Versions\\Migration_' . $migrationName;
    
    try {
        $pdo->beginTransaction();
        
        $migration = new $className($pdo);
        $migration->down();
        
        // Remove migration record
        $stmt = $pdo->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$migrationName]);
        
        $pdo->commit();
        echo "Rolled back: $migrationName\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error rolling back $migrationName: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "Rollback completed successfully.\n";

// Add dry-run capability
function dryRunRollback($migration, $migrationName) {
    echo "Would rollback: $migrationName\n";
    echo "Tables affected:\n";
    // Get affected tables from migration
    $reflection = new \ReflectionClass($migration);
    $method = $reflection->getMethod('down');
    $code = file_get_contents($reflection->getFileName());
    preg_match_all('/DROP TABLE IF EXISTS (\w+)/', $code, $matches);
    foreach ($matches[1] as $table) {
        echo "- $table\n";
    }
} 