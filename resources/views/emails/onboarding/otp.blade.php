@extends('emails.layout')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:{{ config('mail-brand.colors.text') }};">
        Hi {{ $name }},
    </p>

    <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:{{ config('mail-brand.colors.muted') }};">
        Use the one-time password below to verify your email during onboarding:
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;width:100%;">
        <tr>
            <td align="center" style="padding:24px;background-color:#ecfdf5;border:1px solid #bbf7d0;border-radius:12px;">
                <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ config('mail-brand.colors.primary_dark') }};">
                    Verification OTP
                </p>
                <p style="margin:0;font-size:34px;font-weight:700;letter-spacing:0.35em;color:{{ config('mail-brand.colors.primary_dark') }};">
                    {{ $otp }}
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:{{ config('mail-brand.colors.muted') }};">
        This OTP expires in {{ $ttl }} minutes.
    </p>

    <p style="margin:0;font-size:14px;line-height:1.7;color:{{ config('mail-brand.colors.muted') }};">
        If you did not request this, you can ignore this email.
    </p>
@endsection
