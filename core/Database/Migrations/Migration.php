<?php

declare(strict_types=1);

namespace Fly\Database\Migrations;

abstract class Migration
{
    /**
     * Run the migrations.
     */
    abstract public function up(): void;

    /**
     * Reverse the migrations.
     */
    abstract public function down(): void;
}
