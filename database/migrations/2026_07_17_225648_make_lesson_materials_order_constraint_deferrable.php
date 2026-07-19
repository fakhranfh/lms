<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // DEFERRABLE constraints are a PostgreSQL-only feature; other drivers
        // (sqlite, mysql) keep the plain unique constraint from the original migration.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Drop and recreate unique constraint as DEFERRABLE
        // This allows single-query reorder without intermediate constraint violations
        DB::statement(
            'ALTER TABLE lesson_materials DROP CONSTRAINT lesson_materials_lesson_id_order_unique'
        );

        DB::statement(
            'ALTER TABLE lesson_materials ADD CONSTRAINT lesson_materials_lesson_id_order_unique UNIQUE (lesson_id, "order") DEFERRABLE INITIALLY DEFERRED'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Revert to non-deferrable constraint
        DB::statement(
            'ALTER TABLE lesson_materials DROP CONSTRAINT lesson_materials_lesson_id_order_unique'
        );

        DB::statement(
            'ALTER TABLE lesson_materials ADD CONSTRAINT lesson_materials_lesson_id_order_unique UNIQUE (lesson_id, "order")'
        );
    }
};
