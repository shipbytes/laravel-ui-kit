<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-base/7 font-semibold text-zinc-950 dark:text-zinc-50">{{ __('Delete Account') }}</h2>
        <p class="mt-1 text-sm/6 text-zinc-500 dark:text-zinc-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
        </p>
    </header>

    <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" type="button"
        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm/6 font-semibold text-white hover:bg-red-500">
        {{ __('Delete Account') }}
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">
            <h2 class="text-base/7 font-semibold text-zinc-950 dark:text-zinc-50">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-2 text-sm/6 text-zinc-500 dark:text-zinc-400">
                {{ __('Enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <label for="delete_password" class="block text-sm/6 font-medium text-zinc-950 dark:text-zinc-50 mb-2">{{ __('Password') }}</label>
                <input type="password" id="delete_password" wire:model="password" placeholder="{{ __('Password') }}"
                    class="block w-full sm:w-3/4 rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('password') <p class="mt-2 text-sm/6 text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button x-on:click="$dispatch('close')" type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-zinc-800 px-4 py-2 text-sm/6 font-semibold text-zinc-950 dark:text-zinc-50 ring-1 ring-zinc-950/10 dark:ring-white/10 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm/6 font-semibold text-white hover:bg-red-500">
                    {{ __('Delete Account') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
