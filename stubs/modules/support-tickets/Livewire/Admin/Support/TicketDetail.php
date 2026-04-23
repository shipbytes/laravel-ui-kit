<?php

namespace App\Livewire\Admin\Support;

use App\Models\SupportTicket;
use App\Models\TicketReply;
use Livewire\Attributes\Validate;
use Livewire\Component;

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

        TicketReply::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => auth()->id(),
            'message' => $this->reply,
            'is_admin_reply' => true,
        ]);

        $this->reply = '';
        $this->ticket->load(['replies.user']);

        session()->flash('success', 'Reply sent to user.');
    }

    public function updateStatus(): void
    {
        if ($this->newStatus === $this->ticket->status) {
            return;
        }

        $this->ticket->status = $this->newStatus;
        $this->ticket->closed_at = in_array($this->newStatus, ['resolved', 'closed']) ? now() : null;
        $this->ticket->save();

        session()->flash('success', 'Status updated to '.str_replace('_', ' ', $this->newStatus).'.');
    }

    public function updatePriority(): void
    {
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
