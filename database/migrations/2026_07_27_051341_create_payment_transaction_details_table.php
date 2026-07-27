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
        Schema::create('payment_transaction_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_transaction_id')->constrained('payment_transactions')->cascadeOnDelete();
            $table->uuid('school_id')->nullable();
            $table->uuid('subscription_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('subscription_id')->references('id')->on('school_tiers')->nullOnDelete();
        });

        $promotedKeys = ['subtotal', 'vat_rate', 'vat_amount', 'admin_fee_rate', 'admin_fee_amount'];

        DB::table('payment_transactions')->orderBy('id')->get()->each(function (object $row) use ($promotedKeys): void {
            $metadata = json_decode($row->metadata ?? '[]', true) ?? [];
            $metadata = array_diff_key($metadata, array_flip($promotedKeys));

            if (! $row->school_id && ! $row->subscription_id && $metadata === []) {
                return;
            }

            DB::table('payment_transaction_details')->insert([
                'id' => (string) Str::uuid(),
                'payment_transaction_id' => $row->id,
                'school_id' => $row->school_id,
                'subscription_id' => $row->subscription_id,
                'metadata' => $metadata === [] ? null : json_encode($metadata),
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
        Schema::dropIfExists('payment_transaction_details');
    }
};
