<?php

namespace App\Enums;

use App\Services\R2StorageService;

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
     * Brand color used for this channel's generated logo badge.
     */
    public function brandColor(): string
    {
        return match ($this) {
            self::Qris => '#DD2624',
            self::Ovo => '#4C3494',
            self::Dana => '#118EEA',
            self::LinkAja => '#E9312A',
            self::ShopeePay => '#EE4D2D',
            self::Bca => '#0058A3',
            self::Bni => '#F37021',
            self::Bri => '#00529C',
            self::Mandiri => '#003D79',
            self::Permata => '#00695C',
            self::Alfamart => '#E4032E',
            self::Indomaret => '#123B7D',
        };
    }

    /**
     * Public R2 URL for this channel's logo, uploaded by the
     * `channels:sync-logos` artisan command.
     */
    public function logoUrl(): string
    {
        return app(R2StorageService::class)->getPublicUrl("channel-logos/{$this->value}");
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
     * The actual channel_code Xendit expects on the wire — distinct from
     * ->value (used for storage/labels), since bank codes need a
     * "_VIRTUAL_ACCOUNT" suffix for the Payment Requests API.
     */
    public function xenditChannelCode(): string
    {
        return match ($this) {
            self::Bca, self::Bni, self::Bri, self::Mandiri, self::Permata => $this->value.'_VIRTUAL_ACCOUNT',
            default => $this->value,
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
     * Xendit's test-mode simulate endpoint only supports VA, retail (OTC),
     * and QR channels — not e-wallets, which use a push/redirect flow.
     */
    public function supportsSimulation(): bool
    {
        return $this->viewType() !== 'ewallet';
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
            $properties = [
                'success_return_url' => $data['success_return_url'] ?? config('app.url'),
                'failure_return_url' => $data['failure_return_url'] ?? config('app.url'),
            ];

            // OVO charges are tied to the customer's registered mobile number.
            if ($this === self::Ovo) {
                $properties['account_mobile_number'] = $data['mobile_number'] ?? '+628123456789';
            }

            return $properties;
        }

        if ($this->requestType() === 'REUSABLE_PAYMENT_CODE') {
            $properties = [
                'display_name' => $data['display_name'] ?? config('app.name'),
            ];

            // Retail outlets (Alfamart, Indomaret) also require the payer's name.
            if ($this === self::Alfamart || $this === self::Indomaret) {
                $properties['payer_name'] = $data['payer_name'] ?? config('app.name');
            }

            return $properties;
        }

        return [];
    }
}
