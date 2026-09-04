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
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0)->after('status');
        });

        DB::table('assessments')
            ->select('id', 'course_id', 'type')
            ->orderBy('course_id')
            ->orderBy('type')
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($assessment) => $assessment->course_id.'|'.$assessment->type)
            ->each(function ($assessments) {
                foreach ($assessments->values() as $index => $assessment) {
                    DB::table('assessments')->where('id', $assessment->id)->update(['order' => $index + 1]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
