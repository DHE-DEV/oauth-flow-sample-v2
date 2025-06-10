<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TokenEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $tokenData;
    public $clientId;
    public $timestamp;

    /**
     * Create a new message instance.
     */
    public function __construct($tokenData, $clientId, $timestamp)
    {
        $this->tokenData = $tokenData;
        $this->clientId = $clientId;
        $this->timestamp = $timestamp;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'OAuth2 Tokens - Passolution API',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tokens',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}