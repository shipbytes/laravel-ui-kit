<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Shipbytes\UiKit\Support\UiKit;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);
        $this->redirectIntended(default: UiKit::homeUrl(), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-accent-tint text-on-accent-tint">
            <x-ds::icon name="shield-check" size="6" />
        </div>
        <h1 class="text-title font-semibold text-fg">{{ config('ui-kit.copy.confirm_password.heading') }}</h1>
        <p class="mt-2 text-body text-fg-muted">{{ config('ui-kit.copy.confirm_password.subheading') }}</p>
    </div>

    <form wire:submit="confirmPassword" class="space-y-5">
        <x-ds::input wire:model="password" id="password" type="password" name="password" label="Password"
            required autocomplete="current-password" placeholder="Enter your password"
            :error="$errors->first('password')" />

        <x-ds::button type="submit" class="w-full">Confirm</x-ds::button>
    </form>
</div>
