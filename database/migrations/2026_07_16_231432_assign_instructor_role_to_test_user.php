<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $testUser = DB::table('users')->where('email', 'test@school.local')->first();
        $instructorRole = DB::table('roles')->where('name', 'Instructor')->first();

        if ($testUser && $instructorRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $instructorRole->id,
                'model_id' => $testUser->id,
                'model_type' => 'App\Models\User',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $testUser = DB::table('users')->where('email', 'test@school.local')->first();
        if ($testUser) {
            DB::table('model_has_roles')->where('model_id', $testUser->id)->delete();
        }
    }
};
