<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->unsignedInteger('order')->nullable()->after('delivery_mode');
        });

        DB::table('course_sessions')
            ->select('id', 'course_id')
            ->orderBy('date_start')
            ->get()
            ->groupBy('course_id')
            ->each(function ($sessions) {
                foreach ($sessions->values() as $index => $session) {
                    DB::table('course_sessions')
                        ->where('id', $session->id)
                        ->update(['order' => $index + 1]);
                }
            });

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->unsignedInteger('order')->nullable(false)->change();
            $table->unique(['course_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'order']);
            $table->dropColumn('order');
        });
    }
};
