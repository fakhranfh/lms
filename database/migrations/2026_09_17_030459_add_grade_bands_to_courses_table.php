<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedTinyInteger('grade_band_a_min')->default(90);
            $table->unsignedTinyInteger('grade_band_b_min')->default(80);
            $table->unsignedTinyInteger('grade_band_c_min')->default(70);
            $table->unsignedTinyInteger('grade_band_d_min')->default(60);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['grade_band_a_min', 'grade_band_b_min', 'grade_band_c_min', 'grade_band_d_min']);
        });
    }
};
