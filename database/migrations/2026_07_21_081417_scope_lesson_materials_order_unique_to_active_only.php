<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Versioning keeps deactivated rows around with the same `order` as the
     * active row that replaced them, so the old blanket unique(lesson_id, order)
     * constraint collides on every version bump. Scope it to active rows only.
     */
    public function up(): void
    {
        Schema::table('lesson_materials', function (Blueprint $table) {
            $table->dropUnique(['lesson_id', 'order']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX lesson_materials_active_lesson_id_order_unique ON lesson_materials (lesson_id, "order") WHERE is_active'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX lesson_materials_active_lesson_id_order_unique');

        Schema::table('lesson_materials', function (Blueprint $table) {
            $table->unique(['lesson_id', 'order']);
        });
    }
};
