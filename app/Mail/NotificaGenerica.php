<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificaGenerica extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
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
                'oggetto'  => $this->oggetto,
                'corpo'    => $this->corpo,
                'commessa' => null,
            ],
        );
    }
}
