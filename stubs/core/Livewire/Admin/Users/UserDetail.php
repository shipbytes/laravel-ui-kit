<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Shipbytes\UiKit\Support\UiKit;

#[Layout('layouts.admin-sidebar')]
class UserDetail extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Whether the Delete button shows. deleteUser() checks it again, because
     * a Livewire action can be called whether or not its button rendered.
     */
    public function canDelete(): bool
    {
        return ! $this->user->is(Auth::user()) && ! UiKit::isAdmin($this->user);
    }

    public function deleteUser(): void
    {
        // Yourself would end this session mid-request; another admin is a
        // demotion first (ui-kit:make-admin grants, nothing here revokes).
        abort_unless($this->canDelete(), 403);

        if ($this->user->avatar_path ?? null) {
            Storage::disk('public')->delete($this->user->avatar_path);
        }

        $this->user->delete();

        $this->redirectRoute(config('admin.route_name_prefix').'users.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.user-detail');
    }
}
