<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorCodeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $vendorCode;
    public $vendorName;
    public $recipientEmail;
    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(string $vendorCode, string $vendorName, string $recipientEmail, ?string $customMessage = null)
    {
        $this->vendorCode = $vendorCode;
        $this->vendorName = $vendorName;
        $this->recipientEmail = $recipientEmail;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Third Party Vendor Code - ' . $this->vendorName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.vendor-code',
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
