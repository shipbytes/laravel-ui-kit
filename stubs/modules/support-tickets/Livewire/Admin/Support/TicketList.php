<?php

namespace App\Livewire\Admin\Support;

use App\Models\SupportTicket;
use Livewire\Component;
use Livewire\WithPagination;

class TicketList extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public string $priorityFilter = 'all';

    public string $categoryFilter = 'all';

    public string $search = '';

    public array $statusOptions = [
        'all' => 'All Statuses',
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    public array $priorityOptions = [
        'all' => 'All Priorities',
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    public array $categoryOptions = [
        'all' => 'All Categories',
        'general' => 'General',
        'bug' => 'Bug',
        'feature_request' => 'Feature Request',
        'billing' => 'Billing',
        'other' => 'Other',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'priorityFilter' => ['except' => 'all'],
        'categoryFilter' => ['except' => 'all'],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $tickets = SupportTicket::query()
            ->with('user')
            ->withCount('replies')
            ->when($this->search, function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('subject', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', function ($userQuery) {
                            $userQuery->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('email', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter !== 'all', fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::open()->count(),
            'in_progress' => SupportTicket::inProgress()->count(),
            'resolved' => SupportTicket::resolved()->count(),
        ];

        return view('livewire.admin.support.ticket-list', [
            'tickets' => $tickets,
            'stats' => $stats,
        ])->layout('layouts.admin-sidebar');
    }
}
