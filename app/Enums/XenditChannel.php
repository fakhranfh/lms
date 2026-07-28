<?php

namespace App\Enums;

enum XenditChannel: string
{
    case Qris = 'QRIS';
    case Ovo = 'OVO';
    case Dana = 'DANA';
    case LinkAja = 'LINKAJA';
    case ShopeePay = 'SHOPEEPAY';
    case Bca = 'BCA';
    case Bni = 'BNI';
    case Bri = 'BRI';
    case Mandiri = 'MANDIRI';
    case Permata = 'PERMATA';
    case Alfamart = 'ALFAMART';
    case Indomaret = 'INDOMARET';

    public function label(): string
    {
        return match ($this) {
            self::Qris => 'QRIS',
            self::Ovo => 'OVO',
            self::Dana => 'DANA',
            self::LinkAja => 'LinkAja',
            self::ShopeePay => 'ShopeePay',
            self::Bca => 'BCA Virtual Account',
            self::Bni => 'BNI Virtual Account',
            self::Bri => 'BRI Virtual Account',
            self::Mandiri => 'Mandiri Virtual Account',
            self::Permata => 'Permata Virtual Account',
            self::Alfamart => 'Alfamart',
            self::Indomaret => 'Indomaret',
        };
    }

    /**
     * The payment request "type" this channel should be created as,
     * per Xendit's Payment Requests API (docs/xendit/PAYMENT_REQUEST.md).
     */
    public function requestType(): string
    {
        return match ($this) {
            self::Qris, self::Ovo, self::Dana, self::LinkAja, self::ShopeePay => 'PAY',
            self::Bca, self::Bni, self::Bri, self::Mandiri, self::Permata,
            self::Alfamart, self::Indomaret => 'REUSABLE_PAYMENT_CODE',
        };
    }

    public function isRedirectBased(): bool
    {
        return match ($this) {
            self::Ovo, self::Dana, self::LinkAja, self::ShopeePay => true,
            default => false,
        };
    }

    /**
     * Which result page layout this channel's payment details should use.
     */
    public function viewType(): string
    {
        return match ($this) {
            self::Qris => 'qris',
            self::Ovo, self::Dana, self::LinkAja, self::ShopeePay => 'ewallet',
            self::Bca, self::Bni, self::Bri, self::Mandiri, self::Permata => 'virtual_account',
            self::Alfamart, self::Indomaret => 'retail',
        };
    }

    /**
     * Build the channel_properties payload for this channel.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function buildChannelProperties(array $data): array
    {
        if ($this->isRedirectBased()) {
            return [
                'success_return_url' => $data['success_return_url'] ?? config('app.url'),
                'failure_return_url' => $data['failure_return_url'] ?? config('app.url'),
            ];
        }

        if ($this->requestType() === 'REUSABLE_PAYMENT_CODE') {
            return [
                'display_name' => $data['display_name'] ?? config('app.name'),
            ];
        }

        return [];
    }
}
