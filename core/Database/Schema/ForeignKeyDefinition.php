<?php

declare(strict_types=1);

namespace Fly\Database\Schema;

/**
 * Foreign Key Definition — fluent API for defining FK constraints.
 *
 * Usage:
 *   $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
 */
class ForeignKeyDefinition
{
    protected string $column;
    protected string $references = 'id';
    protected string $on = '';
    protected string $onDelete = '';
    protected string $onUpdate = '';
    protected Blueprint $blueprint;

    public function __construct(Blueprint $blueprint, string $column)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
    }

    public function references(string $column): self
    {
        $this->references = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->on = $table;
        $this->register();
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = strtoupper($action);
        $this->register();
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = strtoupper($action);
        $this->register();
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        return $this->onDelete('CASCADE');
    }

    public function nullOnDelete(): self
    {
        return $this->onDelete('SET NULL');
    }

    protected function register(): void
    {
        if ($this->on) {
            $this->blueprint->addForeignKey([
                'column'     => $this->column,
                'references' => $this->references,
                'on'         => $this->on,
                'onDelete'   => $this->onDelete,
                'onUpdate'   => $this->onUpdate,
            ]);
        }
    }
}
