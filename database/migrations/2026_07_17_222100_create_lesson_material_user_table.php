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
        Schema::create('lesson_material_user', function (Blueprint $table) {
            $table->uuid('lesson_material_id');
            $table->uuid('user_id');
            $table->timestamp('accessed_at')->nullable(); // when user marked as read
            $table->timestamps();

            $table->primary(['lesson_material_id', 'user_id']);
            $table->foreign('lesson_material_id')->references('id')->on('lesson_materials')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_material_user');
    }
};
