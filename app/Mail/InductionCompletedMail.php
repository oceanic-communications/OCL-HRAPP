<?php

namespace App\Mail;

use App\Models\InductionEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InductionCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const RECIPIENT_EMPLOYEE = 'employee';

    public const RECIPIENT_HR = 'hr';

    public function __construct(
        public InductionEnrollment $enrollment,
        public string $recipient = self::RECIPIENT_EMPLOYEE,
    ) {}

    public function envelope(): Envelope
    {
        $policyName = $this->enrollment->version->policy->name;
        $employeeName = $this->enrollment->user->name;

        if ($this->recipient === self::RECIPIENT_HR) {
            return new Envelope(
                subject: 'Induction completed – '.$employeeName.' – '.config('app.name'),
            );
        }

        return new Envelope(
            subject: 'Induction completed – '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->recipient === self::RECIPIENT_HR
                ? 'emails.induction-completed-hr'
                : 'emails.induction-completed',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $disk = $this->enrollment->completion_pdf_disk;
        $path = $this->enrollment->completion_pdf_path;
        if ($disk === null || $path === null || ! Storage::disk($disk)->exists($path)) {
            return [];
        }

        $filename = $this->recipient === self::RECIPIENT_HR
            ? 'induction-acknowledgement-'.$this->enrollment->user->id.'.pdf'
            : 'induction-acknowledgement.pdf';

        return [
            Attachment::fromStorageDisk($disk, $path)
                ->as($filename)
                ->withMime('application/pdf'),
        ];
    }
}
