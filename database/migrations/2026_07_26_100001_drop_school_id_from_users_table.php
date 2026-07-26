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
        DB::table('users')
            ->whereNotNull('school_id')
            ->orderBy('id')
            ->each(function (object $user): void {
                DB::table('school_user')->insertOrIgnore([
                    'user_id' => $user->id,
                    'school_id' => $user->school_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('school_id')->nullable()->after('id')->constrained('schools')->cascadeOnDelete();
        });

        DB::table('school_user')->orderBy('id')->each(function (object $row): void {
            DB::table('users')->where('id', $row->user_id)->update(['school_id' => $row->school_id]);
        });
    }
};
