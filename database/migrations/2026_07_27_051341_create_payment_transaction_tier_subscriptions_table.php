<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_transaction_tier_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_transaction_id')->constrained('payment_transactions')->cascadeOnDelete();
            $table->uuid('school_id')->nullable();
            $table->uuid('subscription_id')->nullable();
            $table->string('tier_name')->nullable();
            $table->string('billing_period')->nullable();
            $table->unsignedBigInteger('from_tier_id')->nullable();
            $table->string('change_type')->nullable();
            $table->decimal('proration_amount', 15, 2)->nullable();
            $table->json('registration_data')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('subscription_id')->references('id')->on('school_tiers')->nullOnDelete();
            $table->foreign('from_tier_id')->references('id')->on('pricing_tiers')->nullOnDelete();
        });

        $promotedKeys = ['subtotal', 'vat_rate', 'vat_amount', 'admin_fee_rate', 'admin_fee_amount'];

        DB::table('payment_transactions')->orderBy('id')->get()->each(function (object $row) use ($promotedKeys): void {
            $metadata = json_decode($row->metadata ?? '[]', true) ?? [];
            $metadata = array_diff_key($metadata, array_flip($promotedKeys));

            if (! $row->school_id && ! $row->subscription_id && $metadata === [] && ! $row->registration_data) {
                return;
            }

            DB::table('payment_transaction_tier_subscriptions')->insert([
                'id' => (string) Str::uuid(),
                'payment_transaction_id' => $row->id,
                'school_id' => $row->school_id,
                'subscription_id' => $row->subscription_id,
                'tier_name' => $metadata['tier_name'] ?? null,
                'billing_period' => $metadata['billing_period'] ?? null,
                'from_tier_id' => $metadata['from_tier_id'] ?? null,
                'change_type' => $metadata['change_type'] ?? null,
                'proration_amount' => $metadata['proration_amount'] ?? null,
                'registration_data' => $row->registration_data,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transaction_tier_subscriptions');
    }
};
