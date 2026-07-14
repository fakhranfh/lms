<?php

namespace Database\Seeders;

use App\Models\PaymentGatewayType;
use Illuminate\Database\Seeder;

class PaymentGatewayTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentGatewayType::query()->delete();

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
}
