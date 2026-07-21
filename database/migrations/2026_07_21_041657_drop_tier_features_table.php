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
        Schema::dropIfExists('tier_features');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('tier_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_tier_id')->constrained('pricing_tiers')->onDelete('cascade');
            $table->string('feature_key');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['pricing_tier_id', 'feature_key']);
        });
    }
};
