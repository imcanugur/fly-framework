<?php

declare(strict_types=1);

namespace Fly\Database\Schema;

/**
 * Blueprint for creating database tables.
 */
class Blueprint
{
    protected string $table;
    protected array $columns = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(string $name = 'id'): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'INTEGER PRIMARY KEY AUTOINCREMENT'];
        return $this;
    }

    public function string(string $name): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'VARCHAR(255)'];
        return $this;
    }

    public function text(string $name): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'TEXT'];
        return $this;
    }

    public function integer(string $name): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'INTEGER'];
        return $this;
    }

    public function timestamps(): self
    {
        $this->columns[] = ['name' => 'created_at', 'type' => 'DATETIME'];
        $this->columns[] = ['name' => 'updated_at', 'type' => 'DATETIME'];
        return $this;
    }

    public function build(): string
    {
        $driver = \Fly\Support\Facades\DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $autoIncrement = $driver === 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (\n";
        $cols = [];
        
        foreach ($this->columns as $col) {
            $type = str_replace('AUTOINCREMENT', $autoIncrement, $col['type']);
            $cols[] = "    {$col['name']} {$type}";
        }
        
        $sql .= implode(",\n", $cols) . "\n)";
        return $sql;
    }
}
