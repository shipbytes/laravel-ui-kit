<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink($this->only('email'));

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');
        session()->flash('status', __($status));
    }
}; ?>

<div>
    <div class="text-center mb-8">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50">
            <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>
        <h1 class="text-2xl font-boldtext tracking-tight text-gray-950">{{ config('ui-kit.copy.forgot_password.heading') }}</h1>
        <p class="mt-2 text-sm text-gray-500 font-booktext leading-relaxed">{{ config('ui-kit.copy.forgot_password.subheading') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="space-y-5">
        <div>
            <label for="email" class="block text-sm font-semibold text-gray-950 mb-2">Email</label>
            <input wire:model="email" id="email" type="email" name="email" required autofocus
                   class="block w-full rounded-xl border-0 px-4 py-3 text-sm text-gray-950 ring-1 ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 transition-shadow"
                   placeholder="you@example.com">
            @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="flex w-full justify-center rounded-full bg-gray-950 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition-all hover:shadow-lg hover:shadow-gray-950/20">
            Send reset link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500 font-booktext">
        Remember your password?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">Back to sign in</a>
    </p>
</div>
