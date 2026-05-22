@extends('emails.layout', ['preheader' => 'You now have access to the '.config('app.name').' HR portal'])

@section('content')
    <p style="margin:0 0 16px;">Hello {{ $user->name }},</p>
    <p style="margin:0 0 16px;">Your account has been set up for the <strong>{{ config('app.name') }}</strong> HR portal. You can sign in with this email address to complete induction, view policies, and use other features assigned to your role.</p>
    <p style="margin:0 0 24px;">
        <a href="{{ route('login') }}" style="display:inline-block;padding:12px 22px;background-color:#003a73;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">Sign in to the portal</a>
    </p>
    <p style="margin:0 0 16px;">When you sign in, you will receive a one-time code by email. If you did not expect this message, contact your HR administrator.</p>
@endsection
