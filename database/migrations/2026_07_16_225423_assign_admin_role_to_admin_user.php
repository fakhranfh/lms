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
        $adminUser = DB::table('users')->where('email', 'admin@example.com')->first();
        $adminRole = DB::table('roles')->where('name', 'Admin')->first();

        if ($adminUser && $adminRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $adminRole->id,
                'model_id' => $adminUser->id,
                'model_type' => 'App\Models\User',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $adminUser = DB::table('users')->where('email', 'admin@example.com')->first();
        if ($adminUser) {
            DB::table('model_has_roles')->where('model_id', $adminUser->id)->delete();
        }
    }
};
