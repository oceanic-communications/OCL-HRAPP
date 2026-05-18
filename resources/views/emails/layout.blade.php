<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
</head>
<body style="margin:0;padding:0;background-color:#f7f3eb;-webkit-text-size-adjust:100%;">
@if (! empty($preheader ?? ''))
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ $preheader }}</div>
@endif
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f7f3eb;">
        <tr>
            <td align="center" style="padding:28px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;">
                    <tr>
                        <td style="background-color:#ffffff;border-radius:12px 12px 0 0;padding:24px 24px 20px;text-align:center;border-bottom:4px solid #1cafbf;">
                            <p style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:20px;font-weight:600;color:#003a73;">{{ config('app.name') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff;border:1px solid #d4cfc4;border-top:0;border-radius:0 0 12px 12px;padding:28px 26px;font-family:'Segoe UI',Inter,system-ui,-apple-system,sans-serif;font-size:16px;line-height:1.6;color:#1c1c1c;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 12px 8px;text-align:center;font-family:'Segoe UI',Inter,system-ui,sans-serif;font-size:13px;line-height:1.55;color:#5a5a5a;">
                            <p style="margin:0 0 6px;font-weight:600;color:#003a73;">&copy; {{ config('app.name') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
