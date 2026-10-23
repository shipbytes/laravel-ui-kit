<?php

namespace App\Livewire\Admin\Support;

use App\Mail\TicketReplyMail;
use App\Mail\TicketStatusChangedMail;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Shipbytes\UiKit\Support\UiKit;

class TicketDetail extends Component
{
    public SupportTicket $ticket;

    #[Validate('required|string|min:10|max:5000')]
    public string $reply = '';

    public string $newStatus;

    public string $newPriority;

    public function mount($ulid): void
    {
        $this->ticket = SupportTicket::where('ulid', $ulid)
            ->with(['user', 'replies.user'])
            ->firstOrFail();

        $this->newStatus = $this->ticket->status;
        $this->newPriority = $this->ticket->priority;
    }

    public function submitReply(): void
    {
        $this->validate();

        $reply = TicketReply::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => auth()->id(),
            'message' => $this->reply,
            'is_admin_reply' => true,
        ]);

        $this->reply = '';
        $this->ticket->load(['replies.user']);

        // Saved first: a mail that fails is logged and never loses the reply.
        $failure = $this->ticket->user
            ? UiKit::sendMail($this->ticket->user, new TicketReplyMail($this->ticket, $reply, toAdmin: false))
            : null;

        if ($failure) {
            session()->flash('error', 'Reply saved, but the email failed: '.$failure->getMessage());
        } else {
            session()->flash('success', 'Reply sent to '.($this->ticket->user->email ?? 'the user').'.');
        }
    }

    public function updateStatus(): void
    {
        $this->validate([
            'newStatus' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        if ($this->newStatus === $this->ticket->status) {
            return;
        }

        $oldStatus = $this->ticket->status;

        $this->ticket->status = $this->newStatus;
        $this->ticket->closed_at = in_array($this->newStatus, ['resolved', 'closed']) ? now() : null;
        $this->ticket->save();

        $failure = $this->ticket->user
            ? UiKit::sendMail($this->ticket->user, new TicketStatusChangedMail($this->ticket, $oldStatus, $this->newStatus))
            : null;

        $updated = 'Status updated to '.str_replace('_', ' ', $this->newStatus);

        if ($failure) {
            session()->flash('error', $updated.', but the email failed: '.$failure->getMessage());
        } else {
            session()->flash('success', $updated.'.');
        }
    }

    public function updatePriority(): void
    {
        $this->validate([
            'newPriority' => ['required', 'in:low,medium,high'],
        ]);

        if ($this->newPriority === $this->ticket->priority) {
            return;
        }

        $this->ticket->priority = $this->newPriority;
        $this->ticket->save();

        session()->flash('success', 'Priority updated to '.$this->newPriority.'.');
    }

    public function render()
    {
        return view('livewire.admin.support.ticket-detail')->layout('layouts.admin-sidebar');
    }
}
