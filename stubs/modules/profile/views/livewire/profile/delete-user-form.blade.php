<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component
{
    public string $password = '';

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = Auth::user();

        // The photo lives on the public disk under a random name, so nothing
        // would ever find it again once the row is gone.
        if ($user->avatar_path ?? null) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        tap($user, $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6" x-data="{ confirmingDeletion: @js($errors->isNotEmpty()) }">
    <header>
        <h2 class="text-heading font-semibold text-fg">{{ __('Delete Account') }}</h2>
        <p class="mt-1 text-body text-fg-muted">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
        </p>
    </header>

    <x-ds::button variant="danger" @click="confirmingDeletion = true">{{ __('Delete Account') }}</x-ds::button>

    <x-ds::modal open="confirmingDeletion" size="md"
        title="{{ __('Are you sure you want to delete your account?') }}"
        description="{{ __('Enter your password to confirm you would like to permanently delete your account.') }}">
        <form id="delete-account-form" wire:submit="deleteUser">
            <x-ds::input type="password" id="delete_password" name="password" label="{{ __('Password') }}" wire:model="password"
                autofocus autocomplete="current-password" :error="$errors->first('password')" />
        </form>

        <x-slot:footer>
            <x-ds::button variant="ghost" @click="confirmingDeletion = false">{{ __('Cancel') }}</x-ds::button>
            <x-ds::button variant="danger" type="submit" form="delete-account-form">{{ __('Delete Account') }}</x-ds::button>
        </x-slot:footer>
    </x-ds::modal>
</section>
