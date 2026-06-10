@extends('emails.layout')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:{{ config('mail-brand.colors.text') }};">
        Hi {{ $name }},
    </p>

    <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:{{ config('mail-brand.colors.muted') }};">
        To get started, please complete your account setup by using the onboarding link below:
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
        <tr>
            <td style="border-radius:8px;background-color:{{ config('mail-brand.colors.primary') }};">
                <a href="{{ $onboardingUrl }}" target="_blank" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:8px;">
                    Complete Onboarding
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 12px;font-size:15px;line-height:1.7;color:{{ config('mail-brand.colors.muted') }};">
        This link will guide you through a quick setup where you can:
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;width:100%;">
        <tr>
            <td style="padding:16px 18px;background-color:#f8fafc;border:1px solid {{ config('mail-brand.colors.border') }};border-radius:12px;">
                <p style="margin:0 0 8px;font-size:14px;line-height:1.7;color:{{ config('mail-brand.colors.text') }};">- Create your login credentials</p>
                <p style="margin:0 0 8px;font-size:14px;line-height:1.7;color:{{ config('mail-brand.colors.text') }};">- Enter your basic details</p>
                <p style="margin:0;font-size:14px;line-height:1.7;color:{{ config('mail-brand.colors.text') }};">- Accept the Terms &amp; Conditions</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:{{ config('mail-brand.colors.muted') }};">
        For security purposes, you will also need to verify your email using an OTP during the process.
    </p>

    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:{{ config('mail-brand.colors.muted') }};">
        Once completed, your account will be activated and you will be ready to start using TruckBill.
    </p>

    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:{{ config('mail-brand.colors.muted') }};">
        If you face any issues or need assistance at any step, feel free to reply to this email or contact us.
    </p>

    <p style="margin:0;font-size:15px;line-height:1.7;color:{{ config('mail-brand.colors.text') }};">
        Looking forward to having you onboard.
    </p>
@endsection
