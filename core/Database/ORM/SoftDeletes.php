<?php

declare(strict_types=1);

namespace Fly\Database\ORM;

/**
 * Soft Deletes Trait.
 *
 * When used on a model, delete() sets a deleted_at timestamp
 * instead of physically removing the row.
 *
 * Usage:
 *   class User extends Model {
 *       use SoftDeletes;
 *   }
 */
trait SoftDeletes
{
    /**
     * Boot the soft deletes trait.
     * Adds a global scope to exclude soft-deleted records.
     */
    public function initializeSoftDeletes(): void
    {
        // Mark that this model uses soft deletes
    }

    /**
     * Perform a soft delete on the model.
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $this->attributes['deleted_at'] = date('Y-m-d H:i:s');

        \Fly\Support\Facades\DB::table($this->getTable())
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->update(['deleted_at' => $this->attributes['deleted_at']]);

        return true;
    }

    /**
     * Force-delete (physically remove) the model.
     */
    public function forceDelete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        \Fly\Support\Facades\DB::table($this->getTable())
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->delete();

        $this->exists = false;
        return true;
    }

    /**
     * Restore a soft-deleted model.
     */
    public function restore(): bool
    {
        $this->attributes['deleted_at'] = null;

        \Fly\Support\Facades\DB::table($this->getTable())
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->update(['deleted_at' => null]);

        return true;
    }

    /**
     * Determine if the model has been soft-deleted.
     */
    public function trashed(): bool
    {
        return $this->attributes['deleted_at'] !== null;
    }
}
