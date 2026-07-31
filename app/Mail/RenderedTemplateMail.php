<?php

namespace App\Mail;

use App\Services\Communications\RenderedEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RenderedTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly RenderedEmailTemplate $rendered) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->rendered->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.templated',
            text: 'emails.templated-text',
            with: [
                'template' => $this->rendered,
            ],
        );
    }
}
