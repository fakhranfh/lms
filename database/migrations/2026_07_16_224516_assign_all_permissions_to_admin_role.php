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
        $adminRole = DB::table('roles')->where('name', 'Admin')->first();
        $permissions = DB::table('permissions')->get();

        foreach ($permissions as $permission) {
            DB::table('role_has_permissions')->insert([
                'role_id' => $adminRole->id,
                'permission_id' => $permission->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $adminRole = DB::table('roles')->where('name', 'Admin')->first();
        DB::table('role_has_permissions')->where('role_id', $adminRole->id)->delete();
    }
};
