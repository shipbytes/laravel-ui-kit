<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="text-base/7 font-semibold text-zinc-950 dark:text-zinc-50">{{ __('Update Password') }}</h2>
        <p class="mt-1 text-sm/6 text-zinc-500 dark:text-zinc-400">{{ __('Ensure your account is using a long, random password to stay secure.') }}</p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <div>
            <label for="update_password_current_password" class="block text-sm/6 font-medium text-zinc-950 dark:text-zinc-50 mb-2">{{ __('Current Password') }}</label>
            <input type="password" id="update_password_current_password" wire:model="current_password" autocomplete="current-password"
                class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('current_password') <p class="mt-2 text-sm/6 text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="update_password_password" class="block text-sm/6 font-medium text-zinc-950 dark:text-zinc-50 mb-2">{{ __('New Password') }}</label>
                <input type="password" id="update_password_password" wire:model="password" autocomplete="new-password"
                    class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('password') <p class="mt-2 text-sm/6 text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="update_password_password_confirmation" class="block text-sm/6 font-medium text-zinc-950 dark:text-zinc-50 mb-2">{{ __('Confirm Password') }}</label>
                <input type="password" id="update_password_password_confirmation" wire:model="password_confirmation" autocomplete="new-password"
                    class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('password_confirmation') <p class="mt-2 text-sm/6 text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-zinc-950 dark:bg-indigo-600 px-4 py-2 text-sm/6 font-semibold text-white hover:bg-zinc-800 dark:hover:bg-indigo-500">
                {{ __('Save') }}
            </button>
            <x-action-message class="text-sm/6 text-zinc-500 dark:text-zinc-400" on="password-updated">{{ __('Saved.') }}</x-action-message>
        </div>
    </form>
</section>
