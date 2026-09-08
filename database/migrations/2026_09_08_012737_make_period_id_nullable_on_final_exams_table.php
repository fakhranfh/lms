<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Period field was removed from the final exam form; period_id is
     * kept for existing records but is no longer required going forward.
     */
    public function up(): void
    {
        Schema::table('final_exams', function (Blueprint $table) {
            $table->dropForeign(['period_id']);
        });

        Schema::table('final_exams', function (Blueprint $table) {
            $table->uuid('period_id')->nullable()->change();
        });

        Schema::table('final_exams', function (Blueprint $table) {
            $table->foreign('period_id')->references('id')->on('periods')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('final_exams', function (Blueprint $table) {
            $table->dropForeign(['period_id']);
        });

        Schema::table('final_exams', function (Blueprint $table) {
            $table->uuid('period_id')->nullable(false)->change();
        });

        Schema::table('final_exams', function (Blueprint $table) {
            $table->foreign('period_id')->references('id')->on('periods')->cascadeOnDelete();
        });
    }
};
