<?php

namespace App\Mail;

use App\Models\Commessa;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificaCommessa extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Commessa $commessa,
        public readonly string $oggetto,
        public readonly string $corpo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->oggetto);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notifica-commessa',
            text: 'emails.notifica-commessa-text',
            with: [
                'oggetto' => $this->oggetto,
                'corpo'   => $this->corpo,
                'commessa' => $this->commessa,
            ],
        );
    }
}
