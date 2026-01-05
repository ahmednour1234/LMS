<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $purpose;

    /**
     * Create a new message instance.
     */
    public function __construct(string $code, string $purpose)
    {
        $this->code = $code;
        $this->purpose = $purpose;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->purpose) {
            'register_verify' => 'Verify Your Email Address',
            'password_reset' => 'Reset Your Password',
            default => 'Your OTP Code',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = match ($this->purpose) {
            'register_verify' => 'emails.otp.verify',
            'password_reset' => 'emails.otp.reset',
            default => 'emails.otp.default',
        };

        return new Content(
            view: $view,
            with: [
                'code' => $this->code,
                'purpose' => $this->purpose,
            ],
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

