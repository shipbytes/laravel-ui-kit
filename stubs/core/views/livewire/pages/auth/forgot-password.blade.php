<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

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
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-accent-tint text-on-accent-tint">
            <x-ds::icon name="lock-closed" size="6" />
        </div>
        <h1 class="text-title font-semibold text-fg">{{ config('ui-kit.copy.forgot_password.heading') }}</h1>
        <p class="mt-2 text-body text-fg-muted">{{ config('ui-kit.copy.forgot_password.subheading') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="space-y-5">
        <x-ds::input wire:model="email" id="email" type="email" name="email" label="Email"
            required autofocus placeholder="you@example.com"
            :error="$errors->first('email')" />

        <x-ds::button type="submit" class="w-full">Send reset link</x-ds::button>
    </form>

    <p class="mt-6 text-center text-body text-fg-muted">
        Remember your password?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-accent hover:text-accent-hover">Back to sign in</a>
    </p>
</div>
