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
        Schema::create('storage_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('school_id')->nullable()->index();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->unsignedBigInteger('total_used_bytes');
            $table->unsignedBigInteger('quota_bytes');
            $table->decimal('usage_percent', 5, 2);
            $table->unsignedTinyInteger('last_alert_threshold')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_usage_logs');
    }
};
