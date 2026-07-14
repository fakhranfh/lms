<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('subscription_tiers', 'pricing_tiers');
        Schema::table('pricing_tiers', function (Blueprint $table) {
            $table->dropColumn(['features', 'max_users', 'storage_gb']);
        });

        Schema::create('tier_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_tier_id')->constrained('pricing_tiers')->cascadeOnDelete();
            $table->string('feature_key');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['pricing_tier_id', 'feature_key']);
        });

        Schema::create('tier_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_tier_id')->constrained('pricing_tiers')->cascadeOnDelete();
            $table->string('limit_key');
            $table->integer('limit_value')->nullable();
            $table->timestamps();

            $table->unique(['pricing_tier_id', 'limit_key']);
        });

        Schema::rename('subscriptions', 'school_tiers');
        Schema::table('school_tiers', function (Blueprint $table) {
            $table->dropForeign('subscriptions_tier_id_foreign');
            $table->foreign('tier_id')->references('id')->on('pricing_tiers');
        });

        Schema::create('tier_changes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_tier_id')->constrained('school_tiers')->cascadeOnDelete();
            $table->foreignId('from_tier_id')->nullable()->constrained('pricing_tiers');
            $table->foreignId('to_tier_id')->constrained('pricing_tiers');
            $table->string('change_type');
            $table->text('reason')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tier_changes');
        Schema::dropIfExists('tier_limits');
        Schema::dropIfExists('tier_features');

        Schema::table('school_tiers', function (Blueprint $table) {
            $table->dropForeign('school_tiers_tier_id_foreign');
            $table->foreign('tier_id')->references('id')->on('subscription_tiers');
        });
        Schema::rename('school_tiers', 'subscriptions');

        Schema::table('pricing_tiers', function (Blueprint $table) {
            $table->json('features');
            $table->integer('max_users')->nullable();
            $table->integer('storage_gb')->nullable();
        });
        Schema::rename('pricing_tiers', 'subscription_tiers');
    }
};
