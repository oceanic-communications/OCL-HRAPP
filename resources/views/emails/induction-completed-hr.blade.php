@extends('emails.layout', ['preheader' => 'Employee induction completed — '.config('app.name')])

@section('content')
    <p style="margin:0 0 16px;">Hello,</p>
    <p style="margin:0 0 16px;"><strong>{{ $enrollment->user->name }}</strong> ({{ $enrollment->user->email }}) has completed online induction for <strong>{{ $enrollment->version->policy->name }}</strong> (version {{ $enrollment->version->version_label }}).</p>
    <p style="margin:0 0 16px;">The attached PDF includes each induction section’s content and the employee’s sign-off (name, date and time, and digital signature where required) for every section.</p>
    <p style="margin:0 0 16px;">Completed at: {{ $enrollment->completed_at?->timezone(config('app.timezone'))->format('d M Y, g:i A T') }}.</p>
@endsection
