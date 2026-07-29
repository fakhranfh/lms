<?php

use App\Enums\XenditChannel;
use App\Models\PaymentChannel;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (XenditChannel::cases() as $channel) {
            PaymentChannel::create([
                'code' => $channel->value,
                'label' => $channel->label(),
                'brand_color' => $channel->brandColor(),
            ]);
        }
    }

    public function down(): void
    {
        PaymentChannel::whereIn('code', array_column(XenditChannel::cases(), 'value'))->delete();
    }
};
