<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Shipbytes\UiKit\Livewire\Concerns\WithDesignSystemPagination;

#[Layout('layouts.admin-sidebar')]
class UserList extends Component
{
    use WithDesignSystemPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $users = User::query()
            // The Admin badge asks hasRole() per row; one query, not 25.
            ->when(method_exists(User::class, 'roles'), fn ($q) => $q->with('roles'))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(25);

        return view('livewire.admin.users.user-list', compact('users'));
    }
}
