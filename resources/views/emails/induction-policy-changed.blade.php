@extends('emails.layout', ['preheader' => $changeNotification->notificationTitle()])

@section('content')
    <p style="margin:0 0 16px;">Hello {{ $recipient->name }},</p>
    <p style="margin:0 0 12px;font-weight:600;color:#003a73;">{{ $changeNotification->notificationTitle() }}</p>
    <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;margin:0 0 16px;border:1px solid #d4cfc4;border-radius:8px;">
        <tr>
            <td style="padding:12px 14px;font-size:14px;line-height:1.5;">
                <p style="margin:0 0 8px;"><strong>Policy:</strong> {{ $changeNotification->policyName }} ({{ $changeNotification->policyAbbreviation }})</p>
                <p style="margin:0 0 8px;"><strong>Change type:</strong> {{ $changeNotification->changeTypeLabel() }}</p>
                <p style="margin:0 0 8px;"><strong>Level:</strong> {{ $changeNotification->levelLabel() }}</p>
                @if ($changeNotification->clauseTitle)
                    <p style="margin:0 0 8px;"><strong>Clause:</strong> {{ $changeNotification->clauseTitle }}</p>
                @endif
                @if ($changeNotification->subClauseTitle)
                    <p style="margin:0;"><strong>Sub-clause:</strong> {{ $changeNotification->subClauseTitle }}</p>
                @endif
            </td>
        </tr>
    </table>
    <p style="margin:0 0 16px;">{{ $changeNotification->notificationBody($requiresRepeat) }}</p>
    <p style="margin:0 0 24px;">
        <a href="{{ route('portal.induction') }}" style="display:inline-block;padding:12px 22px;background-color:#003a73;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">Open induction</a>
    </p>
@endsection
