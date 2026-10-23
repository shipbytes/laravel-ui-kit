<?php

namespace App\Livewire\Support;

use App\Mail\TicketReplyMail;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Shipbytes\UiKit\Livewire\Concerns\WithSpamProtection;
use Shipbytes\UiKit\Support\UiKit;

#[Layout('layouts.user-shell')]
class TicketDetail extends Component
{
    use AuthorizesRequests;
    use WithSpamProtection;

    public SupportTicket $ticket;

    public string $reply = '';

    public function mount(string $ulid): void
    {
        $this->ticket = SupportTicket::where('ulid', $ulid)
            ->with(['user', 'replies.user'])
            ->firstOrFail();

        $this->authorize('view', $this->ticket);
    }

    public function submitReply(): void
    {
        $this->authorize('view', $this->ticket);

        if ($this->ticket->isClosed()) {
            $this->addError('reply', 'This ticket is closed. Reopen it to reply.');

            return;
        }

        $this->validate(['reply' => ['required', 'string', 'min:2', 'max:5000']]);

        if (! $this->passesRateLimit('support-ticket:reply:'.$this->ticket->id, 10)) {
            return;
        }

        $reply = TicketReply::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => Auth::id(),
            'message' => $this->reply,
            'is_admin_reply' => false,
        ]);

        UiKit::sendMail(UiKit::admins(), new TicketReplyMail($this->ticket, $reply, toAdmin: true));

        $this->reset('reply');
        $this->ticket->load(['replies.user']);

        session()->flash('success', 'Your reply was added.');
    }

    public function closeTicket(): void
    {
        $this->authorize('update', $this->ticket);

        $this->ticket->close();

        session()->flash('success', 'The ticket is closed.');
    }

    public function reopenTicket(): void
    {
        $this->authorize('update', $this->ticket);

        $this->ticket->reopen();

        session()->flash('success', 'The ticket is open again.');
    }

    public function render()
    {
        return view('livewire.support.ticket-detail');
    }
}
