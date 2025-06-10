<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OAuth2TokenMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $flowData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $flowData)
    {
        $this->flowData = $flowData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'OAuth2 Tokens - ' . ($this->flowData['client_id'] ?? 'Unknown Client'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.oauth2-tokens',
            with: [
                'tokens' => $this->flowData['tokens'] ?? [],
                'clientId' => $this->flowData['client_id'] ?? 'N/A',
                'redirectUri' => $this->flowData['redirect_uri'] ?? 'N/A',
                'generatedAt' => now()->format('Y-m-d H:i:s'),
            ]
        );
    }
}