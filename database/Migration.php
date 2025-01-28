<?php
namespace Database;

use Database\Exceptions\MigrationException;
require_once __DIR__ . '/SchemaComparator.php';

abstract class Migration {
    protected $pdo;
    protected $migrationName;
    protected $schemaComparator;
    
    public function __construct(\PDO $pdo) {
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
        $this->migrationName = get_class($this);
        $this->schemaComparator = new SchemaComparator($pdo);
    }

    protected function parseCreateTableStatement($sql) {
        if (preg_match('/CREATE\s+TABLE\s+(?:`)?(\w+)(?:`)?/i', $sql, $matches)) {
            return [
                'table' => $matches[1],
                'sql' => $sql
            ];
        }
        return null;
    }

    protected function smartUpdate($sql) {
        try {
            // Parse the CREATE TABLE statement
            $parsed = $this->parseCreateTableStatement($sql);
            if (!$parsed) {
                // If not a CREATE TABLE statement, execute directly
                $this->pdo->exec($sql);
                return;
            }

            $tableName = $parsed['table'];
            $newSchema = $parsed['sql'];

            // Check if table exists
            $tableExists = $this->pdo->query(
                "SELECT 1 FROM information_schema.tables 
                 WHERE table_schema = DATABASE() 
                 AND table_name = '$tableName'"
            )->fetchColumn();

            if (!$tableExists) {
                // Create new table if it doesn't exist
                $this->pdo->exec($newSchema);
                echo "Created table: $tableName\n";
                return;
            }

            // Get existing schema
            $oldSchema = $this->schemaComparator->getTableSchema($tableName);
            
            // Create temporary database for schema comparison
            $tempDb = "temp_" . uniqid();
            $this->pdo->exec("CREATE DATABASE `$tempDb`");
            $this->pdo->exec("USE `$tempDb`");
            $this->pdo->exec($newSchema);
            $parsedNewSchema = $this->schemaComparator->getTableSchema($tableName);
            
            // Switch back to original database
            $this->pdo->exec("USE " . $this->getCurrentDatabase());
            $this->pdo->exec("DROP DATABASE `$tempDb`");
            
            // Compare schemas and generate ALTER statements
            $differences = $this->schemaComparator->compareSchemas($oldSchema, $parsedNewSchema);
            
            if (empty($differences['added']) && empty($differences['modified']) && empty($differences['removed'])) {
                echo "No changes needed for table: $tableName\n";
                return;
            }

            // Apply changes
            $alterStatement = $this->schemaComparator->generateAlterStatement($tableName, $differences);
            if ($alterStatement) {
                $this->pdo->exec($alterStatement);
                echo "Modified table: $tableName\n";
                
                // Log changes
                foreach ($differences['added'] as $column => $def) {
                    echo "  + Added column: $column ({$def['type']})\n";
                }
                foreach ($differences['modified'] as $column => $def) {
                    echo "  ~ Modified column: $column ({$def['from']['type']} -> {$def['to']['type']})\n";
                }
                foreach ($differences['removed'] as $column => $def) {
                    echo "  - Removed column: $column\n";
                }
            }

        } catch (\Exception $e) {
            throw new MigrationException(
                "Error during smart update: " . $e->getMessage(),
                $this->migrationName,
                $sql,
                ['table' => $tableName ?? null],
                $e
            );
        }
    }

    protected function getCurrentDatabase() {
        return $this->pdo->query("SELECT DATABASE()")->fetchColumn();
    }

    abstract public function up();
    abstract public function down();
    
    public function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            version VARCHAR(20) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
    }

    protected function tableExists($tableName) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = ?
        ");
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    }

    protected function dropTableIfExists($tableName) {
        $this->pdo->exec("DROP TABLE IF EXISTS `$tableName`");
    }

    protected function createTableIfNotExists($tableName, $sql) {
        try {
            if (!$this->tableExists($tableName)) {
                $this->pdo->exec($sql);
            }
        } catch (\PDOException $e) {
            throw new MigrationException(
                "Failed to create table $tableName",
                $this->migrationName,
                $sql,
                ['table' => $tableName],
                $e
            );
        }
    }

    protected function executeSafely($sql, $params = []) {
        try {
            if (stripos(trim($sql), 'CREATE TABLE') === 0) {
                $this->smartUpdate($sql);
                return true;
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new MigrationException(
                "SQL Error: " . $e->getMessage(),
                $this->migrationName,
                $sql,
                ['params' => $params],
                $e
            );
        }
    }

    protected function handleError(\Exception $e) {
        $errorDetails = ($e instanceof MigrationException) ? 
            $e->getDetails() : 
            [
                'migration' => $this->migrationName,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];

        // Ensure logs directory exists
        if (!is_dir(__DIR__ . '/logs')) {
            mkdir(__DIR__ . '/logs', 0755, true);
        }
        
        // Log the error
        $logEntry = array_merge(['timestamp' => date('Y-m-d H:i:s')], $errorDetails);
        file_put_contents(
            __DIR__ . '/logs/migration_errors.log',
            json_encode($logEntry) . "\n",
            FILE_APPEND
        );

        return $errorDetails;
    }

    protected function getTableSummary() {
        $tables = $this->pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        $summary = [];
        
        foreach ($tables as $table) {
            $columns = $this->pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(\PDO::FETCH_COLUMN);
            $summary[] = sprintf("table: %s (%d cols)", $table, count($columns));
        }
        
        return $summary;
    }

    protected function displayTableSummary() {
        $summary = $this->getTableSummary();
        echo "\nDatabase Summary:\n";
        echo str_repeat('-', 50) . "\n";
        echo implode("\n", $summary);
        echo "\n" . str_repeat('-', 50) . "\n";
    }
} 