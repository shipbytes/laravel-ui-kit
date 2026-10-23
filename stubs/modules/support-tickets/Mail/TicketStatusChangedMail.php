<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * To the ticket's owner when an admin moves it to another status.
 */
class TicketStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ticket is '.strtolower(SupportTicket::statusLabelFor($this->newStatus)).": {$this->ticket->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.support.status-changed',
            with: [
                'url' => route('support.show', $this->ticket->ulid),
                'oldLabel' => SupportTicket::statusLabelFor($this->oldStatus),
                'newLabel' => SupportTicket::statusLabelFor($this->newStatus),
                'isClosed' => in_array($this->newStatus, ['resolved', 'closed'], true),
            ],
        );
    }
}
