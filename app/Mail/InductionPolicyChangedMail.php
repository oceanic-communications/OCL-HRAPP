<?php

namespace App\Mail;

use App\Models\InductionPolicyVersion;
use App\Models\User;
use App\Support\InductionPolicyChangeNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InductionPolicyChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public InductionPolicyVersion $version,
        public User $recipient,
        public InductionPolicyChangeNotification $changeNotification,
        public bool $requiresRepeat = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->changeNotification->emailSubject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.induction-policy-changed',
        );
    }
}
