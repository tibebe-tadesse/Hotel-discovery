<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/Migration.php';
require_once __DIR__ . '/config.php';

use Database\Migration;

class MigrationManager extends Migration {
    protected $errors = [];
    protected $succeeded = [];
    
    public function up() {}
    public function down() {}

    public function run() {
        try {
            $this->createMigrationsTable();
            
            $migrationFiles = glob(__DIR__ . '/versions/*.php');
            $batch = (int)$this->pdo->query("SELECT COALESCE(MAX(batch), 0) FROM migrations")->fetchColumn() + 1;
            $executedMigrations = $this->pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($migrationFiles as $file) {
                $migrationName = basename($file, '.php');
                
                if (in_array($migrationName, $executedMigrations)) {
                    echo "Skipping: $migrationName (already executed)\n";
                    continue;
                }
                
                try {
                    $this->processMigration($migrationName, $file, $batch);
                    $this->succeeded[] = $migrationName;
                } catch (\Exception $e) {
                    $this->errors[$migrationName] = $this->handleError($e);
                }
            }

            $this->displaySummary();
            
            // If there were any errors, exit with error code
            if (!empty($this->errors)) {
                exit(1);
            }

        } catch (\Exception $e) {
            $this->handleError($e);
            echo "Critical Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    protected function processMigration($migrationName, $file, $batch) {
        require_once $file;
        $className = 'Database\\Versions\\Migration_' . $migrationName;
        
        $this->pdo->beginTransaction();
        try {
            $migration = new $className($this->pdo);
            $migration->up();
            
            $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch, version) VALUES (?, ?, '1.0.0')");
            $stmt->execute([$migrationName, $batch]);
            
            $this->pdo->commit();
            echo "Migrated: $migrationName\n";
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    protected function displaySummary() {
        echo "\nMigration Summary:\n";
        echo str_repeat('-', 50) . "\n";
        
        if (!empty($this->succeeded)) {
            echo "Successful Migrations:\n";
            foreach ($this->succeeded as $migration) {
                echo "✓ $migration\n";
            }
        }
        
        if (!empty($this->errors)) {
            echo "\nFailed Migrations:\n";
            foreach ($this->errors as $migration => $error) {
                echo "✗ $migration: {$error['message']}\n";
            }
            echo "\nDetailed error logs have been written to: " . __DIR__ . "/logs/migration_errors.log\n";
        }

        if ($this->succeeded) {
            $this->displayTableSummary();
        }
    }

    public function checkVersion() {
        $stmt = $this->pdo->query("SELECT MAX(version) FROM migrations");
        $currentVersion = $stmt->fetchColumn();
        
        $requiredVersion = '1.0.0';
        if ($currentVersion && version_compare($currentVersion, $requiredVersion, '<')) {
            throw new \Exception("Database version $currentVersion is below required version $requiredVersion");
        }
    }
}

// Run migrations
try {
    $manager = new MigrationManager($pdo);
    $manager->run();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 