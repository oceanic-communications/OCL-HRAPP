@extends('emails.layout', ['preheader' => 'Your sign-in code for '.config('app.name')])

@section('content')
    <p style="margin:0 0 16px;">Hello {{ $userName }},</p>
    <p style="margin:0 0 20px;">Use this code to finish signing in to {{ config('app.name') }}:</p>
    <p style="margin:0 0 24px;padding:16px 20px;background-color:#f7f3eb;border-radius:10px;border:1px solid #d4cfc4;text-align:center;font-size:26px;font-weight:700;letter-spacing:0.35em;color:#003a73;font-family:'Courier New',Consolas,monospace;">{{ $code }}</p>
    <p style="margin:0 0 16px;">This code expires in {{ \App\Services\LoginOtpService::TTL_MINUTES }} min. If you did not try to sign in, you can ignore this email.</p>
@endsection
