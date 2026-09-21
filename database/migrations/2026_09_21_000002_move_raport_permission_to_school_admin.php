<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Role::where('name', 'Teacher')->get()->each(function (Role $role): void {
            $role->revokePermissionTo('raport.view');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where('name', 'Teacher')->get()->each(function (Role $role): void {
            $role->givePermissionTo('raport.view');
        });
    }
};
