<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $judul,
        public string $isi,
        public string $kategori,
        public string $recipientName,
        public string $tglTerbit,
        public string $publisherName,
        public ?string $actionUrl = null,
        public ?string $lampiranUrl = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pengumuman SDI BIM: {$this->judul}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.announcement',
            with: [
                'judul' => $this->judul,
                'isi' => $this->isi,
                'kategori' => $this->kategori,
                'recipientName' => $this->recipientName,
                'tglTerbit' => $this->tglTerbit,
                'publisherName' => $this->publisherName,
                'actionUrl' => $this->actionUrl,
                'lampiranUrl' => $this->lampiranUrl,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

