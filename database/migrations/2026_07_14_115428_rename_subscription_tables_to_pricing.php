<?php

use App\Enums\BillingPeriod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('school_tiers', function (Blueprint $table) {
                $table->dropForeign('subscriptions_tier_id_foreign');
                $table->foreign('tier_id')->references('id')->on('pricing_tiers');
            });
        }

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

        $this->seedPricingTiers();
    }

    private function seedPricingTiers(): void
    {
        DB::table('pricing_tiers')->delete();

        $basicTier = DB::table('pricing_tiers')->insertGetId([
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => 'Free tier for getting started',
            'price' => 0,
            'currency' => 'IDR',
            'billing_period' => BillingPeriod::Monthly->value,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tier_limits')->insert([
            'pricing_tier_id' => $basicTier, 'limit_key' => 'material_storage_gb', 'limit_value' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $plusTier = DB::table('pricing_tiers')->insertGetId([
            'name' => 'Plus',
            'slug' => 'plus',
            'description' => 'Enhanced learning tools for growing schools',
            'price' => 299000,
            'currency' => 'IDR',
            'billing_period' => BillingPeriod::Monthly->value,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tier_features')->insert([
            ['pricing_tier_id' => $plusTier, 'feature_key' => 'analytics', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_tier_id' => $plusTier, 'feature_key' => 'live_session', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('tier_limits')->insert([
            'pricing_tier_id' => $plusTier, 'limit_key' => 'material_storage_gb', 'limit_value' => 10, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $proTier = DB::table('pricing_tiers')->insertGetId([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Professional features for scaling institutions',
            'price' => 799000,
            'currency' => 'IDR',
            'billing_period' => BillingPeriod::Monthly->value,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tier_features')->insert([
            ['pricing_tier_id' => $proTier, 'feature_key' => 'analytics', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_tier_id' => $proTier, 'feature_key' => 'live_session', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_tier_id' => $proTier, 'feature_key' => 'live_session_recording', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_tier_id' => $proTier, 'feature_key' => 'api_access', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('tier_limits')->insert([
            'pricing_tier_id' => $proTier, 'limit_key' => 'material_storage_gb', 'limit_value' => 50, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $maxTier = DB::table('pricing_tiers')->insertGetId([
            'name' => 'Max',
            'slug' => 'max',
            'description' => 'Enterprise features with unlimited capabilities',
            'price' => 1999000,
            'currency' => 'IDR',
            'billing_period' => BillingPeriod::Monthly->value,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tier_features')->insert([
            ['pricing_tier_id' => $maxTier, 'feature_key' => 'analytics', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_tier_id' => $maxTier, 'feature_key' => 'live_session', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_tier_id' => $maxTier, 'feature_key' => 'live_session_recording', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_tier_id' => $maxTier, 'feature_key' => 'api_access', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_tier_id' => $maxTier, 'feature_key' => 'custom_branding', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_tier_id' => $maxTier, 'feature_key' => 'sso', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_tier_id' => $maxTier, 'feature_key' => 'priority_support', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('tier_limits')->insert([
            'pricing_tier_id' => $maxTier, 'limit_key' => 'material_storage_gb', 'limit_value' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('tier_limits')->delete();
        DB::table('tier_features')->delete();
        DB::table('pricing_tiers')->delete();

        Schema::dropIfExists('tier_changes');
        Schema::dropIfExists('tier_limits');
        Schema::dropIfExists('tier_features');

        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('school_tiers', function (Blueprint $table) {
                $table->dropForeign('school_tiers_tier_id_foreign');
                $table->foreign('tier_id')->references('id')->on('subscription_tiers');
            });
        }
        Schema::rename('school_tiers', 'subscriptions');

        Schema::table('pricing_tiers', function (Blueprint $table) {
            $table->json('features');
            $table->integer('max_users')->nullable();
            $table->integer('storage_gb')->nullable();
        });
        Schema::rename('pricing_tiers', 'subscription_tiers');
    }
};
