<?php
namespace Database\Exceptions;

class MigrationException extends \Exception {
    protected $migration;
    protected $sql;
    protected $context;

    public function __construct($message, $migration = null, $sql = null, $context = [], \Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->migration = $migration;
        $this->sql = $sql;
        $this->context = $context;
    }

    public function getDetails() {
        return [
            'migration' => $this->migration,
            'sql' => $this->sql,
            'context' => $this->context,
            'message' => $this->getMessage(),
            'trace' => $this->getTraceAsString()
        ];
    }
} 