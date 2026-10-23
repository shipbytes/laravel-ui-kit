<?php

namespace App\Livewire\Support;

use App\Mail\NewTicketMail;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Shipbytes\UiKit\Livewire\Concerns\WithSpamProtection;
use Shipbytes\UiKit\Support\UiKit;

#[Layout('layouts.user-shell')]
class CreateTicket extends Component
{
    use WithSpamProtection;

    public string $subject = '';

    public string $category = '';

    public string $message = '';

    protected function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(array_keys(SupportTicket::categories()))],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
        ];
    }

    public function submit()
    {
        if ($this->caughtInHoneypot()) {
            return $this->redirect(route('support.index'), navigate: true);
        }

        $this->validate();

        if (! $this->passesRateLimit('support-ticket:create:'.Auth::id(), 5)) {
            return null;
        }

        // The ticket and its opening message exist together or not at all.
        $ticket = DB::transaction(function () {
            $ticket = SupportTicket::create([
                'user_id' => Auth::id(),
                'subject' => $this->subject,
                'category' => $this->category,
                'priority' => 'medium',
                'status' => 'open',
                'ip_address' => request()->ip(),
            ]);

            TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'message' => $this->message,
                'is_admin_reply' => false,
            ]);

            return $ticket;
        });

        // Saved first: a mail that fails is logged and never loses the ticket.
        UiKit::sendMail(UiKit::admins(), new NewTicketMail($ticket));

        session()->flash('success', "Your ticket is open. We'll reply here and by email.");

        return $this->redirect(route('support.show', $ticket->ulid), navigate: true);
    }

    public function render()
    {
        return view('livewire.support.create-ticket', [
            'categories' => SupportTicket::categories(),
        ]);
    }
}
