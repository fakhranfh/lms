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
        Schema::table('payment_gateways', function (Blueprint $table) {
            // Table was renamed from school_payment_gateways; mysql/pgsql keep the
            // original constraint name, so it must be dropped explicitly by name.
            if (DB::getDriverName() === 'sqlite') {
                $table->dropForeign(['school_id']);
            } else {
                $table->dropForeign('school_payment_gateways_school_id_foreign');
            }

            $table->dropColumn('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
        });
    }
};
