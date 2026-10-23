<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * To the admins (UiKit::admins()) when a user opens a ticket. Replying to it
 * writes to the user; the button opens the ticket in the admin panel.
 */
class NewTicketMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Support] New ticket: {$this->ticket->subject}",
            replyTo: [new Address($this->ticket->user->email, $this->ticket->user->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.support.new-ticket',
            with: [
                'url' => route('admin.support.show', $this->ticket->ulid),
                // Not "message": every mail view already has a $message, the message being built.
                'opening' => (string) $this->ticket->replies()->value('message'),
            ],
        );
    }
}
