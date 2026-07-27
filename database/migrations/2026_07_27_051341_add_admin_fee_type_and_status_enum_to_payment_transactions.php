<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->nullable()->after('amount');
            $table->decimal('vat_rate', 8, 4)->nullable()->after('subtotal');
            $table->decimal('vat_amount', 15, 2)->nullable()->after('vat_rate');
            $table->decimal('admin_fee_rate', 8, 4)->nullable()->after('vat_amount');
            $table->string('admin_fee_type')->nullable()->after('admin_fee_rate');
            $table->decimal('admin_fee_amount', 15, 2)->nullable()->after('admin_fee_type');
        });

        DB::table('payment_transactions')->whereNotNull('metadata')->orderBy('id')->get()->each(function (object $row): void {
            $metadata = json_decode($row->metadata, true) ?? [];

            if (! array_key_exists('subtotal', $metadata)) {
                return;
            }

            DB::table('payment_transactions')->where('id', $row->id)->update([
                'subtotal' => $metadata['subtotal'] ?? null,
                'vat_rate' => $metadata['vat_rate'] ?? null,
                'vat_amount' => $metadata['vat_amount'] ?? null,
                'admin_fee_rate' => $metadata['admin_fee_rate'] ?? null,
                'admin_fee_type' => 'percentage',
                'admin_fee_amount' => $metadata['admin_fee_amount'] ?? null,
            ]);
        });

        DB::table('payment_transactions')->where('status', '<>', '')
            ->whereNotIn('status', ['pending', 'completed', 'failed', 'refunded'])
            ->update(['status' => 'pending']);

        DB::statement(
            "ALTER TABLE payment_transactions ADD CONSTRAINT payment_transactions_status_check CHECK (status IN ('pending', 'completed', 'failed', 'refunded'))"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE payment_transactions DROP CONSTRAINT payment_transactions_status_check');

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'vat_rate',
                'vat_amount',
                'admin_fee_rate',
                'admin_fee_type',
                'admin_fee_amount',
            ]);
        });
    }
};
