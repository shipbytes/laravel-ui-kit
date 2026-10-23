<?php

namespace App\Mail;

use App\Models\ContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The admin's answer from the inbox, sent to the sender: the reply first, then
 * the message it answers, quoted.
 */
class ContactReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContactSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Re: {$this->submission->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contact.reply');
    }
}
