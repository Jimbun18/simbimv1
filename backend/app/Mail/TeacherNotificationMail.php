<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $recipientName,
        public string $bodyMessage,
        public ?string $actionUrl = null,
        public ?string $actionText = null,
        public array $extraDetails = [],
        public ?string $customSubject = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->customSubject ?: "SIM-BIM: {$this->title}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher_notification',
            with: [
                'title' => $this->title,
                'recipientName' => $this->recipientName,
                'bodyMessage' => $this->bodyMessage,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText ?? 'Buka di SIM-BIM',
                'extraDetails' => $this->extraDetails,
                'subject' => $this->customSubject ?: "SIM-BIM: {$this->title}",
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

