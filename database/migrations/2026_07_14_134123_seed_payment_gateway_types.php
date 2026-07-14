<?php

use App\Models\PaymentGatewayType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PaymentGatewayType::create([
            'name' => 'midtrans',
            'label' => 'Midtrans',
            'description' => 'Midtrans payment gateway for Indonesian payments',
            'is_active' => true,
        ]);

        PaymentGatewayType::create([
            'name' => 'xendit',
            'label' => 'Xendit',
            'description' => 'Xendit payment gateway for multiple payment methods',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        PaymentGatewayType::whereIn('name', ['midtrans', 'xendit'])->delete();
    }
};
