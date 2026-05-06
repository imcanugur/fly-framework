<?php

declare(strict_types=1);

namespace Fly\Database\Schema;

/**
 * Foreign ID Column Definition.
 *
 * Combines a column definition with automatic FK constraint registration.
 *
 * Usage:
 *   $table->foreignId('user_id')->constrained();               // → FK to users.id
 *   $table->foreignId('author_id')->constrained('users');       // → FK to users.id
 *   $table->foreignId('user_id')->constrained()->cascadeOnDelete();
 */
class ForeignIdColumnDefinition
{
    protected ColumnDefinition $column;
    protected Blueprint $blueprint;
    protected string $columnName;
    protected ?ForeignKeyDefinition $foreignKey = null;

    public function __construct(ColumnDefinition $column, Blueprint $blueprint, string $columnName)
    {
        $this->column = $column;
        $this->blueprint = $blueprint;
        $this->columnName = $columnName;
    }

    /**
     * Create a FK constraint to the guessed or provided table.
     */
    public function constrained(?string $table = null): self
    {
        // Guess table from column name: user_id → users
        if ($table === null) {
            $base = str_replace('_id', '', $this->columnName);
            $table = $base . 's';
        }

        $this->foreignKey = $this->blueprint->foreign($this->columnName)
            ->references('id')
            ->on($table);

        return $this;
    }

    public function cascadeOnDelete(): self
    {
        $this->foreignKey?->cascadeOnDelete();
        return $this;
    }

    public function nullOnDelete(): self
    {
        $this->column->nullable();
        $this->foreignKey?->nullOnDelete();
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->foreignKey?->onDelete($action);
        return $this;
    }

    public function nullable(): self
    {
        $this->column->nullable();
        return $this;
    }

    public function default(mixed $value): self
    {
        $this->column->default($value);
        return $this;
    }
}
