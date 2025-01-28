<?php
// Get PDO connection
$pdo = require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/Migration.php';
require_once __DIR__ . '/config.php';

use Database\Migration;

class DatabaseReset extends Migration {
    public function up() {}
    public function down() {}

    private function sortTablesByDependencies($statements) {
        $tables = [];
        $dependencies = [];
        
        // First pass: collect tables and their dependencies
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            
            // Skip non-CREATE TABLE statements
            if (!preg_match('/CREATE TABLE `?(\w+)`?/i', $statement, $matches)) continue;
            
            $tableName = $matches[1];
            $tables[$tableName] = $statement;
            
            // Find foreign key dependencies
            preg_match_all('/FOREIGN KEY.*REFERENCES `?(\w+)`?/i', $statement, $fkMatches);
            $dependencies[$tableName] = $fkMatches[1] ?? [];
        }
        
        // Topological sort
        $sorted = [];
        $visited = [];
        
        $visit = function($table) use (&$visit, &$sorted, &$visited, &$dependencies, &$tables) {
            if (!isset($tables[$table])) return;
            if (isset($visited[$table])) return;
            
            $visited[$table] = true;
            
            foreach ($dependencies[$table] as $dependency) {
                $visit($dependency);
            }
            
            $sorted[$table] = $tables[$table];
        };
        
        foreach (array_keys($tables) as $table) {
            $visit($table);
        }
        
        return $sorted;
    }

    public function reset() {
        try {
            // Get schema content
            $schemaFile = __DIR__ . '/schema.sql';
            if (!file_exists($schemaFile)) {
                throw new \Exception("Schema file not found: schema.sql");
            }

            $schema = file_get_contents($schemaFile);
            
            echo "Starting database reset...\n";

            // Disable foreign key checks
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            
            try {
                // Drop all existing tables
                $tables = $this->pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
                }
                
                // Parse and sort statements
                $statements = array_filter(
                    array_map('trim', explode(';', $schema))
                );
                $sortedTables = $this->sortTablesByDependencies($statements);

                // Create tables in correct order
                foreach ($sortedTables as $tableName => $statement) {
                    $this->pdo->exec($statement);
                    echo "Created table: $tableName\n";
                }
                
                // Create indexes
                foreach ($statements as $statement) {
                    if (empty(trim($statement))) continue;
                    if (!preg_match('/^CREATE\s+INDEX/i', trim($statement))) continue;
                    
                    $this->pdo->exec($statement);
                    if (preg_match('/CREATE\s+INDEX\s+(\w+)/i', $statement, $matches)) {
                        echo "Created index: {$matches[1]}\n";
                    }
                }
                
                // Create migrations table
                $this->createMigrationsTable();
                echo "Created migrations table\n";
                
                echo "\nDatabase reset completed successfully!\n";
                
                // Display final table summary
                $this->displayTableSummary();
                
            } finally {
                // Always re-enable foreign key checks
                $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            }
            
        } catch (\Exception $e) {
            $errorDetails = $this->handleError($e);
            echo "\nError during reset: {$errorDetails['message']}\n";
            echo "Check logs for details: " . __DIR__ . "/logs/migration_errors.log\n";
            exit(1);
        }
    }
}

// Make sure PDO connection exists
if (!($pdo instanceof PDO)) {
    die("Database connection not available. Check your db_connect.php file.\n");
}

// Add confirmation prompt
echo "WARNING: This will delete all data and reset the database to its initial state.\n";
echo "Are you sure you want to continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim(strtolower($line)) != 'yes') {
    echo "Reset cancelled.\n";
    exit;
}

// Execute reset
try {
    $reset = new DatabaseReset($pdo);
    $reset->reset();
} catch (\Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
} 