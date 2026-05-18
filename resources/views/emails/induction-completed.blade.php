@extends('emails.layout', ['preheader' => 'Induction completed — '.config('app.name')])

@section('content')
    <p style="margin:0 0 16px;">Hello {{ $enrollment->user->name }},</p>
    <p style="margin:0 0 16px;">You have completed your online induction for <strong>{{ $enrollment->version->policy->name }}</strong> (version {{ $enrollment->version->version_label }}).</p>
    <p style="margin:0 0 16px;">Your signed acknowledgement PDF is attached to this message for your records.</p>
    <p style="margin:0 0 16px;">If you have questions, contact your manager or HR.</p>
@endsection
