<?php

namespace App\Livewire\Admin\Changelog;

use App\Models\ChangelogEntry;
use Livewire\Component;
use Livewire\WithPagination;

class ChangelogList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => 'all'],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function deleteEntry(int $id): void
    {
        ChangelogEntry::find($id)?->delete();
        session()->flash('success', 'Changelog entry deleted.');
    }

    public function render()
    {
        $entries = ChangelogEntry::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderBy('published_at', 'desc')
            ->paginate(20);

        return view('livewire.admin.changelog.changelog-list', [
            'entries' => $entries,
        ])->layout('layouts.admin-sidebar');
    }
}
