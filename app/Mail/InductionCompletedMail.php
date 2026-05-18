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

    public function __construct(
        public InductionEnrollment $enrollment,
        public ?string $hrCopyEmail = null,
    ) {}

    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: 'Induction completed – '.config('app.name'),
        );

        $hr = $this->hrCopyEmail;
        if (is_string($hr) && filter_var($hr, FILTER_VALIDATE_EMAIL)
            && strtolower($hr) !== strtolower($this->enrollment->user->email)) {
            $envelope = $envelope->cc($hr);
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.induction-completed',
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

        return [
            Attachment::fromStorageDisk($disk, $path)
                ->as('induction-acknowledgement.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
