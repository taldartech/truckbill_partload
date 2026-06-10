<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? config('mail-brand.name') }}</title>
</head>
<body style="margin:0;padding:0;background-color:{{ config('mail-brand.colors.background') }};font-family:Inter,Arial,Helvetica,sans-serif;color:{{ config('mail-brand.colors.text') }};">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:{{ config('mail-brand.colors.background') }};padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background-color:{{ config('mail-brand.colors.surface') }};border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,0.08);">
                    <tr>
                        <td style="padding:32px 32px 24px;text-align:center;border-bottom:1px solid {{ config('mail-brand.colors.border') }};">
                            <img src="{{ config('mail-brand.logo_url') }}" alt="{{ config('mail-brand.name') }} Logo" width="120" style="display:block;margin:0 auto 12px;max-width:120px;height:auto;">
                            <p style="margin:0;font-size:13px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;color:{{ config('mail-brand.colors.muted') }};">
                                Transport. Billing. Simplified.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;background-color:#f8fafc;border-top:1px solid {{ config('mail-brand.colors.border') }};">
                            <p style="margin:0 0 8px;font-size:14px;line-height:1.6;color:{{ config('mail-brand.colors.text') }};">
                                Best regards,<br>
                                <strong>Team {{ config('mail-brand.name') }}</strong>
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;">
                                <a href="{{ config('mail-brand.website') }}" style="color:{{ config('mail-brand.colors.primary') }};text-decoration:none;">
                                    {{ str_replace(['https://', 'http://'], '', config('mail-brand.website')) }}
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
