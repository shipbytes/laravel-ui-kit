<?php

namespace App\Livewire\Support;

use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Shipbytes\UiKit\Livewire\Concerns\WithDesignSystemPagination;

#[Layout('layouts.user-shell')]
class TicketList extends Component
{
    use WithDesignSystemPagination;

    #[Url(as: 'status', except: 'all')]
    public string $statusFilter = 'all';

    /** @var array<string, string> */
    public array $statusOptions = [
        'all' => 'All tickets',
        'open' => 'Open',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $mine = SupportTicket::query()->where('user_id', Auth::id());

        $tickets = (clone $mine)
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->withCount('replies')
            ->latest()
            ->paginate(15);

        return view('livewire.support.ticket-list', [
            'tickets' => $tickets,
            // A filter that matches nothing is not the same as no tickets at all.
            'hasAny' => $tickets->isNotEmpty() || (clone $mine)->exists(),
        ]);
    }
}
