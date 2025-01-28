<?php
namespace Database;

class SchemaComparator {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getTableSchema($tableName) {
        // Get table columns
        $columns = $this->pdo->query("SHOW COLUMNS FROM `$tableName`")->fetchAll(\PDO::FETCH_ASSOC);
        $schema = [];
        foreach ($columns as $column) {
            $schema[$column['Field']] = [
                'type' => $column['Type'],
                'null' => $column['Null'],
                'key' => $column['Key'],
                'default' => $column['Default'],
                'extra' => $column['Extra']
            ];
        }
        return $schema;
    }

    public function compareSchemas($oldSchema, $newSchema) {
        $differences = [
            'added' => [],
            'modified' => [],
            'removed' => []
        ];

        // Find added and modified columns
        foreach ($newSchema as $column => $definition) {
            if (!isset($oldSchema[$column])) {
                $differences['added'][$column] = $definition;
            } elseif ($this->isDifferent($oldSchema[$column], $definition)) {
                $differences['modified'][$column] = [
                    'from' => $oldSchema[$column],
                    'to' => $definition
                ];
            }
        }

        // Find removed columns
        foreach ($oldSchema as $column => $definition) {
            if (!isset($newSchema[$column])) {
                $differences['removed'][$column] = $definition;
            }
        }

        return $differences;
    }

    private function isDifferent($old, $new) {
        return $old['type'] !== $new['type'] ||
               $old['null'] !== $new['null'] ||
               $old['default'] !== $new['default'] ||
               $old['extra'] !== $new['extra'];
    }

    public function generateAlterStatement($tableName, $differences) {
        $alterParts = [];

        // Handle added columns
        foreach ($differences['added'] as $column => $def) {
            $alterParts[] = "ADD COLUMN `$column` {$def['type']}" .
                           ($def['null'] === 'NO' ? ' NOT NULL' : '') .
                           (isset($def['default']) ? " DEFAULT '{$def['default']}'" : '') .
                           ($def['extra'] ? " {$def['extra']}" : '');
        }

        // Handle modified columns
        foreach ($differences['modified'] as $column => $def) {
            $alterParts[] = "MODIFY COLUMN `$column` {$def['to']['type']}" .
                           ($def['to']['null'] === 'NO' ? ' NOT NULL' : '') .
                           (isset($def['to']['default']) ? " DEFAULT '{$def['to']['default']}'" : '') .
                           ($def['to']['extra'] ? " {$def['to']['extra']}" : '');
        }

        // Handle removed columns
        foreach ($differences['removed'] as $column => $def) {
            $alterParts[] = "DROP COLUMN `$column`";
        }

        if (empty($alterParts)) {
            return null;
        }

        return "ALTER TABLE `$tableName` " . implode(", ", $alterParts);
    }
} 