<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Storage Quota Alert - {{ config('app.name') }}</title>
    <style>
        body { margin:0; padding:0; background-color:#F9FAFB; }
        table { border-collapse:collapse; }
    </style>
</head>
<body style="margin:0;padding:24px;background-color:#F9FAFB;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#191b23;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #c3c6d7;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(16,24,40,0.06);">
        <tr>
            <td style="padding:16px 24px;background:#ffffff;border-bottom:1px solid #e1e2ed;text-align:center;">
                <span style="font-size:20px;line-height:24px;font-weight:700;color:#004ac6;">{{ config('app.name') }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:40px 32px;text-align:center;">
                @php
                    $color = match(true) {
                        $threshold >= 100 => '#dc2626',
                        $threshold >= 90 => '#ea580c',
                        default => '#ca8a04',
                    };
                @endphp
                <h1 style="margin:0 0 16px;font-size:24px;line-height:32px;font-weight:700;color:{{ $color }};text-align:center;">
                    @if($threshold >= 100)
                        ❌ Storage quota full
                    @elseif($threshold >= 90)
                        ⚠️ Storage at 90%
                    @else
                        Storage at 80%
                    @endif
                </h1>

                <p style="margin:0 0 14px;font-size:16px;line-height:24px;color:#374151;max-width:480px;margin-left:auto;margin-right:auto;">
                    Global material storage is at <strong>{{ number_format($percentage, 1) }}%</strong> ({{ $usedFormatted }} of {{ $quotaFormatted }}).
                </p>

                <p style="margin:0 0 10px;font-size:15px;line-height:22px;color:#374151;max-width:480px;margin-left:auto;margin-right:auto;">
                    @if($threshold >= 100)
                        All uploads are now blocked. Delete or archive materials to resume.
                    @elseif($threshold >= 90)
                        Uploads may start failing soon. Consider freeing up space.
                    @else
                        Consider planning an archival strategy for older materials.
                    @endif
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:20px 24px;background:#F9FAFB;text-align:center;border-top:1px solid #e1e2ed;">
                <div style="font-size:12px;color:#9ca3af;margin-top:8px;">© {{ date('Y') }} {{ config('app.name') }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
