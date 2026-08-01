<?php

use App\Models\School;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        School::whereNull('slug')->orderBy('created_at')->each(function (School $school): void {
            $base = Str::slug($school->name);
            $slug = $base;
            $suffix = 2;

            while (School::where('slug', $slug)->where('id', '!=', $school->id)->exists()) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            $school->forceFill(['slug' => $slug])->saveQuietly();
        });

        DB::statement('ALTER TABLE schools ALTER COLUMN slug SET NOT NULL');

        Schema::table('schools', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
