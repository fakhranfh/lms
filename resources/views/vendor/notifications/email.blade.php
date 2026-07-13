<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $actionText ?? 'Verify Your Email' }} - {{ config('app.name') }}</title>
    <style>
        /* Basic reset for email clients */
        body { margin:0; padding:0; background-color:#F9FAFB; }
        table { border-collapse:collapse; }
        img { border:0; -ms-interpolation-mode:bicubic; }
    </style>
</head>
<body style="margin:0;padding:24px;background-color:#F9FAFB;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#191b23;">
    <!-- Outer wrapper centered -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #c3c6d7;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(16,24,40,0.06);">
        <tr>
            <td style="padding:16px 24px;background:#ffffff;border-bottom:1px solid #e1e2ed;text-align:center;">
                <div style="display:inline-flex;align-items:center;gap:8px;color:#004ac6;font-weight:700;font-size:18px;">
                    <span style="font-size:20px;line-height:24px;">{{ config('app.name') }}</span>
                </div>
            </td>
        </tr>
        <tr>
        <td style="padding:40px 32px;text-align:center;">    

                <h1 style="margin:0 0 16px;font-size:24px;line-height:32px;font-weight:700;color:#0f1724;text-align:center;">{{ $actionText ?? 'Verify your email address' }}</h1>

                <!-- Intro lines -->
                @if (!empty($introLines))
                    @foreach ($introLines as $line)
                        <p style="margin:0 0 14px;font-size:16px;line-height:24px;color:#374151;max-width:480px;margin-left:auto;margin-right:auto;">{{ $line }}</p>
                    @endforeach
                @else
                    <p style="margin:0 0 18px;font-size:16px;line-height:24px;color:#374151;max-width:480px;margin-left:auto;margin-right:auto;">Hello, please click the button below to complete the action.</p>
                @endif

                <!-- Button -->
                @if (!empty($actionUrl) && !empty($actionText))
                    <div style="margin:20px 0;">
                        <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 24px;background:#004ac6;color:#ffffff;border-radius:999px;text-decoration:none;font-weight:600;font-size:15px;">{{ $actionText }}</a>
                    </div>
                @endif

                <!-- Outro lines / expiry note -->
                @if (!empty($outroLines))
                    @foreach ($outroLines as $line)
                        <p style="margin:0 0 10px;font-size:13px;line-height:18px;color:#6b7280;max-width:420px;margin-left:auto;margin-right:auto;">{{ $line }}</p>
                    @endforeach
                @else
                    <p style="margin:0 0 10px;font-size:13px;line-height:18px;color:#6b7280;max-width:420px;margin-left:auto;margin-right:auto;">This link will expire in 48 hours. If you did not create an account, please ignore this email.</p>
                @endif
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