<?php

namespace App\Livewire\Admin\Contacts;

use App\Models\ContactSubmission;
use App\Models\User;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class ContactList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public array $selected = [];

    public bool $selectAll = false;

    public bool $showModal = false;

    public ?int $viewingId = null;

    public bool $senderIsUser = false;

    #[Validate('required|string|min:10|max:5000')]
    public string $replyText = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selected = $this->getFilteredQuery()->pluck('id')->all();
        } else {
            $this->selected = [];
        }
    }

    public function updatedSelected(): void
    {
        $this->selectAll = false;
    }

    public function openModal(int $id): void
    {
        $this->viewingId = $id;
        $this->showModal = true;
        $this->replyText = '';
        $this->resetValidation('replyText');

        $submission = ContactSubmission::findOrFail($id);

        if ($submission->status === 'unread') {
            $submission->update(['status' => 'read']);
        }

        $this->senderIsUser = User::where('email', $submission->email)->exists();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->viewingId = null;
        $this->replyText = '';
        $this->senderIsUser = false;
        $this->resetValidation('replyText');
    }

    public function submitReply(int $id): void
    {
        $this->validate();

        $submission = ContactSubmission::findOrFail($id);

        $submission->update([
            'reply' => $this->replyText,
            'replied_at' => now(),
            'status' => 'replied',
        ]);

        // Bring your own Mailable:
        // Mail::to($submission->email)->queue(new \App\Mail\ContactReply($submission));

        $this->replyText = '';

        session()->flash('success', 'Reply saved for '.$submission->name.'.');
    }

    public function toggleReadStatus(int $id): void
    {
        $submission = ContactSubmission::findOrFail($id);

        $newStatus = $submission->status === 'unread' ? 'read' : 'unread';
        $submission->update(['status' => $newStatus]);
    }

    public function markSelectedAs(string $status): void
    {
        if (! in_array($status, ['read', 'unread'])) {
            return;
        }

        ContactSubmission::whereIn('id', $this->selected)->update(['status' => $status]);
        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;

        session()->flash('success', $count.' '.str('message')->plural($count).' marked as '.$status.'.');
    }

    public function archiveSubmission(int $id): void
    {
        ContactSubmission::where('id', $id)->update(['archived_at' => now()]);
        $this->closeModal();

        session()->flash('success', 'Message archived.');
    }

    public function unarchiveSubmission(int $id): void
    {
        ContactSubmission::where('id', $id)->update(['archived_at' => null]);
        $this->closeModal();

        session()->flash('success', 'Message unarchived.');
    }

    public function archiveSelected(): void
    {
        ContactSubmission::whereIn('id', $this->selected)->update(['archived_at' => now()]);
        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;

        session()->flash('success', $count.' '.str('message')->plural($count).' archived.');
    }

    public function deleteSubmission(int $id): void
    {
        ContactSubmission::find($id)?->delete();
        $this->closeModal();

        session()->flash('success', 'Contact submission deleted.');
    }

    public function deleteSelected(): void
    {
        ContactSubmission::whereIn('id', $this->selected)->delete();
        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;

        session()->flash('success', $count.' '.str('message')->plural($count).' deleted.');
    }

    public function copyToTicket(int $id): void
    {
        if (! class_exists(\App\Models\SupportTicket::class) || ! class_exists(\App\Models\TicketReply::class)) {
            session()->flash('error', 'Install the support-tickets module to use this action.');

            return;
        }

        $submission = ContactSubmission::findOrFail($id);
        $user = User::where('email', $submission->email)->first();

        if (! $user) {
            session()->flash('error', 'No registered user found with email '.$submission->email);

            return;
        }

        $ticket = \App\Models\SupportTicket::create([
            'user_id' => $user->id,
            'subject' => $submission->subject,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        \App\Models\TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $submission->message,
            'is_admin_reply' => false,
        ]);

        if ($submission->reply) {
            \App\Models\TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'message' => $submission->reply,
                'is_admin_reply' => true,
            ]);
        }

        $this->closeModal();

        session()->flash('success', 'Copied to support ticket #'.$ticket->ulid);
    }

    private function getFilteredQuery()
    {
        return ContactSubmission::query()
            ->when($this->search, function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('subject', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter === 'archived', fn ($q) => $q->archived(), fn ($q) => $q->notArchived())
            ->when(in_array($this->statusFilter, ['unread', 'read', 'replied']), fn ($q) => $q->where('status', $this->statusFilter));
    }

    public function supportTicketsInstalled(): bool
    {
        return class_exists(\App\Models\SupportTicket::class);
    }

    public function render()
    {
        $submissions = $this->getFilteredQuery()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => ContactSubmission::notArchived()->count(),
            'unread' => ContactSubmission::notArchived()->where('status', 'unread')->count(),
            'replied' => ContactSubmission::notArchived()->where('status', 'replied')->count(),
            'archived' => ContactSubmission::archived()->count(),
        ];

        $viewingSubmission = $this->viewingId
            ? ContactSubmission::find($this->viewingId)
            : null;

        return view('livewire.admin.contacts.contact-list', [
            'submissions' => $submissions,
            'stats' => $stats,
            'viewingSubmission' => $viewingSubmission,
        ])->layout('layouts.admin-sidebar');
    }
}
