<?php

namespace App\Mail;

use App\Models\SupportTicket;
use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A new message on a ticket, both ways. $toAdmin picks the reader: true when
 * the user wrote and the admins are told (the link opens the admin page, and
 * replying writes to the user), false when an admin answered and the ticket's
 * owner is told (the link opens their own ticket page).
 */
class TicketReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public TicketReply $reply,
        public bool $toAdmin = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->toAdmin
                ? "[Support] New reply: {$this->ticket->subject}"
                : "New reply to your ticket: {$this->ticket->subject}",
            replyTo: $this->toAdmin
                ? [new Address($this->ticket->user->email, $this->ticket->user->name)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.support.ticket-reply',
            with: [
                'url' => $this->toAdmin
                    ? route('admin.support.show', $this->ticket->ulid)
                    : route('support.show', $this->ticket->ulid),
            ],
        );
    }
}
