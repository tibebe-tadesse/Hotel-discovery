<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/Migration.php';

use Database\Migration;

// Function to parse SQL file and extract CREATE TABLE statements
function parseCreateTableStatements($sqlFile) {
    $content = file_get_contents($sqlFile);
    
    // Remove comments
    $content = preg_replace('/--.*$/m', '', $content);
    
    // Split into statements
    $statements = array_filter(
        array_map('trim', 
            explode(';', $content)
        )
    );
    
    // Extract CREATE TABLE statements
    $createStatements = [];
    foreach ($statements as $statement) {
        if (preg_match('/CREATE\s+TABLE\s+/i', $statement)) {
            // Get table name
            preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches);
            if (isset($matches[1])) {
                $tableName = $matches[1];
                $createStatements[$tableName] = $statement;
            }
        }
    }
    
    return $createStatements;
}

// Add function to handle SQL indexes
function parseIndexStatements($sqlFile) {
    $content = file_get_contents($sqlFile);
    $indexes = [];
    
    if (preg_match_all('/CREATE\s+INDEX\s+(\w+)\s+ON\s+(\w+)\s*\((.*?)\)/is', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $indexes[] = [
                'name' => $match[1],
                'table' => $match[2],
                'columns' => $match[3]
            ];
        }
    }
    
    return $indexes;
}

// Function to generate migration file content
function generateMigrationContent($createStatements, $indexes) {
    $tables = array_keys($createStatements);
    $timestamp = date('YmdHis');
    
    $content = "<?php\nnamespace Database\\Versions;\n\n";
    $content .= "use Database\\Migration;\n\n";
    $content .= "class Migration_{$timestamp}_initial_schema extends Migration {\n";
    $content .= "    public function up() {\n";
    
    // Add CREATE TABLE statements
    foreach ($createStatements as $tableName => $statement) {
        $content .= "        // Create $tableName table\n";
        $content .= "        \$this->pdo->exec(\"$statement\");\n\n";
    }
    
    // Add indexes after table creation
    foreach ($indexes as $index) {
        $content .= sprintf(
            "        \$this->pdo->exec(\"CREATE INDEX %s ON %s(%s)\");\n",
            $index['name'],
            $index['table'],
            $index['columns']
        );
    }
    
    $content .= "    }\n\n";
    $content .= "    public function down() {\n";
    $content .= "        // Drop tables in reverse order to handle foreign key constraints\n";
    
    // Add DROP TABLE statements in reverse order
    $reversedTables = array_reverse($tables);
    foreach ($reversedTables as $table) {
        $content .= "        \$this->pdo->exec(\"DROP TABLE IF EXISTS $table\");\n";
    }
    
    $content .= "    }\n";
    $content .= "}\n";
    
    return $content;
}

// Main execution
try {
    // Check if database.sql exists
    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("database.sql file not found!");
    }

    // Create versions directory if it doesn't exist
    $versionsDir = __DIR__ . '/versions';
    if (!is_dir($versionsDir)) {
        mkdir($versionsDir, 0755, true);
    }

    // Parse CREATE TABLE statements
    $createStatements = parseCreateTableStatements($sqlFile);
    
    if (empty($createStatements)) {
        throw new Exception("No CREATE TABLE statements found in database.sql");
    }

    // Parse indexes
    $indexes = parseIndexStatements($sqlFile);

    // Generate migration content
    $migrationContent = generateMigrationContent($createStatements, $indexes);
    
    // Save migration file
    $timestamp = date('YmdHis');
    $migrationFile = $versionsDir . "/{$timestamp}_initial_schema.php";
    
    if (file_put_contents($migrationFile, $migrationContent)) {
        echo "Migration file generated successfully: " . basename($migrationFile) . "\n";
    } else {
        throw new Exception("Failed to write migration file!");
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 