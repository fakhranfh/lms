<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The reorder repository shifts rows into a temporary negative range to
     * avoid mid-statement unique collisions, which an unsigned column
     * rejects on MySQL/MariaDB (pgsql/sqlite don't enforce "unsigned").
     */
    public function up(): void
    {
        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE lesson_materials MODIFY `order` INT NOT NULL'),
            'pgsql' => DB::statement('ALTER TABLE lesson_materials ALTER COLUMN "order" TYPE INTEGER'),
            default => null,
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE lesson_materials MODIFY `order` INT UNSIGNED NOT NULL'),
            'pgsql' => DB::statement('ALTER TABLE lesson_materials ALTER COLUMN "order" TYPE INTEGER'),
            default => null,
        };
    }
};
