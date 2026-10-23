<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Shipbytes\UiKit\Support\UiKit;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        if (! $this->form->authenticate()) {
            // Credentials are valid but the user has 2FA enabled — finish
            // authentication on the challenge page.
            $this->redirectRoute('two-factor.challenge', navigate: true);

            return;
        }

        Session::regenerate();

        $this->redirectIntended(default: UiKit::homeUrl(), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8 text-center">
        <h1 class="text-title font-semibold text-fg">{{ config('ui-kit.copy.login.heading') }}</h1>
        <p class="mt-2 text-body text-fg-muted">{{ config('ui-kit.copy.login.subheading') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <x-ds::input wire:model="form.email" id="email" type="email" name="email" label="Email"
            required autofocus autocomplete="username" placeholder="you@example.com"
            :error="$errors->first('form.email')" />

        <x-ds::input wire:model="form.password" id="password" type="password" name="password" label="Password"
            required autocomplete="current-password" placeholder="Enter your password"
            :error="$errors->first('form.password')" />

        <div class="flex items-center justify-between">
            <x-ds::checkbox wire:model="form.remember" id="remember" name="remember" label="Remember me" />

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate class="text-body font-medium text-accent hover:text-accent-hover">
                    Forgot password?
                </a>
            @endif
        </div>

        <x-ds::button type="submit" class="w-full">Sign in</x-ds::button>
    </form>

    <p class="mt-6 text-center text-body text-fg-muted">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-accent hover:text-accent-hover">Create one free</a>
    </p>
</div>
