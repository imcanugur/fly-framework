<?php

declare(strict_types=1);

namespace Fly\Database\Schema;

/**
 * Schema Blueprint — Defines table structure fluently.
 *
 * Supports 15+ column types, nullable, default, unique, unsigned,
 * indexes, foreign keys, softDeletes, and driver-aware compilation.
 */
class Blueprint
{
    protected string $table;

    /** @var array<int, ColumnDefinition> */
    protected array $columns = [];

    /** @var array<int, array{columns: array, name: string}> */
    protected array $indexes = [];

    /** @var array<int, array{column: string, on: string, references: string, onDelete: string, onUpdate: string}> */
    protected array $foreignKeys = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    // ----------------------------------------------------------------
    // Column Type Methods
    // ----------------------------------------------------------------

    protected function addColumn(string $type, string $name): ColumnDefinition
    {
        $column = new ColumnDefinition($name, $type);
        $this->columns[] = $column;
        return $column;
    }

    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->addColumn('BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT', $name);
    }

    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn('BIGINT', $name);
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn('INT', $name);
    }

    public function tinyInteger(string $name): ColumnDefinition
    {
        return $this->addColumn('TINYINT', $name);
    }

    public function smallInteger(string $name): ColumnDefinition
    {
        return $this->addColumn('SMALLINT', $name);
    }

    public function mediumInteger(string $name): ColumnDefinition
    {
        return $this->addColumn('MEDIUMINT', $name);
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn("DECIMAL({$precision},{$scale})", $name);
    }

    public function float(string $name): ColumnDefinition
    {
        return $this->addColumn('FLOAT', $name);
    }

    public function double(string $name): ColumnDefinition
    {
        return $this->addColumn('DOUBLE', $name);
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn("VARCHAR({$length})", $name);
    }

    public function char(string $name, int $length = 1): ColumnDefinition
    {
        return $this->addColumn("CHAR({$length})", $name);
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn('TEXT', $name);
    }

    public function mediumText(string $name): ColumnDefinition
    {
        return $this->addColumn('MEDIUMTEXT', $name);
    }

    public function longText(string $name): ColumnDefinition
    {
        return $this->addColumn('LONGTEXT', $name);
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn('TINYINT(1)', $name);
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn('DATE', $name);
    }

    public function dateTime(string $name): ColumnDefinition
    {
        return $this->addColumn('DATETIME', $name);
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn('TIMESTAMP', $name);
    }

    public function time(string $name): ColumnDefinition
    {
        return $this->addColumn('TIME', $name);
    }

    public function year(string $name): ColumnDefinition
    {
        return $this->addColumn('YEAR', $name);
    }

    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn('JSON', $name);
    }

    public function binary(string $name): ColumnDefinition
    {
        return $this->addColumn('BLOB', $name);
    }

    public function enum(string $name, array $allowed): ColumnDefinition
    {
        $values = implode("','", $allowed);
        return $this->addColumn("ENUM('{$values}')", $name);
    }

    public function uuid(string $name = 'uuid'): ColumnDefinition
    {
        return $this->addColumn('CHAR(36)', $name);
    }

    public function ipAddress(string $name = 'ip_address'): ColumnDefinition
    {
        return $this->addColumn('VARCHAR(45)', $name);
    }

    public function macAddress(string $name = 'mac_address'): ColumnDefinition
    {
        return $this->addColumn('VARCHAR(17)', $name);
    }

    /**
     * Add a foreign ID column (convention: user_id → BIGINT UNSIGNED).
     */
    public function foreignId(string $name): ForeignIdColumnDefinition
    {
        $col = $this->addColumn('BIGINT UNSIGNED', $name);
        return new ForeignIdColumnDefinition($col, $this, $name);
    }

    /**
     * Convenience for created_at and updated_at columns.
     */
    public function timestamps(): void
    {
        $this->addColumn('DATETIME', 'created_at')->nullable();
        $this->addColumn('DATETIME', 'updated_at')->nullable();
    }

    /**
     * Add a deleted_at column for soft deletes.
     */
    public function softDeletes(string $column = 'deleted_at'): ColumnDefinition
    {
        return $this->addColumn('DATETIME', $column)->nullable();
    }

    /**
     * Add remember_token column.
     */
    public function rememberToken(): ColumnDefinition
    {
        return $this->string('remember_token', 100)->nullable();
    }

    // ----------------------------------------------------------------
    // Indexes
    // ----------------------------------------------------------------

    /**
     * Add a composite index.
     */
    public function index(string|array $columns, ?string $name = null): self
    {
        $columns = (array) $columns;
        $name = $name ?: $this->table . '_' . implode('_', $columns) . '_index';
        $this->indexes[] = ['columns' => $columns, 'name' => $name, 'type' => 'INDEX'];
        return $this;
    }

    /**
     * Add a composite unique index.
     */
    public function uniqueIndex(string|array $columns, ?string $name = null): self
    {
        $columns = (array) $columns;
        $name = $name ?: $this->table . '_' . implode('_', $columns) . '_unique';
        $this->indexes[] = ['columns' => $columns, 'name' => $name, 'type' => 'UNIQUE INDEX'];
        return $this;
    }

    // ----------------------------------------------------------------
    // Foreign Keys
    // ----------------------------------------------------------------

    /**
     * Add a foreign key constraint.
     */
    public function foreign(string $column): ForeignKeyDefinition
    {
        return new ForeignKeyDefinition($this, $column);
    }

    /**
     * Register a foreign key constraint (called by ForeignKeyDefinition).
     */
    public function addForeignKey(array $fk): void
    {
        $this->foreignKeys[] = $fk;
    }

    // ----------------------------------------------------------------
    // SQL Compilation
    // ----------------------------------------------------------------

    public function build(): string
    {
        $driver = \Fly\Support\Facades\DB::connection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (\n";
        $defs = [];

        foreach ($this->columns as $col) {
            $type = $col->type;

            // Driver-specific auto increment syntax
            if ($isSqlite) {
                $type = str_replace('BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $type);
                $type = str_replace('AUTO_INCREMENT', 'AUTOINCREMENT', $type);
            }

            $line = "    {$col->name} {$type}";

            if ($col->unsigned && !str_contains($type, 'UNSIGNED')) {
                $line .= ' UNSIGNED';
            }

            if (!$col->nullable && !str_contains($type, 'PRIMARY KEY')) {
                $line .= ' NOT NULL';
            }

            if ($col->hasDefault) {
                $line .= ' DEFAULT ' . $this->compileDefault($col->default);
            }

            if ($col->unique && !str_contains($type, 'PRIMARY KEY')) {
                $line .= ' UNIQUE';
            }

            $defs[] = $line;
        }

        // Indexes
        foreach ($this->indexes as $idx) {
            $cols = implode(', ', $idx['columns']);
            $defs[] = "    {$idx['type']} {$idx['name']} ({$cols})";
        }

        // Foreign keys (skip for SQLite — limited support)
        if (!$isSqlite) {
            foreach ($this->foreignKeys as $fk) {
                $name = "{$this->table}_{$fk['column']}_foreign";
                $line = "    CONSTRAINT {$name} FOREIGN KEY ({$fk['column']}) REFERENCES {$fk['on']} ({$fk['references']})";
                if ($fk['onDelete']) {
                    $line .= " ON DELETE {$fk['onDelete']}";
                }
                if ($fk['onUpdate']) {
                    $line .= " ON UPDATE {$fk['onUpdate']}";
                }
                $defs[] = $line;
            }
        }

        $sql .= implode(",\n", $defs) . "\n)";

        // MySQL engine
        if (!$isSqlite) {
            $sql .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        }

        return $sql;
    }

    protected function compileDefault(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_null($value)) {
            return 'NULL';
        }
        return "'{$value}'";
    }
}
