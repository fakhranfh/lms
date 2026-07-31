<?php

namespace App\Console\Commands;

use App\Enums\XenditChannel;
use App\Models\PaymentChannel;
use App\Services\R2StorageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('channels:sync-logos')]
#[Description("Download each payment channel's real icon from its official website (or Google's favicon proxy as a fallback) and upload it to R2.")]
class SyncChannelLogos extends Command
{
    /**
     * Source URL for each channel's real icon, pulled from the provider's
     * own site where a direct asset was found; falls back to Google's
     * favicon proxy (https://www.google.com/s2/favicons) for the rest.
     *
     * @var array<string, string>
     */
    private const LOGO_URLS = [
        'QRIS' => 'https://www.google.com/s2/favicons?domain=qris.id&sz=128',
        'OVO' => 'https://www.google.com/s2/favicons?domain=www.ovo.id&sz=128',
        'DANA' => 'https://www.google.com/s2/favicons?domain=dana.id&sz=128',
        'LINKAJA' => 'https://linkaja.id/assets/apple-touch-icon.png',
        'SHOPEEPAY' => 'https://www.google.com/s2/favicons?domain=shopeepay.co.id&sz=128',
        'BCA' => 'https://bca.co.id/homepage/assets/images/favicon-bca.png',
        'BNI' => 'https://www.google.com/s2/favicons?domain=bni.co.id&sz=128',
        'BRI' => 'https://www.google.com/s2/favicons?domain=bri.co.id&sz=128',
        'MANDIRI' => 'https://www.bankmandiri.co.id/o/mandiri-corporate-theme/images/favicon.ico',
        'PERMATA' => 'https://www.google.com/s2/favicons?domain=permatabank.com&sz=128',
        'ALFAMART' => 'https://alfamart.co.id/frontend/ico/apple-touch-icon.png',
        'INDOMARET' => 'https://www.google.com/s2/favicons?domain=www.indomaret.co.id&sz=128',
    ];

    public function handle(R2StorageService $r2Storage): int
    {
        foreach (XenditChannel::cases() as $channel) {
            $url = self::LOGO_URLS[$channel->value];

            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 ('.config('app.name').')'])
                ->timeout(15)
                ->get($url);

            $contentType = $response->header('Content-Type');

            if (! $response->successful() || ! str_starts_with($contentType, 'image/')) {
                $this->error("Failed to download a real image for {$channel->value} from {$url} (got: {$contentType})");

                continue;
            }

            // No file extension on the key: the object's Content-Type header
            // (set below) is what browsers use to render it, so
            // XenditChannel::logoUrl() doesn't need to track per-channel format.
            $key = "channel-logos/{$channel->value}";

            $r2Storage->uploadRawContent($key, $response->body(), $contentType);

            // Store the bare key, not the full URL, so the logo stays reachable
            // if the R2 base/custom domain ever changes.
            PaymentChannel::where('code', $channel->value)->update(['logo_url' => $key]);

            $this->line("Uploaded {$channel->value} -> {$key}");
        }

        return self::SUCCESS;
    }
}
